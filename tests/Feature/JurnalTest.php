<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JurnalTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Perusahaan profil
        DB::table('perusahaan')->insert([
            'id' => 1,
            'nama_perusahaan' => 'PT Test Jurnal',
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

        // Setup Accounts (Coa)
        Akun::create(['kode_akun' => '1-1001', 'nama_akun' => 'Kas & Bank', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit']);
        Akun::create(['kode_akun' => '3-1001', 'nama_akun' => 'Modal Pemilik', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '4-1001', 'nama_akun' => 'Pendapatan Jasa', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '5-1001', 'nama_akun' => 'Beban Operasional', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit']);
    }

    public function test_user_can_access_edit_form()
    {
        $j = Jurnal::create([
            'no_transaksi' => 'JU-001',
            'tanggal' => '2026-06-01',
            'deskripsi' => 'Transaksi Jurnal Awal',
            'sumber_jurnal' => 'Manual',
            'is_locked' => 0
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('jurnal.edit', $j->id_jurnal));
        $response->assertStatus(200);
        $response->assertViewHas('jurnal');
    }

    public function test_user_can_update_jurnal()
    {
        $j = Jurnal::create([
            'no_transaksi' => 'JU-001',
            'tanggal' => '2026-06-01',
            'deskripsi' => 'Transaksi Jurnal Awal',
            'sumber_jurnal' => 'Manual',
            'is_locked' => 0
        ]);

        JurnalDetail::create(['id_jurnal' => $j->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 1000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j->id_jurnal, 'kode_akun' => '3-1001', 'debit' => 0, 'kredit' => 1000000]);

        $response = $this->actingAs($this->adminUser)->put(route('jurnal.update', $j->id_jurnal), [
            'no_transaksi' => 'JU-001-REV',
            'tanggal' => '2026-06-02',
            'deskripsi' => 'Transaksi Jurnal Direvisi',
            'details' => [
                ['kode_akun' => '1-1001', 'debit' => 2000000, 'kredit' => 0],
                ['kode_akun' => '3-1001', 'debit' => 0, 'kredit' => 2000000],
            ]
        ]);

        $response->assertRedirect(route('jurnal.index'));
        
        $this->assertDatabaseHas('jurnal_umum', [
            'id_jurnal' => $j->id_jurnal,
            'no_transaksi' => 'JU-001-REV',
            'deskripsi' => 'Transaksi Jurnal Direvisi',
        ]);

        $this->assertEquals(2000000, JurnalDetail::where('id_jurnal', $j->id_jurnal)->where('kode_akun', '1-1001')->first()->debit);
    }

    public function test_user_can_delete_jurnal()
    {
        $j = Jurnal::create([
            'no_transaksi' => 'JU-001',
            'tanggal' => '2026-06-01',
            'deskripsi' => 'Transaksi Jurnal Awal',
            'sumber_jurnal' => 'Manual',
            'is_locked' => 0
        ]);

        JurnalDetail::create(['id_jurnal' => $j->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 1000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j->id_jurnal, 'kode_akun' => '3-1001', 'debit' => 0, 'kredit' => 1000000]);

        $response = $this->actingAs($this->adminUser)->delete(route('jurnal.destroy', $j->id_jurnal));
        $response->assertRedirect(route('jurnal.index'));

        $this->assertDatabaseMissing('jurnal_umum', ['id_jurnal' => $j->id_jurnal]);
        $this->assertDatabaseMissing('jurnal_detail', ['id_jurnal' => $j->id_jurnal]);
    }
}
