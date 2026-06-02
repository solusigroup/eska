<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\AsetTetap;
use App\Models\DepresiasiHistory;
use App\Models\Jurnal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsetTetapTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Perusahaan profil
        DB::table('perusahaan')->insert([
            'id' => 1,
            'nama_perusahaan' => 'PT Test Aset Tetap',
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
        Akun::create(['kode_akun' => '1-1200', 'nama_akun' => 'Kendaraan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit']);
        Akun::create(['kode_akun' => '1-1201', 'nama_akun' => 'Akm Depresiasi Kendaraan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '5-5200', 'nama_akun' => 'Beban Depresiasi Kendaraan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit']);
    }

    public function test_create_and_store_fixed_asset()
    {
        $response = $this->actingAs($this->adminUser)->post(route('aset-tetap.store'), [
            'kode_aset' => 'AT-VEH-01',
            'nama_aset' => 'Mobil Box',
            'tanggal_perolehan' => '2026-01-01',
            'harga_perolehan' => 120000000,
            'nilai_residu' => 24000000,
            'umur_ekonomis' => 48,
            'kode_akun_aset' => '1-1200',
            'kode_akun_akumulasi' => '1-1201',
            'kode_akun_beban' => '5-5200',
        ]);

        $response->assertRedirect(route('aset-tetap.index'));
        $this->assertDatabaseHas('master_aset_tetap', [
            'kode_aset' => 'AT-VEH-01',
            'nama_aset' => 'Mobil Box',
        ]);
    }

    public function test_depresiasi_process_and_journal_generation()
    {
        // 1. Buat aset tetap
        $asset = AsetTetap::create([
            'kode_aset' => 'AT-VEH-01',
            'nama_aset' => 'Mobil Box',
            'tanggal_perolehan' => '2026-01-01',
            'harga_perolehan' => 120000000,
            'nilai_residu' => 24000000,
            'umur_ekonomis' => 48,
            'kode_akun_aset' => '1-1200',
            'kode_akun_akumulasi' => '1-1201',
            'kode_akun_beban' => '5-5200',
            'status' => 'Aktif'
        ]);

        // Depresiasi bulanan: (120M - 24M) / 48 bulan = 96M / 48 = 2,000,000 per bulan

        // 2. Jalankan depresiasi untuk periode Januari 2026
        $response = $this->actingAs($this->adminUser)->post(route('aset-tetap.depresiasi.proses'), [
            'periode' => '2026-01'
        ]);

        $response->assertRedirect(route('aset-tetap.depresiasi'));
        
        // Memastikan riwayat depresiasi tercatat
        $this->assertDatabaseHas('depresiasi_history', [
            'id_aset' => $asset->id,
            'periode' => '2026-01',
            'jumlah_depresiasi' => 2000000,
        ]);

        // Memastikan jurnal otomatis terbentuk
        $history = DepresiasiHistory::where('id_aset', $asset->id)->first();
        $this->assertNotNull($history->id_jurnal);

        $jurnal = Jurnal::with('details')->find($history->id_jurnal);
        $this->assertNotNull($jurnal);
        $this->assertEquals('2026-01-31', $jurnal->tanggal->format('Y-m-d')); // Akhir bulan
        
        // Cek detail jurnal:
        // Debit Beban Penyusutan (5-5200) = 2,000,000
        // Kredit Akumulasi Penyusutan (1-1201) = 2,000,000
        $debitDetail = $jurnal->details->where('kode_akun', '5-5200')->first();
        $kreditDetail = $jurnal->details->where('kode_akun', '1-1201')->first();

        $this->assertEquals(2000000, $debitDetail->debit);
        $this->assertEquals(0, $debitDetail->kredit);
        
        $this->assertEquals(0, $kreditDetail->debit);
        $this->assertEquals(2000000, $kreditDetail->kredit);
    }

    public function test_cancellation_of_depresiasi()
    {
        $asset = AsetTetap::create([
            'kode_aset' => 'AT-VEH-01',
            'nama_aset' => 'Mobil Box',
            'tanggal_perolehan' => '2026-01-01',
            'harga_perolehan' => 120000000,
            'nilai_residu' => 24000000,
            'umur_ekonomis' => 48,
            'kode_akun_aset' => '1-1200',
            'kode_akun_akumulasi' => '1-1201',
            'kode_akun_beban' => '5-5200',
            'status' => 'Aktif'
        ]);

        // Proses
        $this->actingAs($this->adminUser)->post(route('aset-tetap.depresiasi.proses'), [
            'periode' => '2026-01'
        ]);

        $history = DepresiasiHistory::first();
        $jurnalId = $history->id_jurnal;

        // Batalkan
        $response = $this->actingAs($this->adminUser)->delete(route('aset-tetap.depresiasi.destroy', $history->id));
        $response->assertRedirect(route('aset-tetap.depresiasi'));

        // Cek bahwa riwayat dan jurnal sudah terhapus
        $this->assertDatabaseMissing('depresiasi_history', ['id' => $history->id]);
        $this->assertDatabaseMissing('jurnal_umum', ['id_jurnal' => $jurnalId]);
        $this->assertDatabaseMissing('jurnal_detail', ['id_jurnal' => $jurnalId]);
    }
}
