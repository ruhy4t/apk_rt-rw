<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_rw_manager_can_only_see_and_manage_subordinates_in_own_rw(): void
    {
        $rw = RwArea::query()->create(['code' => 'RW1', 'name' => 'RW Satu']);
        $otherRw = RwArea::query()->create(['code' => 'RW2', 'name' => 'RW Dua']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT1', 'name' => 'RT Satu']);
        $otherRt = RtArea::query()->create(['rw_area_id' => $otherRw->id, 'code' => 'RT2', 'name' => 'RT Dua']);
        $manager = User::factory()->create(['role' => 'rw_operator', 'rw_area_id' => $rw->id]);
        $own = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id, 'name' => 'Petugas Wilayah Sendiri']);
        $other = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $otherRt->id, 'name' => 'Petugas Wilayah Lain']);
        $peer = User::factory()->create(['role' => 'rw_operator', 'rw_area_id' => $rw->id]);
        $resident = $this->residentIn($rt, 'Warga Sendiri');
        $otherResident = $this->residentIn($otherRt, 'Warga Lain');

        $this->actingAs($manager)->get(route('users.index'))->assertOk()
            ->assertSee($own->name)->assertSee($resident->name)
            ->assertDontSee($other->name)->assertDontSee($otherResident->name);
        $this->patch(route('users.update', $own), ['name' => 'Nama Baru'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $own->id, 'name' => 'Nama Baru']);
        foreach ([$other, $otherResident, $peer, $manager] as $target) {
            $this->patch(route('users.update', $target), ['name' => 'Dilarang'])->assertForbidden();
            $this->patch(route('users.password', $target), ['password' => 'password-sementara', 'password_confirmation' => 'password-sementara'])->assertForbidden();
            $this->patch(route('users.status', $target), ['is_active' => false])->assertForbidden();
        }
    }

    public function test_rt_manager_can_create_only_residents_in_own_rt_and_never_operators(): void
    {
        $rw = RwArea::query()->create(['code' => 'RW1', 'name' => 'RW Satu']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT1', 'name' => 'RT Satu']);
        $otherRt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT2', 'name' => 'RT Dua']);
        $manager = User::factory()->create(['role' => 'rt_operator', 'rt_area_id' => $rt->id]);
        $ownResident = $this->unlinkedResidentIn($rt);
        $otherResident = $this->unlinkedResidentIn($otherRt);
        $values = ['name' => 'Akun Baru', 'username' => 'akun_baru', 'role' => 'resident', 'password' => 'password-sementara', 'password_confirmation' => 'password-sementara'];

        $this->actingAs($manager)->post(route('users.store'), $values + ['resident_id' => $otherResident->id])->assertForbidden();
        $this->post(route('users.store'), array_merge($values, ['role' => 'rt_operator', 'rt_area_id' => $rt->id]))->assertForbidden();
        $this->post(route('users.store'), $values + ['resident_id' => $ownResident->id])->assertRedirect()->assertSessionHasNoErrors();
        $created = User::query()->where('username', 'akun_baru')->firstOrFail();
        $this->assertTrue($created->must_change_password);
        $this->assertSame($created->id, $ownResident->fresh()->user_id);
        $this->get(route('admin.setup'))->assertForbidden();
    }

    public function test_rw_manager_can_create_rt_operator_only_in_own_rw(): void
    {
        $rw = RwArea::query()->create(['code' => 'RW1', 'name' => 'RW Satu']);
        $otherRw = RwArea::query()->create(['code' => 'RW2', 'name' => 'RW Dua']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT1', 'name' => 'RT Satu']);
        $otherRt = RtArea::query()->create(['rw_area_id' => $otherRw->id, 'code' => 'RT2', 'name' => 'RT Dua']);
        $manager = User::factory()->create(['role' => 'rw_operator', 'rw_area_id' => $rw->id]);
        $values = ['name' => 'Petugas Baru', 'username' => 'petugas_baru', 'role' => 'rt_operator', 'password' => 'password-sementara', 'password_confirmation' => 'password-sementara'];

        $this->actingAs($manager)->post(route('users.store'), $values + ['rt_area_id' => $otherRt->id])->assertForbidden();
        $this->post(route('users.store'), array_merge($values, ['role' => 'rw_operator', 'rw_area_id' => $rw->id]))->assertForbidden();
        $this->post(route('users.store'), $values + ['rt_area_id' => $rt->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'petugas_baru', 'rt_area_id' => $rt->id, 'must_change_password' => true]);
    }

    public function test_reset_revokes_only_target_sessions_and_requires_password_change(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'rw_operator', 'password' => 'old-password-123']);
        $other = User::factory()->create(['role' => 'resident']);
        foreach (['target-session' => $target->id, 'other-session' => $other->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }

        $this->actingAs($admin)->patch(route('users.password', $target), [
            'password' => 'new-temporary-123', 'password_confirmation' => 'new-temporary-123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertTrue(Hash::check('new-temporary-123', $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
    }

    public function test_temporary_password_restricts_access_until_changed_with_current_password(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['username' => 'admin_test', 'role' => 'super_admin', 'password' => 'temporary-pass-123', 'must_change_password' => true]);
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'temporary-pass-123'])->assertRedirect();
        $this->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->patch(route('profile.update'), ['name' => 'Dilarang'])->assertRedirect(route('profile.edit'));
        $this->patch(route('profile.password'), [
            'current_password' => 'salah', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');
        $this->patch(route('profile.password'), [
            'current_password' => 'temporary-pass-123', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_resident_cannot_manage_users_and_can_update_own_name(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $this->actingAs($resident)->get(route('users.index'))->assertForbidden();
        $this->patch(route('profile.update'), ['name' => 'Nama Sendiri'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'name' => 'Nama Sendiri']);
    }

    public function test_unlinked_residents_are_available_on_later_pages(): void
    {
        $rw = RwArea::query()->create(['code' => 'RW1', 'name' => 'RW Satu']);
        $rt = RtArea::query()->create(['rw_area_id' => $rw->id, 'code' => 'RT1', 'name' => 'RT Satu']);
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-1']);
        foreach (range(1, 51) as $number) {
            Resident::query()->create(['household_id' => $household->id, 'full_name' => sprintf('Warga %02d', $number)]);
        }
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('users.index'))->assertSee('Warga 01')->assertDontSee('Warga 51');
        $this->get(route('users.index', ['resident_page' => 2]))->assertSee('Warga 51')->assertDontSee('Warga 01');
    }

    public function test_session_with_stale_password_fingerprint_is_logged_out(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user)->withSession(['auth.password_fingerprint' => hash('sha256', 'old-password-hash')])
            ->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivating_user_revokes_their_sessions(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'resident']);
        DB::table('sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->patch(route('users.status', $target), ['is_active' => false])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
    }

    private function unlinkedResidentIn(RtArea $rt): Resident
    {
        $household = Household::query()->create(['rt_area_id' => $rt->id, 'household_code' => 'KEL-'.$rt->id]);

        return Resident::query()->create(['household_id' => $household->id, 'full_name' => 'Warga Contoh']);
    }

    private function residentIn(RtArea $rt, string $name): User
    {
        $user = User::factory()->create(['role' => 'resident', 'name' => $name]);
        $this->unlinkedResidentIn($rt)->update(['user_id' => $user->id]);

        return $user;
    }
}
