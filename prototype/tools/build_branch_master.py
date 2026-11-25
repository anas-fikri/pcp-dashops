"""Bangun master cabang untuk prototype dari file coverage area (xlsx).

Pemakaian:
    python3 -I prototype/tools/build_branch_master.py <coverage_area.xlsx>

Output:
    prototype/data/branch-master.json  (ringkasan per kode cabang)
    + menyisipkan JSON yang sama ke prototype/pod-dashboard.html di antara
      penanda /*BRANCH_MASTER_START*/ ... /*BRANCH_MASTER_END*/

Aturan pemetaan (lihat docs/pod-dashboard-spec.md):
    - Kode cabang = 3 huruf pertama CityCode.
    - Tier 1 / KOTA UTAMA            -> BRANCH (Branch)
      Tier 2 / KOTA LANJUTAN         -> SUB    (Sub Branch)
      Remote Area                    -> SITE   (Site)
    - Office_Type dinormalisasi: Cabang, Agen, Perwakilan, Satelit.
      Grup: Cabang & Satelit -> INTERNAL; Agen & Perwakilan -> AGENT (Perwakilan).
    - Region INTERNATIONAL dikecualikan (bukan tujuan POD domestik).
    - Region: file memakai dua tingkat yang bersarang. Baris kecamatan (CityCode <= 10 karakter)
      memakai region rinci (SUMATERA, KALIMANTAN, JATIMBARA, JABATENG, DKI JAKARTA, SUMAPA, ...),
      baris kelurahan memakai region induk (KALSTRA, JABASRA, SUMAPA). Dipakai region rinci;
      bila tidak ada, region induk.
File mentah tidak disimpan di repo; hanya ringkasan per cabang.
"""
import collections
import json
import pathlib
import re
import sys

import openpyxl

ROOT = pathlib.Path(__file__).resolve().parents[1]
TIER = {"Tier 1": "BRANCH", "Tier 2": "SUB", "Remote Area": "SITE"}
OFFICE = {"cabang": "Cabang", "agen": "Agen", "perwakilan": "Perwakilan", "satelit": "Satelit"}
GROUP = {"Cabang": "INTERNAL", "Satelit": "INTERNAL", "Agen": "AGENT", "Perwakilan": "AGENT"}


def title(s: str) -> str:
    s = re.sub(r"\s+", " ", s.strip())
    return " ".join(w if w in {"KAB.", "KOTA"} else w.capitalize() for w in s.split(" ")).replace("Kab.", "Kab.")


def main(path: str) -> None:
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = wb["Data"]
    head = [c for c in next(ws.iter_rows(max_row=1, values_only=True))]
    idx = {name: head.index(name) for name in ["CityCode", "RegionName", "DistrictName", "Tier", "Office_Type"]}

    areas = collections.defaultdict(lambda: collections.Counter())          # code -> Counter(level)
    office = collections.defaultdict(lambda: collections.Counter())         # (code, level) -> Counter(office)
    region = collections.defaultdict(collections.Counter)        # region rinci (baris kecamatan)
    region_parent = collections.defaultdict(collections.Counter)  # region induk (baris kelurahan)
    names = collections.defaultdict(collections.Counter)
    skipped = collections.Counter()

    for r in ws.iter_rows(min_row=2, values_only=True):
        code_full = str(r[idx["CityCode"]] or "").strip()
        reg = (r[idx["RegionName"]] or "").strip()
        tier = (r[idx["Tier"]] or "").strip()
        off = OFFICE.get(str(r[idx["Office_Type"]] or "").strip().lower())
        if len(code_full) < 3 or reg == "INTERNATIONAL":
            skipped["international_or_blank"] += 1
            continue
        if tier not in TIER or not off:
            skipped["unknown_tier_or_office"] += 1
            continue
        code, lv = code_full[:3].upper(), TIER[tier]
        areas[code][lv] += 1
        office[(code, lv)][off] += 1
        (region if len(code_full) <= 10 else region_parent)[code][reg] += 1
        dist = str(r[idx["DistrictName"]] or "")
        if len(code_full) <= 10 and dist:
            parts = [x.strip() for x in dist.split(",")]
            # baris kota utama ("JAKARTA" atau "MEDAN, MEDAN") diberi bobot besar agar jadi nama cabang
            names[code][parts[-1]] += 100000 if len(parts) == 1 else 1000 if parts[0] == parts[-1] else 1

    master = []
    for code in sorted(areas):
        lv = areas[code]
        name = names[code].most_common(1)[0][0] if names[code] else code
        master.append({
            "c": code,
            "n": title(name),
            "r": (region[code] or region_parent[code]).most_common(1)[0][0],
            "a": [lv.get("BRANCH", 0), lv.get("SUB", 0), lv.get("SITE", 0)],
            "o": [office[(code, l)].most_common(1)[0][0] if office[(code, l)] else "" for l in ("BRANCH", "SUB", "SITE")],
        })

    out_dir = ROOT / "data"
    out_dir.mkdir(exist_ok=True)
    (out_dir / "branch-master.json").write_text(
        "[\n" + ",\n".join(json.dumps(m, ensure_ascii=False) for m in master) + "\n]\n")

    html_path = ROOT / "pod-dashboard.html"
    html = html_path.read_text()
    compact = json.dumps(master, ensure_ascii=False, separators=(",", ":")).replace("<", "\\u003c")
    new, n = re.subn(r"/\*BRANCH_MASTER_START\*/.*?/\*BRANCH_MASTER_END\*/",
                     lambda _: f"/*BRANCH_MASTER_START*/{compact}/*BRANCH_MASTER_END*/", html, flags=re.S)
    if n != 1:
        sys.exit("Penanda BRANCH_MASTER tidak ditemukan di pod-dashboard.html")
    html_path.write_text(new)

    lv_tot = collections.Counter()
    for m in master:
        for i, l in enumerate(("BRANCH", "SUB", "SITE")):
            lv_tot[l] += m["a"][i]
    print(f"{len(master)} cabang · area per level {dict(lv_tot)} · dilewati {dict(skipped)}")
    print("region:", dict(collections.Counter(m["r"] for m in master)))


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
