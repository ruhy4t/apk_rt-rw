<?php

namespace App\Services;

use App\Models\Resident;

final class ResidentInputRules
{
    public static function rules(?Resident $resident = null): array
    {
        return [
            'full_name' => ['required', 'string', 'max:160'],
            'national_id' => [$resident?->national_id ? 'nullable' : 'required', 'string', 'regex:/^[0-9]{16}$/D'],
            'birthplace' => ['required', 'string', 'max:160'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'religion' => ['required', 'string', 'max:80'],
            'marital_status' => ['required', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'education' => ['required', 'string', 'max:80'],
            'occupation' => ['required', 'string', 'max:160'],
            'residency_status' => ['required', 'in:Tetap,Kontrak/Kos'],
            'phone' => ['nullable', 'string', 'max:32'],
            'clear_phone' => ['nullable', 'boolean'],
        ];
    }
}
