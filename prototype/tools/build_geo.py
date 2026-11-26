"""Tambahkan lokasi (provinsi + titik) ke master cabang dan sisipkan peta provinsi (SVG) ke prototype.

Pemakaian (setelah build_branch_master.py):
    python3 -I prototype/tools/build_geo.py <coverage_area.xlsx> <IDN_adm_2_kabkota.json> <indonesia-prov.geojson>

Sumber (tidak disimpan di repo, hanya hasil turunannya):
    - indonesia-prov.geojson : batas 34 provinsi, "Peta Dasar BAKOSURTANAL 1:250.000"
      (github.com/ans-4175/peta-indonesia-geojson). Dipakai untuk menggambar peta & menentukan provinsi.
    - IDN_adm_2_kabkota.json : batas kabupaten/kota (turunan GADM; github.com/rifani/geojson-political-indonesia).
      Hanya titik tengahnya yang dipakai untuk menempatkan cabang. Lisensi GADM tidak untuk komersial →
      untuk produksi ganti dengan data resmi BIG (Badan Informasi Geospasial).

Cara menempatkan cabang (kode 3 huruf):
    1. kabupaten/kota dari kolom DistrictName ("KECAMATAN, KABUPATEN") baris kecamatan kode itu — terbanyak yang cocok;
    2. bila tidak ada, nama cabang di master;
    3. bila tetap tidak ada, kecocokan kata (mis. "Ogan Komering Ulu Timur" → "Ogan Komering Ulu"),
       dibatasi ke provinsi yang masuk akal untuk region bisnis cabang itu;
    4. provinsi = poligon BAKOSURTANAL yang memuat titik tersebut (yang terdekat bila titik jatuh di laut).
    Cabang yang tetap tidak ketemu diberi "q": "none" dan tidak digambar di peta (dihitung terpisah).
"""
import collections
import json
import math
import pathlib
import re
import sys

import openpyxl

ROOT = pathlib.Path(__file__).resolve().parents[1]
W = 1000.0                      # lebar viewBox peta
LON0, LON1, LAT0, LAT1 = 94.6, 141.4, 6.4, -11.4
K = W / (LON1 - LON0)
H = round((LAT0 - LAT1) * K, 1)
proj = lambda lon, lat: (round((lon - LON0) * K, 1), round((LAT0 - lat) * K, 1))

REGION_PROV = {   # provinsi (nama GADM) yang wajar untuk tiap region bisnis — hanya untuk langkah 3
    "SUMATERA": {"Aceh", "Sumatera Utara", "Sumatera Barat", "Riau", "Kepulauan Riau", "Jambi", "Bengkulu", "Sumatera Selatan", "Bangka-Belitung", "Lampung"},
    "KALIMANTAN": {"Kalimantan Barat", "Kalimantan Tengah", "Kalimantan Selatan", "Kalimantan Timur"},
    "JABATENG": {"Jawa Barat", "Jawa Tengah", "Yogyakarta"},
    "JATIMBARA": {"Jawa Timur", "Bali", "Nusa Tenggara Barat", "Nusa Tenggara Timur"},
    "SUMAPA": {"Sulawesi Utara", "Gorontalo", "Sulawesi Tengah", "Sulawesi Barat", "Sulawesi Selatan", "Sulawesi Tenggara", "Maluku", "Maluku Utara", "Papua", "Irian Jaya Barat"},
    "DKI JAKARTA": {"Jakarta Raya", "Banten", "Jawa Barat"},
    "JABASRA": {"Jawa Barat", "Banten", "Jawa Tengah"},
}
REGION_PROV["KALSTRA"] = REGION_PROV["SUMATERA"] | REGION_PROV["KALIMANTAN"]

norm = lambda x: re.sub(r"[^a-z]", "", re.sub(r"\b(kota|kabupaten|kab)\b\.?", "", (x or "").lower()))
words = lambda x: set(re.findall(r"[a-z]{3,}", re.sub(r"\b(kota|kabupaten|kab|utara|selatan|timur|barat|tengah)\b", "", (x or "").lower())))


def rings(geom):
    if geom["type"] == "Polygon":
        return [geom["coordinates"]]
    return geom["coordinates"]


def ring_area_centroid(ring):
    a = cx = cy = 0.0
    for (x1, y1), (x2, y2) in zip(ring, ring[1:]):
        f = x1 * y2 - x2 * y1
        a += f; cx += (x1 + x2) * f; cy += (y1 + y2) * f
    if abs(a) < 1e-12:
        return 0.0, ring[0][0], ring[0][1]
    return a / 2, cx / (3 * a), cy / (3 * a)


def centroid(geom):
    tot = sx = sy = 0.0
    for poly in rings(geom):
        a, x, y = ring_area_centroid([tuple(p[:2]) for p in poly[0]])
        a = abs(a); tot += a; sx += x * a; sy += y * a
    return (sx / tot, sy / tot) if tot else tuple(rings(geom)[0][0][0][:2])


def inside(pt, ring):
    x, y = pt; c = False
    for (x1, y1), (x2, y2) in zip(ring, ring[1:]):
        if (y1 > y) != (y2 > y) and x < (x2 - x1) * (y - y1) / (y2 - y1) + x1:
            c = not c
    return c


def rdp(pts, eps):
    if len(pts) < 3:
        return pts
    (x1, y1), (x2, y2) = pts[0], pts[-1]
    dx, dy = x2 - x1, y2 - y1
    n = math.hypot(dx, dy) or 1e-9
    idx, dmax = 0, -1
    for i in range(1, len(pts) - 1):
        d = abs(dy * pts[i][0] - dx * pts[i][1] + x2 * y1 - y2 * x1) / n
        if d > dmax:
            idx, dmax = i, d
    if dmax > eps:
        return rdp(pts[: idx + 1], eps)[:-1] + rdp(pts[idx:], eps)
    return [pts[0], pts[-1]]


def main(xlsx, kab_path, prov_path):
    kab = [f for f in json.load(open(kab_path))["features"] if f.get("geometry")]   # beberapa fitur tanpa geometri
    prov = [f for f in json.load(open(prov_path))["features"] if f.get("geometry")]
    # indeks kabupaten/kota → titik tengah
    kidx = collections.defaultdict(list)
    for f in kab:
        p = f["properties"]
        pt = centroid(f["geometry"])
        for nm in [p["NAME_2"]] + re.split(r"[|/]", p.get("VARNAME_2") or ""):
            if nm and nm.strip():
                kidx[norm(nm)].append((p["NAME_1"], pt, p["NAME_2"]))
    # kabupaten per kode dari file coverage
    wb = openpyxl.load_workbook(xlsx, read_only=True, data_only=True)
    ws = wb["Data"]
    head = next(ws.iter_rows(max_row=1, values_only=True))
    ci, di = head.index("CityCode"), head.index("DistrictName")
    covk = collections.defaultdict(collections.Counter)
    for r in ws.iter_rows(min_row=2, values_only=True):
        code = str(r[ci] or "")
        if 3 <= len(code) <= 10 and r[di]:
            covk[code[:3].upper()][str(r[di]).split(",")[-1].strip()] += 1

    pmaster = ROOT / "data" / "branch-master.json"
    master = json.load(open(pmaster))
    provpolys = [(f["properties"]["Propinsi"], [[tuple(p[:2]) for p in poly[0]] for poly in rings(f["geometry"])]) for f in prov]

    def province_of(pt):
        for name, polys in provpolys:
            if any(inside(pt, r) for r in polys):
                return name
        best = min(provpolys, key=lambda np_: min(math.hypot(pt[0] - x, pt[1] - y) for r in np_[1] for (x, y) in r[:: max(1, len(r) // 60)]))
        return best[0]

    q = collections.Counter()
    for m in master:
        hit, how = None, "none"
        for nm, _ in covk[m["c"]].most_common():
            if norm(nm) in kidx:
                hit, how = kidx[norm(nm)][0], "cov"; break
        if not hit and norm(m["n"].split("/")[0]) in kidx:
            hit, how = kidx[norm(m["n"].split("/")[0])][0], "name"
        if not hit:
            allowed = REGION_PROV.get(m["r"], set())
            cands = [nm for nm, _ in covk[m["c"]].most_common(3)] + [m["n"]]
            best, score = None, 0
            for f in kab:
                p = f["properties"]
                if allowed and p["NAME_1"] not in allowed:
                    continue
                for nm in cands:
                    s = len(words(nm) & words(p["NAME_2"]))
                    if s > score:
                        best, score = (p["NAME_1"], centroid(f["geometry"]), p["NAME_2"]), s
            if best:
                hit, how = best, "fuzzy"
        q[how] += 1
        if hit:
            lon, lat = hit[1]
            m["p"] = province_of((lon, lat)).title().replace("Di. ", "").replace("Dki ", "DKI ")
            m["ll"] = [round(lon, 3), round(lat, 3)]
        else:
            m.pop("p", None); m.pop("ll", None)
        m["q"] = how
    pmaster.write_text("[\n" + ",\n".join(json.dumps(m, ensure_ascii=False) for m in master) + "\n]\n")

    # peta provinsi: proyeksi ekuirektangular, disederhanakan (RDP), pulau sangat kecil dibuang
    shapes = []
    for name, polys in provpolys:
        parts = []
        for ring in polys:
            pts = [proj(x, y) for x, y in ring]
            a = abs(ring_area_centroid([(x, -y) for x, y in pts])[0])
            if a < 1.2:          # < ~1.2 px² pada lebar 1000
                continue
            # cincin tertutup: pecah di titik terjauh dari titik awal, sederhanakan tiap separuh
            far = max(range(len(pts)), key=lambda i: math.hypot(pts[i][0] - pts[0][0], pts[i][1] - pts[0][1]))
            pts = rdp(pts[: far + 1], 0.55)[:-1] + rdp(pts[far:], 0.55)
            if len(pts) >= 4:
                parts.append("M" + "L".join(f"{x:g} {y:g}" for x, y in pts) + "Z")
        if parts:
            shapes.append({"n": name.title().replace("Di. ", "").replace("Dki ", "DKI "), "d": "".join(parts)})
    geo = {"w": W, "h": H, "lon0": LON0, "lat0": LAT0, "k": round(K, 5), "prov": shapes}
    html_path = ROOT / "pod-dashboard.html"
    html = html_path.read_text()
    compact = json.dumps(geo, ensure_ascii=False, separators=(",", ":"))
    new, n = re.subn(r"/\*MAP_START\*/.*?/\*MAP_END\*/", lambda _: f"/*MAP_START*/{compact}/*MAP_END*/", html, flags=re.S)
    if n != 1:
        sys.exit("Penanda MAP tidak ditemukan di pod-dashboard.html")
    html_path.write_text(new)
    print(f"lokasi cabang: {dict(q)} · peta {len(shapes)} provinsi, {len(compact) // 1024} KB")
    print("provinsi:", dict(collections.Counter(m.get('p', '-') for m in master)))
    # master juga ditanam di HTML
    comp = json.dumps(master, ensure_ascii=False, separators=(",", ":")).replace("<", "\\u003c")
    html = html_path.read_text()
    html, n = re.subn(r"/\*BRANCH_MASTER_START\*/.*?/\*BRANCH_MASTER_END\*/", lambda _: f"/*BRANCH_MASTER_START*/{comp}/*BRANCH_MASTER_END*/", html, flags=re.S)
    html_path.write_text(html)


if __name__ == "__main__":
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    main(*sys.argv[1:])
