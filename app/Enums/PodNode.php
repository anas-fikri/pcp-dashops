<?php

namespace App\Enums;

/**
 * Node pada pohon status POD (sesuai sketsa). Dipakai sebagai kunci drill-down:
 * setiap angka di summary = COUNT kiriman yang memenuhi node tsb + filter aktif.
 */
enum PodNode: string
{
    case All = 'all';
    case TidakBalik = 'tidak_balik';
    case Balik = 'balik';
    case BelumBalik = 'belum_balik';
    case NotDelivered = 'not_delivered';
    case Delivered = 'delivered';
    case MasihDiPelanggan = 'masih_di_pelanggan';
    case Podc = 'podc';
    case Redoc = 'redoc';
    case Upload = 'upload';
    case SudahBalik = 'sudah_balik';
    case Stpod = 'stpod';
    case Podp = 'podp';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Semua Kiriman',
            self::TidakBalik => 'Tidak Balik',
            self::Balik => 'POD Balik',
            self::BelumBalik => 'Belum Balik',
            self::NotDelivered => 'Not Yet Delivered',
            self::Delivered => 'Delivered (POD belum kembali)',
            self::SudahBalik => 'Sudah Balik',
            default => $this->status()->label(),
        };
    }

    public function parent(): ?self
    {
        return match ($this) {
            self::All => null,
            self::TidakBalik, self::Balik => self::All,
            self::BelumBalik, self::SudahBalik => self::Balik,
            self::NotDelivered, self::Delivered => self::BelumBalik,
            self::MasihDiPelanggan, self::Podc, self::Redoc, self::Upload => self::Delivered,
            self::Stpod, self::Podp, self::Arsip => self::SudahBalik,
        };
    }

    /** Kedalaman di pohon (All = 0), untuk indentasi tampilan. */
    public function depth(): int
    {
        return $this->parent() ? $this->parent()->depth() + 1 : 0;
    }

    /** @return list<self> dari root sampai node ini */
    public function path(): array
    {
        return $this->parent() ? [...$this->parent()->path(), $this] : [$this];
    }

    /** Status tunggal untuk node daun. */
    public function status(): ?PodStatus
    {
        return match ($this) {
            self::MasihDiPelanggan => PodStatus::MasihDiPelanggan,
            self::Podc => PodStatus::Podc,
            self::Redoc => PodStatus::Redoc,
            self::Upload => PodStatus::Upload,
            self::Stpod => PodStatus::Stpod,
            self::Podp => PodStatus::Podp,
            self::Arsip => PodStatus::Arsip,
            self::NotDelivered => PodStatus::NotDelivered,
            default => null,
        };
    }

    /**
     * Status yang tercakup node. null = tidak dibatasi status
     * (All, TidakBalik, Balik dibatasi lewat pod_required).
     *
     * @return list<PodStatus>|null
     */
    public function statuses(): ?array
    {
        return match ($this) {
            self::All, self::TidakBalik, self::Balik => null,
            self::BelumBalik => PodStatus::pending(),
            self::Delivered => PodStatus::deliveredPending(),
            self::SudahBalik => PodStatus::returned(),
            default => [$this->status()],
        };
    }

    /** true/false = filter pod_required; null = tanpa filter. */
    public function podRequired(): ?bool
    {
        return match ($this) {
            self::All => null,
            self::TidakBalik => false,
            default => true,
        };
    }

    /** Node yang umurnya bermakna "lead time kembali" (bukan umur outstanding). */
    public function isReturnedBranch(): bool
    {
        return $this === self::SudahBalik || $this->parent() === self::SudahBalik;
    }

    /** Urutan baris pohon di summary (depth-first, sesuai sketsa). @return list<self> */
    public static function tree(): array
    {
        return [
            self::All,
            self::TidakBalik,
            self::Balik,
            self::BelumBalik,
            self::NotDelivered,
            self::Delivered,
            self::MasihDiPelanggan,
            self::Podc,
            self::Redoc,
            self::Upload,
            self::SudahBalik,
            self::Stpod,
            self::Podp,
            self::Arsip,
        ];
    }

    public static function forStatus(PodStatus $status): self
    {
        foreach (self::cases() as $node) {
            if ($node->status() === $status) {
                return $node;
            }
        }

        throw new \LogicException("No node for status {$status->value}");
    }
}
