<?php

namespace App\Console\Commands;

use App\Models\AssetLoan;
use App\Models\Contribution;
use App\Models\FinancialEntry;
use App\Models\Household;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EncryptPersonalData extends Command
{
    protected $signature = 'app:encrypt-personal-data {--apply : Convert existing rows} {--backup-confirmed : A restorable backup has been verified}';

    protected $description = 'Count or encrypt legacy personal records without displaying their contents';

    public function handle(): int
    {
        if ($this->option('apply') && (! $this->option('backup-confirmed') || ! config('personal_data.encryption_enabled'))) {
            $this->components->error('Konversi memerlukan backup terverifikasi dan enkripsi tambahan yang telah diaktifkan.');

            return self::FAILURE;
        }
        foreach ([User::class, Household::class, Resident::class, ServiceRequest::class, AssetLoan::class, Contribution::class, FinancialEntry::class, StoredFile::class] as $modelClass) {
            $model = new $modelClass;
            if (! Schema::hasColumn($model->getTable(), 'private_data')) {
                $this->components->error('Migrasi kolom data terenkripsi belum lengkap. Tidak ada konversi dijalankan.');

                return self::FAILURE;
            }
        }
        foreach ([User::class, Household::class, Resident::class, ServiceRequest::class, AssetLoan::class, Contribution::class, FinancialEntry::class, StoredFile::class] as $modelClass) {
            $query = $modelClass::query()->whereNull('private_data');
            $count = (clone $query)->count();
            $this->line((new $modelClass)->getTable().': '.$count.' record belum dikonversi.');
            if (! $this->option('apply')) {
                continue;
            }
            $query->select('id')->chunkById(100, function ($rows) use ($modelClass) {
                DB::transaction(function () use ($rows, $modelClass) {
                    foreach ($rows as $row) {
                        $record = $modelClass::query()->lockForUpdate()->findOrFail($row->id);
                        $record->timestamps = false;
                        $record->encryptLegacyPersonalData();
                    }
                });
            });
        }

        return self::SUCCESS;
    }
}
