<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use App\Services\IdentityDigest;
use App\Services\ResidentImport\ResidentWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ResidentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function staff(): User
    {
        config(['personal_data.encryption_enabled' => true, 'session.driver' => 'database']);
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji']);

        return User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
    }

    private function row(array $changes = []): array
    {
        return array_values(array_replace(array_combine(ResidentWorkbook::HEADERS, ['05', 'KEL-UJI', '0000000000000001', 'Alamat Uji', 'Kepala Uji', '0000000000000002', 'Kepala Keluarga', 'Kota Uji', '1990-01-01', 'Laki-laki', 'Keyakinan Uji', 'Kawin', 'Sarjana', 'Karyawan', 'Tetap', '081234567898']), $changes));
    }

    private function upload(array $rows): UploadedFile
    {
        $path = app(ResidentWorkbook::class)->create($rows);
        try {
            return UploadedFile::fake()->createWithContent('warga.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    private function token($response): string
    {
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->assertNotNull($cookie);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->assertSame(1, preg_match('/name="import_token" value="([^"]+)"/', $response->getContent(), $match));

        return html_entity_decode($match[1], ENT_QUOTES);
    }

    public function test_template_keeps_identifiers_as_text_and_requires_staff(): void
    {
        $this->get(route('residents.import.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'resident']))->get(route('residents.import.template'))->assertForbidden();
        $operator = $this->staff();
        $response = $this->actingAs($operator)->get(route('residents.import.template'))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $rows = app(ResidentWorkbook::class)->read($path);
            $this->assertSame('0000000000000002', $rows[0]['values']['nik']);
            $this->assertSame('05', $rows[0]['values']['kode_rt']);
        } finally {
            unlink($path);
        }
    }

    public function test_preview_is_masked_and_saves_nothing_until_confirmation(): void
    {
        $this->actingAs($this->staff());
        $response = $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row(), $this->row(['nama_lengkap' => 'Anak Uji', 'nik' => '0000000000000003', 'hubungan_keluarga' => 'Anak'])])])
            ->assertOk()->assertSee('Pratinjau impor')->assertSee('08*********8')
            ->assertDontSee('081234567898')->assertDontSee('0000000000000002')->assertDontSee('0000000000000001');
        $this->assertDatabaseCount('residents', 0);
        $token = $this->token($response);
        $this->post(route('residents.import.store'), ['import_token' => $token])->assertRedirect(route('residents.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('residents', 2);
        $household = Household::query()->firstOrFail();
        $this->assertSame('0000000000000001', $household->family_card_number);
        $this->assertSame(1, $household->residents()->heads()->count());
        $this->assertNull(DB::table('residents')->value('phone'));
        $this->assertDatabaseHas('audit_events', ['action' => 'residents.imported']);
        $this->post(route('residents.import.store'), ['import_token' => $token])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('residents', 2);
    }

    public function test_workbook_is_not_archived_and_only_encrypted_personal_values_are_persisted(): void
    {
        foreach (['local', 'public', 'b2'] as $disk) {
            Storage::fake($disk);
        }
        $this->actingAs($this->staff());
        $preview = $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row()])])->assertOk();
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('stored_files', 0);
        $sessionData = json_encode(session()->all());
        foreach (['Kepala Uji', 'Alamat Uji', '0000000000000002', '081234567898'] as $value) {
            $this->assertStringNotContainsString($value, $sessionData);
        }
        $this->post(route('residents.import.store'), ['import_token' => $this->token($preview)])
            ->assertRedirect(route('residents.index'))->assertSessionHasNoErrors();

        $resident = Resident::query()->firstOrFail();
        $expected = [
            'full_name' => 'Kepala Uji', 'relationship' => 'Kepala Keluarga',
            'phone' => '081234567898', 'birthplace' => 'Kota Uji', 'birth_date' => '1990-01-01',
            'gender' => 'Laki-laki', 'marital_status' => 'Kawin', 'education' => 'Sarjana',
            'occupation' => 'Karyawan', 'residency_status' => 'Tetap',
        ];
        $payload = json_decode(Crypt::decryptString($resident->getRawOriginal('private_data')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($expected as $field => $value) {
            $this->assertSame($value, $payload[$field]);
            $this->assertNotSame($value, $resident->getRawOriginal($field));
        }
        $this->assertSame('0000000000000002', Crypt::decryptString($resident->getRawOriginal('national_id')));
        $this->assertSame('Keyakinan Uji', Crypt::decryptString($resident->getRawOriginal('religion')));
        $household = $resident->household;
        $this->assertNull($household->getRawOriginal('address'));
        $this->assertSame(['address' => 'Alamat Uji'], json_decode(Crypt::decryptString($household->getRawOriginal('private_data')), true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame('0000000000000001', Crypt::decryptString($household->getRawOriginal('family_card_number')));
        $this->assertDatabaseCount('stored_files', 0);
        $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row(['hubungan_keluarga' => 'Anak'])])])
            ->assertOk()->assertDontSee('name="import_token"', false);
        $this->assertDatabaseCount('residents', 1);
        foreach (['local', 'public', 'b2'] as $disk) {
            $this->assertSame([], Storage::disk($disk)->allFiles());
        }
    }

    public function test_invalid_rows_and_other_rt_are_rejected_without_partial_writes(): void
    {
        $this->actingAs($this->staff());
        $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row(), $this->row(['kode_rt' => '99', 'nik' => '0000000000000003', 'hubungan_keluarga' => 'Anak'])])])
            ->assertOk()->assertSee('Kode RT tidak tersedia')->assertDontSee('name="import_token"', false);
        $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row(), $this->row(['hubungan_keluarga' => 'Anak'])])])
            ->assertOk()->assertSee('NIK berulang')->assertDontSee('name="import_token"', false);
        $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row(['hubungan_keluarga' => 'Anak'])])])
            ->assertOk()->assertSee('tepat satu Kepala Keluarga');
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('households', 0);
    }

    public function test_preview_tokens_are_bound_to_actor_expire_and_cannot_be_tampered_with(): void
    {
        $operator = $this->staff();
        $response = $this->actingAs($operator)->post(route('residents.import.preview'), ['file' => $this->upload([$this->row()])])->assertOk();
        $token = $this->token($response);
        $this->post(route('residents.import.store'), ['import_token' => 'invalid'.$token])->assertSessionHasErrors('import');
        $payload = json_decode(Crypt::decryptString($token), true);
        $payload['expires'] = now()->timestamp - 1;
        $this->post(route('residents.import.store'), ['import_token' => Crypt::encryptString(json_encode($payload))])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_data_changed_after_preview_is_revalidated_on_confirmation(): void
    {
        $this->actingAs($this->staff());
        $response = $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row()])])->assertOk();
        $token = $this->token($response);
        $rt = RtArea::query()->firstOrFail();
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-UJI', 'family_card_number' => '0000000000000001', 'family_card_digest' => app(IdentityDigest::class)->make('kk', '0000000000000001')]);
        $household->residents()->create(['full_name' => 'Kepala yang sudah ada', 'relationship' => 'Kepala Keluarga']);
        $this->post(route('residents.import.store'), ['import_token' => $token])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('residents', 1);
    }

    public function test_write_failure_rolls_back_whole_import(): void
    {
        $this->actingAs($this->staff());
        $response = $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row()])])->assertOk();
        $token = $this->token($response);
        Resident::creating(fn () => throw new RuntimeException('Synthetic import write failure'));
        try {
            $this->withoutExceptionHandling()->post(route('residents.import.store'), ['import_token' => $token]);
            $this->fail('Expected synthetic failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic import write failure', $exception->getMessage());
        } finally {
            Resident::flushEventListeners();
        }
        $this->assertDatabaseCount('households', 0);
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_parser_rejects_formula_numeric_identity_external_links_macro_and_dtd(): void
    {
        foreach (['formula', 'numeric', 'external', 'macro', 'dtd', 'bomb'] as $variant) {
            $path = app(ResidentWorkbook::class)->create([$this->row()]);
            $zip = new ZipArchive;
            $zip->open($path);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($variant === 'formula') {
                $sheet = str_replace('<is><t xml:space="preserve">0000000000000002</t></is>', '<f>1+1</f><v>2</v>', $sheet);
            } elseif ($variant === 'numeric') {
                $sheet = preg_replace('/<c r="F2"[^>]*>.*?<\/c>/', '<c r="F2" t="n"><v>2</v></c>', $sheet);
            } elseif ($variant === 'external') {
                $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="x" Target="https://example.invalid/" TargetMode="External"/></Relationships>');
            } elseif ($variant === 'macro') {
                $zip->addFromString('xl/vbaProject.bin', 'not executable');
            } elseif ($variant === 'dtd') {
                $sheet = str_replace('?><worksheet', '?><!DOCTYPE worksheet [<!ENTITY sample "blocked">]><worksheet', $sheet);
            } else {
                $zip->addFromString('xl/padding.xml', str_repeat('A', 100000));
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
            $zip->close();
            try {
                app(ResidentWorkbook::class)->read($path);
                $this->fail('Unsafe workbook was accepted: '.$variant);
            } catch (RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            } finally {
                unlink($path);
            }
        }
    }

    public function test_import_requires_encryption_and_rejects_row_limit(): void
    {
        $this->actingAs($this->staff());
        config(['personal_data.encryption_enabled' => false]);
        $this->post(route('residents.import.preview'), ['file' => $this->upload([$this->row()])])->assertSessionHasErrors('file');
        $path = app(ResidentWorkbook::class)->create(array_fill(0, 201, $this->row()));
        try {
            $this->expectException(RuntimeException::class);
            app(ResidentWorkbook::class)->read($path);
        } finally {
            unlink($path);
        }
    }
}
