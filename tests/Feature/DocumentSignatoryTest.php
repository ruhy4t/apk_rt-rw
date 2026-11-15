<?php

namespace Tests\Feature;

use App\Models\Concerns\HasSignatory;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use App\Services\DocumentSignatory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentSignatoryTest extends TestCase
{
    use RefreshDatabase;

    private function document(): Model
    {
        return new class extends Model
        {
            use HasSignatory;

            protected $table = 'announcements';

            protected $guarded = [];
        };
    }

    private function areas(): array
    {
        $rw = RwArea::query()->create(['code' => '01', 'name' => 'RW Uji']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => '05', 'name' => 'RT Uji']);

        return [$rw, $rt];
    }

    public function test_capture_requires_assigned_chairman_without_mutating_area(): void
    {
        [, $rt] = $this->areas();
        try {
            DB::transaction(fn () => app(DocumentSignatory::class)->capture($this->document(), $rt->id));
            $this->fail('Expected missing chairman validation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Ketua RT belum ditetapkan', $exception->errors()['signatory'][0]);
        }
        $this->assertNull(DB::table('rt_areas')->where('id', $rt->id)->value('chairman_name'));
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_capture_selects_matching_rt_or_the_single_rw(): void
    {
        [$rw, $rt] = $this->areas();
        $rw->forceFill(['chairman_name' => 'Nama Ketua RW Uji'])->save();
        $rt->forceFill(['chairman_name' => 'Nama Ketua RT Uji'])->save();
        $rtSnapshot = DB::transaction(fn () => app(DocumentSignatory::class)->capture($this->document(), $rt->id));
        $rwSnapshot = DB::transaction(fn () => app(DocumentSignatory::class)->capture($this->document(), null));
        $this->assertSame('Nama Ketua RT Uji', $rtSnapshot['name']);
        $this->assertSame('Ketua RT 05', $rtSnapshot['title']);
        $this->assertSame('rt', $rtSnapshot['area_type']);
        $this->assertSame($rt->id, $rtSnapshot['area_id']);
        $this->assertSame('Nama Ketua RW Uji', $rwSnapshot['name']);
        $this->assertSame('Ketua RW 01', $rwSnapshot['title']);
        $this->assertSame('rw', $rwSnapshot['area_type']);
        $this->assertArrayHasKey('issued_at', $rwSnapshot);
        $this->assertNotSame('Nama Ketua RT Uji', DB::table('rt_areas')->value('chairman_name'));
        $this->assertNotSame('Nama Ketua RW Uji', DB::table('rw_areas')->value('chairman_name'));
    }

    public function test_saved_snapshot_is_encrypted_hidden_and_preserved_after_chairman_changes(): void
    {
        [, $rt] = $this->areas();
        $rt->forceFill(['chairman_name' => 'Ketua Lama Uji'])->save();
        $snapshot = DB::transaction(fn () => app(DocumentSignatory::class)->capture($this->document(), $rt->id));
        $document = $this->document();
        $document->fill(['rt_area_id' => $rt->id, 'created_by' => User::factory()->create()->id, 'title' => 'Dokumen Uji', 'body' => 'Isi uji', 'audience' => 'residents', 'signatory_data' => $snapshot])->save();
        $raw = DB::table('announcements')->where('id', $document->id)->value('signatory_data');
        $this->assertStringNotContainsString('Ketua Lama Uji', $raw);
        $this->assertSame($snapshot, json_decode(Crypt::decryptString($raw), true));
        $this->assertArrayNotHasKey('signatory_data', $document->toArray());
        $rt->forceFill(['chairman_name' => 'Ketua Baru Uji'])->save();
        $this->assertSame($snapshot, DB::transaction(fn () => app(DocumentSignatory::class)->capture($document->fresh(), $rt->id)));
    }

    public function test_missing_snapshot_column_blocks_capture_with_clear_validation(): void
    {
        [, $rt] = $this->areas();
        Schema::table('announcements', fn (Blueprint $table) => $table->renameColumn('signatory_data', 'signatory_data_unavailable'));
        try {
            app(DocumentSignatory::class)->capture($this->document(), $rt->id);
            $this->fail('Expected schema validation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('pembaruan struktur database', $exception->errors()['signatory'][0]);
        } finally {
            Schema::table('announcements', fn (Blueprint $table) => $table->renameColumn('signatory_data_unavailable', 'signatory_data'));
        }
    }

    public function test_invalid_existing_snapshot_is_not_silently_replaced(): void
    {
        [, $rt] = $this->areas();
        $rt->forceFill(['chairman_name' => 'Ketua Baru Uji'])->save();
        $document = $this->document();
        $document->setAttribute('signatory_data', []);
        try {
            app(DocumentSignatory::class)->capture($document, $rt->id);
            $this->fail('Expected invalid stored snapshot validation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak diganti otomatis', $exception->errors()['signatory'][0]);
        }
        $this->assertSame([], $document->getAttribute('signatory_data'));
    }
}
