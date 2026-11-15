<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RwIdentity
{
    public const FIELDS = ['village_name', 'district_name', 'city_name'];

    public static function rules(bool $required = false): array
    {
        $rules = [];
        foreach (self::FIELDS as $field) {
            $rules[$field] = $required
                ? ['required', 'string', 'max:120']
                : ['nullable', 'required_with:'.implode(',', array_diff(self::FIELDS, [$field])), 'string', 'max:120'];
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_with' => 'Lengkapi Kelurahan/Desa, Kecamatan, dan Kota/Kabupaten bersama-sama.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
        ];
    }

    public static function attributes(): array
    {
        return ['village_name' => 'Kelurahan/Desa', 'district_name' => 'Kecamatan', 'city_name' => 'Kota/Kabupaten'];
    }

    public static function ready(): bool
    {
        return Schema::hasColumns('rw_areas', self::FIELDS);
    }

    public static function forStorage(array $data): array
    {
        $hasValue = false;
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                $data[$field] = $value === '' ? null : $value;
                $hasValue = $hasValue || $data[$field] !== null;
            }
        }
        if (! self::ready()) {
            if ($hasValue) {
                throw ValidationException::withMessages(['rw_identity' => 'Kolom identitas RW belum tersedia. Terapkan file UPDATE-DATABASE.sql terbaru terlebih dahulu.']);
            }
            foreach (self::FIELDS as $field) {
                unset($data[$field]);
            }
        }

        return $data;
    }
}
