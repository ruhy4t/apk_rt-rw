<?php

namespace App\Services;

use App\Models\RtArea;
use App\Models\RwArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DocumentSignatory
{
    /**
     * Capture inside the caller's publishing transaction, using server-derived
     * area IDs. This records a manual signatory, not an electronic signature.
     */
    public function capture(Model $document, ?int $rtAreaId): array
    {
        if (! Schema::hasColumn($document->getTable(), 'signatory_data')) {
            $this->unavailable();
        }
        $existing = $document->getAttribute('signatory_data');
        if ($existing !== null) {
            if (is_array($existing) && $existing !== []) {
                return $existing;
            }
            throw ValidationException::withMessages([
                'signatory' => 'Identitas penandatangan yang tersimpan tidak valid. Minta administrator memeriksa data; identitas tidak diganti otomatis.',
            ]);
        }

        $type = $rtAreaId === null ? 'RW' : 'RT';
        $table = $rtAreaId === null ? 'rw_areas' : 'rt_areas';
        if (! Schema::hasColumn($table, 'chairman_name')) {
            $this->unavailable();
        }
        if ($rtAreaId !== null) {
            $area = RtArea::query()->whereKey($rtAreaId)->lockForUpdate()->first();
        } else {
            $areas = RwArea::query()->orderBy('id')->lockForUpdate()->get();
            if ($areas->count() !== 1) {
                throw ValidationException::withMessages(['signatory' => 'Penerbitan tingkat RW memerlukan tepat satu wilayah RW. Periksa pengaturan wilayah.']);
            }
            $area = $areas->first();
        }
        if (! $area || ! is_string($area->chairman_name) || trim($area->chairman_name) === '') {
            throw ValidationException::withMessages([
                'signatory' => 'Ketua '.$type.' belum ditetapkan. Simpan sebagai draf dan minta administrator menetapkan nama ketua melalui Pengaturan wilayah.',
            ]);
        }

        return [
            'name' => trim($area->chairman_name),
            'title' => 'Ketua '.$type.' '.$area->code,
            'area_type' => strtolower($type),
            'area_id' => $area->id,
            'area_code' => $area->code,
            'issued_at' => now()->toIso8601String(),
        ];
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages([
            'signatory' => 'Pengaturan penandatangan belum tersedia. Administrator perlu menerapkan pembaruan struktur database sebelum penerbitan. Draf tetap dapat disimpan.',
        ]);
    }
}
