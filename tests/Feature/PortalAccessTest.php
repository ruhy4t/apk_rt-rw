<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_shows_only_published_public_announcements(): void
    {
        $author = User::factory()->create(['role' => 'super_admin']);

        Announcement::query()->create([
            'created_by' => $author->id,
            'title' => 'Informasi umum',
            'body' => 'Isi pengumuman publik',
            'audience' => 'public',
            'is_published' => true,
        ]);

        Announcement::query()->create([
            'created_by' => $author->id,
            'title' => 'Informasi warga',
            'body' => 'Isi privat yang tidak boleh tampil',
            'audience' => 'residents',
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Informasi umum')
            ->assertDontSee('Informasi warga')
            ->assertDontSee('Isi privat yang tidak boleh tampil');
    }

    public function test_active_user_can_login_and_reach_private_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'warga01',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $this->post('/login', [
            'username' => 'warga01',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'nonaktif',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'nonaktif',
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }

    public function test_resident_dashboard_only_discloses_their_household_code(): void
    {
        $rw = RwArea::query()->create(['code' => 'RW-01', 'name' => 'RW 01']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT-01', 'name' => 'RT 01']);
        $ownHousehold = Household::query()->create([
            'rt_area_id' => $rt->id,
            'household_code' => 'KEL-OWN',
        ]);
        $otherHousehold = Household::query()->create([
            'rt_area_id' => $rt->id,
            'household_code' => 'KEL-OTHER',
        ]);
        $user = User::factory()->create(['role' => 'resident']);
        Resident::query()->create([
            'household_id' => $ownHousehold->id,
            'user_id' => $user->id,
            'full_name' => 'Warga Contoh',
        ]);
        Resident::query()->create([
            'household_id' => $otherHousehold->id,
            'full_name' => 'Warga Lain',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('KEL-OWN')
            ->assertDontSee('KEL-OTHER')
            ->assertDontSee('Warga Lain');
    }
}
