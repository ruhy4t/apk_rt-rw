<?php

namespace App\Models\Concerns;

use App\Services\IdentityDigest;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

trait HasEncryptedPersonalData
{
    public function initializeHasEncryptedPersonalData(): void
    {
        $this->mergeHidden(['private_data', ...array_keys(static::PERSONAL_FIELDS)]);
    }

    public static function personalLookup(string $field, string $value): string
    {
        $digest = app(IdentityDigest::class)->make('private:'.static::class.':'.$field, mb_strtolower($value));

        return 'p:'.substr($digest, 0, $field === 'relationship' ? 40 : 64);
    }

    public function getAttribute($key): mixed
    {
        if (is_string($key) && array_key_exists($key, static::PERSONAL_FIELDS)) {
            $data = parent::getAttribute('private_data');
            if (is_array($data) && array_key_exists($key, $data)) {
                return $key === 'birth_date' && $data[$key] !== null ? Carbon::parse($data[$key])->startOfDay() : $data[$key];
            }
            $raw = $this->getAttributes()[$key] ?? null;
            if ($raw === '[protected]' || (is_string($raw) && str_starts_with($raw, 'p:'))) {
                throw new RuntimeException('Data privat tidak tersedia untuk dibaca.');
            }
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value): mixed
    {
        if (array_key_exists($key, static::PERSONAL_FIELDS)) {
            if (! config('personal_data.encryption_enabled') && empty($this->getAttributes()['private_data'])) {
                return parent::setAttribute($key, $value);
            }
            $data = parent::getAttribute('private_data');
            if ($data === null) {
                $data = [];
                $legacy = [];
                foreach (static::PERSONAL_FIELDS as $field => $replacement) {
                    if (array_key_exists($field, $this->getAttributes())) {
                        $legacy[$field] = parent::getAttribute($field);
                    }
                }
                foreach ($legacy as $field => $original) {
                    $data[$field] = $original instanceof DateTimeInterface ? $original->format('Y-m-d') : $original;
                    parent::setAttribute($field, in_array($field, ['username', 'relationship'], true) && $original !== null
                        ? static::personalLookup($field, $original)
                        : static::PERSONAL_FIELDS[$field]);
                }
            }
            if (! is_array($data)) {
                throw new RuntimeException('Penyimpanan data privat tidak valid.');
            }
            $data[$key] = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
            parent::setAttribute('private_data', $data);
            $replacement = in_array($key, ['username', 'relationship'], true) && $value !== null
                ? static::personalLookup($key, $value)
                : static::PERSONAL_FIELDS[$key];

            return parent::setAttribute($key, $replacement);
        }

        return parent::setAttribute($key, $value);
    }

    public function encryptLegacyPersonalData(): void
    {
        if (! config('personal_data.encryption_enabled')) {
            throw new RuntimeException('Enkripsi tambahan belum diaktifkan.');
        }
        if (parent::getAttribute('private_data') !== null) {
            return;
        }
        $values = [];
        foreach (static::PERSONAL_FIELDS as $field => $replacement) {
            $values[$field] = parent::getAttribute($field);
        }
        $this->forceFill($values)->saveOrFail();
    }

    public static function personalColumns(array $columns): array
    {
        return config('personal_data.encryption_enabled') ? [...$columns, 'private_data'] : $columns;
    }
}
