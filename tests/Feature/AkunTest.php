<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Perusahaan profil
        DB::table('perusahaan')->insert([
            'id' => 1,
            'nama_perusahaan' => 'PT Test Akun',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Admin User
        $this->adminUser = User::create([
            'nama_user' => 'admin_user',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'jabatan' => 'Admin Keuangan',
        ]);
    }

    public function test_user_can_access_akun_index()
    {
        Akun::create([
            'kode_akun' => '1-1001',
            'nama_akun' => 'Kas Jurnal',
            'tipe_akun' => 'Kas & Bank',
            'saldo_normal' => 'Debit',
            'saldo_awal' => 5000000
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('akun.index'));
        $response->assertStatus(200);
        $response->assertSee('1-1001');
        $response->assertSee('Kas Jurnal');
        $response->assertSee('Rp 5.000.000,00');
    }

    public function test_user_can_create_akun_with_saldo_awal()
    {
        $response = $this->actingAs($this->adminUser)->post(route('akun.store'), [
            'kode_akun' => '1-1002',
            'nama_akun' => 'Bank ABC',
            'tipe_akun' => 'Kas & Bank',
            'saldo_normal' => 'Debit',
            'saldo_awal' => 15000000
        ]);

        $response->assertRedirect(route('akun.index'));
        $this->assertDatabaseHas('akun', [
            'kode_akun' => '1-1002',
            'nama_akun' => 'Bank ABC',
            'saldo_awal' => 15000000
        ]);
    }

    public function test_user_can_update_akun_saldo_awal()
    {
        $akun = Akun::create([
            'kode_akun' => '1-1003',
            'nama_akun' => 'Piutang Dagang',
            'tipe_akun' => 'Piutang',
            'saldo_normal' => 'Debit',
            'saldo_awal' => 0
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('akun.update', $akun->kode_akun), [
            'nama_akun' => 'Piutang Dagang Baru',
            'tipe_akun' => 'Piutang',
            'saldo_normal' => 'Debit',
            'saldo_awal' => 3500000
        ]);

        $response->assertRedirect(route('akun.index'));
        $this->assertDatabaseHas('akun', [
            'kode_akun' => '1-1003',
            'nama_akun' => 'Piutang Dagang Baru',
            'saldo_awal' => 3500000
        ]);
    }
}
