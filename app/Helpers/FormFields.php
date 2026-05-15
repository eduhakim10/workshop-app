<?php

namespace App\Helpers;

use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;
use Illuminate\Support\Str;

/**
 * Reusable Filament form helpers.
 *
 * Tujuan utama: menyediakan input mata uang Rupiah dengan format
 * Indonesia (titik sebagai pemisah ribuan, koma sebagai pemisah desimal)
 * sehingga user mengetik / melihat angka seperti "7.720.000,00"
 * sementara database menyimpan angka float (7720000.00).
 */
class FormFields
{
    /**
     * Input angka dengan format Rupiah (1.234.567,89) dan prefix "Rp".
     */
    public static function rupiah(string $name, ?string $label = null): TextInput
    {
        return self::applyRupiahMask(
            TextInput::make($name)->label($label ?? Str::headline($name))
        );
    }

    /**
     * Terapkan masking Rupiah ke instance TextInput yang sudah ada
     * (berguna untuk field dengan konfigurasi custom).
     */
    public static function applyRupiahMask(TextInput $input): TextInput
    {
        return $input
            ->prefix('Rp')
            ->inputMode('decimal')
            ->mask(RawJs::make("\$money(\$input, ',', '.', 2)"))
            // hapus pemisah ribuan, lalu ganti koma desimal jadi titik saat dehydrate
            ->stripCharacters('.')
            ->rules(['nullable', 'regex:/^\d+([,]\d{1,2})?$/'])
            ->dehydrateStateUsing(fn ($state) => self::parseRupiah($state))
            ->formatStateUsing(fn ($state) => self::formatRupiah($state));
    }

    /**
     * Konversi nilai dari DB (float / string angka) ke tampilan Rupiah ID.
     */
    public static function formatRupiah($state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $value = is_numeric($state) ? (float) $state : self::parseRupiah($state);

        if ($value === null) {
            return null;
        }

        return number_format($value, 2, ',', '.');
    }

    /**
     * Parse string Rupiah ("7.720.000,00") -> float (7720000.00).
     */
    public static function parseRupiah($state): ?float
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_numeric($state)) {
            return (float) $state;
        }

        $clean = str_replace('.', '', (string) $state);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
