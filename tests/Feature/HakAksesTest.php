<?php

namespace Tests\Feature;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HakAksesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/panel')->assertRedirect(route('masuk'));
        $this->get(route('dataset.create'))->assertRedirect(route('masuk'));
    }

    public function test_operator_tidak_bisa_melihat_atau_mengubah_dataset_opd_lain(): void
    {
        $pupr = Opd::factory()->create();
        $dinkes = Opd::factory()->create();
        $operatorPupr = User::factory()->operator($pupr)->create();
        $datasetDinkes = Dataset::factory()->for($dinkes)->create();

        $this->actingAs($operatorPupr)->get(route('dataset.show', $datasetDinkes))->assertForbidden();
        $this->actingAs($operatorPupr)->get(route('dataset.edit', $datasetDinkes))->assertForbidden();
        $this->actingAs($operatorPupr)->post(route('dataset.ajukan', $datasetDinkes))->assertForbidden();
        $this->actingAs($operatorPupr)->delete(route('dataset.destroy', $datasetDinkes))->assertForbidden();
        $this->assertModelExists($datasetDinkes);
    }

    public function test_daftar_operator_hanya_berisi_dataset_opd_sendiri(): void
    {
        $pupr = Opd::factory()->create();
        $operator = User::factory()->operator($pupr)->create();
        Dataset::factory()->for($pupr)->create(['judul' => 'Data milik PUPR']);
        Dataset::factory()->create(['judul' => 'Data milik OPD lain']);

        $this->actingAs($operator)->get(route('dataset.index'))
            ->assertOk()
            ->assertSee('Data milik PUPR')
            ->assertDontSee('Data milik OPD lain');
    }

    public function test_operator_tidak_bisa_mengubah_dataset_yang_sedang_diperiksa(): void
    {
        $opd = Opd::factory()->create();
        $operator = User::factory()->operator($opd)->create();
        $dataset = Dataset::factory()->for($opd)->status(StatusDataset::Diajukan)->create();

        $this->actingAs($operator)->get(route('dataset.edit', $dataset))->assertForbidden();
    }

    public function test_pembagian_halaman_per_peran(): void
    {
        $operator = User::factory()->operator()->create();
        $walidata = User::factory()->walidata()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($operator)->get(route('pemeriksaan.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($walidata)->get(route('dataset.create'))->assertForbidden();
        $this->actingAs($walidata)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pemeriksaan.index'))->assertForbidden();

        $this->actingAs($operator)->get('/panel')->assertRedirect(route('dataset.index'));
        $this->actingAs($walidata)->get('/panel')->assertRedirect(route('pemeriksaan.index'));
        $this->actingAs($admin)->get('/panel')->assertRedirect(route('pengguna.index'));
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_dan_dikeluarkan_dari_sesi(): void
    {
        $user = User::factory()->operator()->nonaktif()->create(['email' => 'mati@contoh.test']);

        $this->post(route('masuk'), ['email' => 'mati@contoh.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('dataset.index'))->assertRedirect(route('masuk'));
        $this->assertGuest();
    }

    public function test_login_berhasil(): void
    {
        User::factory()->walidata()->create(['email' => 'wd@contoh.test']);

        $this->post(route('masuk'), ['email' => 'wd@contoh.test', 'password' => 'password'])->assertRedirect(route('panel'));
        $this->assertAuthenticated();
    }

    public function test_admin_tidak_bisa_menonaktifkan_diri_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('pengguna.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'aktif' => '0',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->aktif);
    }

    public function test_admin_membuat_akun_operator(): void
    {
        $admin = User::factory()->admin()->create();
        $opd = Opd::factory()->create();

        $this->actingAs($admin)->post(route('pengguna.store'), [
            'name' => 'Operator Baru', 'email' => 'baru@contoh.test', 'role' => 'operator', 'opd_id' => $opd->id,
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'aktif' => '1',
        ])->assertRedirect(route('pengguna.index'));

        $this->assertDatabaseHas('users', ['email' => 'baru@contoh.test', 'opd_id' => $opd->id, 'role' => 'operator']);
    }
}
