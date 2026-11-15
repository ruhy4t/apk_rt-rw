<?php

namespace App\Services\ResidentImport;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class ResidentWorkbook
{
    public const HEADERS = ['kode_rt', 'kode_rumah_tangga', 'nomor_kk', 'alamat', 'nama_lengkap', 'nik', 'hubungan_keluarga', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'status_perkawinan', 'pendidikan', 'pekerjaan', 'status_kependudukan', 'telepon'];

    public const MAX_ROWS = 200;

    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function create(array $rows = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rt-xlsx-');
        $zip = new ZipArchive;
        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Template tidak dapat dibuat.');
        }
        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="'.self::NS.'"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="16" width="24" customWidth="1"/></cols><sheetData>';
        foreach ([self::HEADERS, ...$rows] as $index => $values) {
            $row = $index + 1;
            $sheet .= '<row r="'.$row.'">';
            foreach (array_values($values) as $column => $value) {
                $sheet .= '<c r="'.chr(65 + $column).$row.'" s="'.($index === 0 ? 1 : 0).'" t="inlineStr"><is><t xml:space="preserve">'.$escape($value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Warga" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet xmlns="'.self::NS.'"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        if (! $zip->close()) {
            throw new RuntimeException('Template tidak dapat disimpan.');
        }

        return $path;
    }

    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Berkas bukan workbook .xlsx yang dapat dibaca.');
        }
        try {
            $total = 0;
            $names = [];
            if ($zip->numFiles > 64) {
                throw new RuntimeException('Workbook memuat terlalu banyak bagian.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = $entry['name'];
                $lower = strtolower($name);
                if (isset($names[$lower]) || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/') || str_contains($lower, 'vba') || str_contains($lower, 'externallink') || str_ends_with($lower, '.bin')) {
                    throw new RuntimeException('Workbook memuat bagian yang tidak diizinkan.');
                }
                $names[$lower] = true;
                $total += $entry['size'];
                if ($entry['size'] > 2097152 || $total > 8388608 || ($entry['size'] > 65536 && $entry['size'] / max(1, $entry['comp_size']) > 100)) {
                    throw new RuntimeException('Ukuran isi workbook melebihi batas aman.');
                }
                if (str_ends_with($lower, '.rels')) {
                    $relations = $this->xml($zip->getFromIndex($i, 2097153));
                    foreach ($relations->getElementsByTagNameNS('*', 'Relationship') as $relation) {
                        if (strtolower($relation->getAttribute('TargetMode')) === 'external') {
                            throw new RuntimeException('Tautan eksternal tidak diizinkan.');
                        }
                    }
                }
            }
            $types = $zip->getFromName('[Content_Types].xml', 2097153);
            if (! is_string($types) || str_contains(strtolower($types), 'macroenabled')) {
                throw new RuntimeException('Gunakan template .xlsx tanpa makro.');
            }
            $book = $this->xpath($this->xml($zip->getFromName('xl/workbook.xml', 2097153)));
            $sheets = $book->query('/x:workbook/x:sheets/x:sheet');
            if ($sheets->length !== 1 || $sheets->item(0)->getAttribute('name') !== 'Warga' || in_array($sheets->item(0)->getAttribute('state'), ['hidden', 'veryHidden'], true)) {
                throw new RuntimeException('Workbook harus memiliki satu sheet terlihat bernama Warga.');
            }
            $rid = $sheets->item(0)->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $rels = $this->xml($zip->getFromName('xl/_rels/workbook.xml.rels', 2097153));
            $sheetPath = null;
            foreach ($rels->getElementsByTagNameNS('*', 'Relationship') as $rel) {
                if ($rel->getAttribute('Id') === $rid && str_ends_with($rel->getAttribute('Type'), '/worksheet')) {
                    $target = $rel->getAttribute('Target');
                    $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
                }
            }
            if (! $sheetPath || ! preg_match('~\Axl/worksheets/sheet[0-9]+\.xml\z~D', $sheetPath)) {
                throw new RuntimeException('Struktur sheet tidak didukung. Gunakan template yang disediakan.');
            }
            $strings = [];
            $shared = $zip->getFromName('xl/sharedStrings.xml', 2097153);
            if ($shared !== false) {
                $sharedXml = $this->xpath($this->xml($shared));
                foreach ($sharedXml->query('/x:sst/x:si') as $item) {
                    $text = '';
                    foreach ($sharedXml->query('x:t|x:r/x:t', $item) as $node) {
                        $text .= $node->textContent;
                    }
                    $strings[] = $text;
                    if (count($strings) > 5000) {
                        throw new RuntimeException('Tabel teks workbook terlalu besar.');
                    }
                }
            }
            $sheet = $this->xpath($this->xml($zip->getFromName($sheetPath, 2097153)));
            if ($sheet->query('//x:f')->length || $sheet->query('//x:hyperlink')->length || $sheet->query('//x:mergeCell')->length) {
                throw new RuntimeException('Formula, tautan, dan sel gabungan tidak diizinkan pada template impor.');
            }
            $rows = [];
            $seenRows = [];
            $headerFound = false;
            foreach ($sheet->query('/x:worksheet/x:sheetData/x:row') as $row) {
                $number = (int) $row->getAttribute('r');
                if ($number < 1 || $number > 10000 || isset($seenRows[$number])) {
                    throw new RuntimeException('Nomor baris workbook tidak valid.');
                }
                $seenRows[$number] = true;
                $values = array_fill(0, count(self::HEADERS), '');
                $seenCells = [];
                foreach ($sheet->query('x:c', $row) as $cell) {
                    if (! preg_match('/\A([A-Z]{1,3})([1-9][0-9]*)\z/D', $cell->getAttribute('r'), $match) || (int) $match[2] !== $number) {
                        throw new RuntimeException('Koordinat sel tidak valid.');
                    }
                    $column = 0;
                    foreach (str_split($match[1]) as $letter) {
                        $column = $column * 26 + ord($letter) - 64;
                    }
                    if (isset($seenCells[$column])) {
                        throw new RuntimeException('Terdapat sel ganda.');
                    }
                    $seenCells[$column] = true;
                    $type = $cell->getAttribute('t');
                    $value = '';
                    if ($type === 'inlineStr') {
                        foreach ($sheet->query('x:is/x:t|x:is/x:r/x:t', $cell) as $text) {
                            $value .= $text->textContent;
                        }
                    } elseif ($type === 's') {
                        $index = $sheet->evaluate('string(x:v)', $cell);
                        if (! ctype_digit($index) || ! array_key_exists((int) $index, $strings)) {
                            throw new RuntimeException('Referensi teks sel tidak valid.');
                        }
                        $value = $strings[(int) $index];
                    } elseif ($sheet->evaluate('string(x:v)', $cell) !== '') {
                        throw new RuntimeException('Baris '.$number.': semua kolom harus berformat Text, termasuk NIK, KK, RT, telepon, dan tanggal ISO.');
                    }
                    if (mb_strlen($value) > 2000 || ($column > count(self::HEADERS) && $value !== '')) {
                        throw new RuntimeException('Kolom atau panjang sel tidak sesuai template.');
                    }
                    if ($column <= count(self::HEADERS)) {
                        $values[$column - 1] = trim($value);
                    }
                }
                if ($number === 1) {
                    if ($values !== self::HEADERS) {
                        throw new RuntimeException('Nama dan urutan kolom harus sama dengan template.');
                    }
                    $headerFound = true;
                } elseif (array_filter($values, fn ($value) => $value !== '')) {
                    $rows[] = ['row' => $number, 'values' => array_combine(self::HEADERS, $values)];
                    if (count($rows) > self::MAX_ROWS) {
                        throw new RuntimeException('Maksimal '.self::MAX_ROWS.' warga per impor.');
                    }
                }
            }
            if (! $headerFound || ! $rows) {
                throw new RuntimeException('Workbook tidak memiliki baris warga untuk diperiksa.');
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function xml(string|false $xml): DOMDocument
    {
        if (! is_string($xml) || strlen($xml) > 2097152 || str_contains($xml, "\0") || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            throw new RuntimeException('XML workbook tidak diizinkan atau tidak lengkap.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = new DOMDocument;
            if (! $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('XML workbook tidak dapat dibaca.');
            }

            return $doc;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function xpath(DOMDocument $doc): DOMXPath
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('x', self::NS);

        return $xpath;
    }
}
