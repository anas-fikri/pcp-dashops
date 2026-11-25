<?php

namespace App\Enums;

/**
 * Status POD terakhir per AWB (hanya untuk kiriman POD balik).
 * Kiriman "Tidak Balik" disimpan dengan pod_required = false dan pod_status = null.
 */
enum PodStatus: string
{
    // Belum balik — paket belum terkirim
    case NotDelivered = 'NOT_DELIVERED';

    // Belum balik — paket terkirim, POD fisik belum kembali
    case MasihDiPelanggan = 'MASIH_DI_PELANGGAN';
    case Podc = 'PODC';
    case Redoc = 'REDOC';
    case Upload = 'UPLOAD';

    // Sudah balik
    case Stpod = 'STPOD';
    case Podp = 'PODP';
    case Arsip = 'ARSIP';

    public function label(): string
    {
        return match ($this) {
            self::NotDelivered => 'Not Yet Delivered',
            self::MasihDiPelanggan => 'Masih di Pelanggan',
            self::Podc => 'PODC',
            self::Redoc => 'Redoc',
            self::Upload => 'Upload',
            self::Stpod => 'STPOD',
            self::Podp => 'PODP',
            self::Arsip => 'Arsip',
        };
    }

    public function isReturned(): bool
    {
        return in_array($this, self::returned(), true);
    }

    /** @return list<self> */
    public static function returned(): array
    {
        return [self::Stpod, self::Podp, self::Arsip];
    }

    /** @return list<self> */
    public static function deliveredPending(): array
    {
        return [self::MasihDiPelanggan, self::Podc, self::Redoc, self::Upload];
    }

    /** @return list<self> */
    public static function pending(): array
    {
        return [self::NotDelivered, ...self::deliveredPending()];
    }

    /** @param list<self> $statuses @return list<string> */
    public static function values(array $statuses): array
    {
        return array_map(fn (self $s) => $s->value, $statuses);
    }
}
