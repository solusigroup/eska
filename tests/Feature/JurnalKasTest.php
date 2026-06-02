<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\JurnalKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurnalKasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required cash and counter accounts
        Akun::create([
            'kode_akun' => '1-1001',
            'nama_akun' => 'Kas Kecil',
            'tipe_akun' => 'Kas & Bank',
            'saldo_normal' => 'Debit',
        ]);

        Akun::create([
            'kode_akun' => '4-1001',
            'nama_akun' => 'Pendapatan Jasa',
            'tipe_akun' => 'Pendapatan',
            'saldo_normal' => 'Kredit',
        ]);

        Akun::create([
            'kode_akun' => '5-1001',
            'nama_akun' => 'Beban Air & Listrik',
            'tipe_akun' => 'Beban',
            'saldo_normal' => 'Debit',
        ]);
    }

    public function test_user_can_create_jurnal_kas_masuk()
    {
        $user = User::create([
            'nama_user' => 'staff_user',
            'password_hash' => bcrypt('password'),
            'role' => 'staff',
            'jabatan' => 'Staff Akuntansi',
        ]);

        $response = $this->actingAs($user)->post(route('jurnal-kas.store'), [
            'no_bukti' => 'KM000001',
            'tanggal' => '2026-06-03',
            'tipe' => 'Masuk',
            'akun_kas' => '1-1001',
            'akun_lawan' => '4-1001',
            'jumlah' => 500000,
            'keterangan' => 'Terima pembayaran jasa',
        ]);

        $response->assertRedirect(route('jurnal-kas.index'));
        $this->assertDatabaseHas('jurnal_kas', [
            'no_bukti' => 'KM000001',
            'jumlah' => 500000,
        ]);

        // Verify automated Jurnal Umum creation
        $jurnalKas = JurnalKas::where('no_bukti', 'KM000001')->first();
        $this->assertNotNull($jurnalKas->id_jurnal);

        $jurnal = Jurnal::find($jurnalKas->id_jurnal);
        $this->assertNotNull($jurnal);
        $this->assertEquals('KM000001', $jurnal->no_transaksi);
        $this->assertEquals('Jurnal Kas', $jurnal->sumber_jurnal);

        // Verify Details: Debit Kas Kecil (1-1001), Kredit Pendapatan (4-1001)
        $this->assertDatabaseHas('jurnal_detail', [
            'id_jurnal' => $jurnal->id_jurnal,
            'kode_akun' => '1-1001',
            'debit' => 500000,
            'kredit' => 0,
        ]);

        $this->assertDatabaseHas('jurnal_detail', [
            'id_jurnal' => $jurnal->id_jurnal,
            'kode_akun' => '4-1001',
            'debit' => 0,
            'kredit' => 500000,
        ]);
    }

    public function test_user_can_update_jurnal_kas()
    {
        $user = User::create([
            'nama_user' => 'staff_user',
            'password_hash' => bcrypt('password'),
            'role' => 'staff',
            'jabatan' => 'Staff Akuntansi',
        ]);

        $jurnalKas = JurnalKas::create([
            'no_bukti' => 'KK000001',
            'tanggal' => '2026-06-03',
            'tipe' => 'Keluar',
            'akun_kas' => '1-1001',
            'akun_lawan' => '5-1001',
            'jumlah' => 200000,
            'keterangan' => 'Bayar listrik',
        ]);

        // Update the amount and description
        $response = $this->actingAs($user)->put(route('jurnal-kas.update', $jurnalKas->id_jurnal_kas), [
            'no_bukti' => 'KK000001',
            'tanggal' => '2026-06-03',
            'tipe' => 'Keluar',
            'akun_kas' => '1-1001',
            'akun_lawan' => '5-1001',
            'jumlah' => 250000, // Updated
            'keterangan' => 'Bayar listrik Juni', // Updated
        ]);

        $response->assertRedirect(route('jurnal-kas.index'));
        $this->assertDatabaseHas('jurnal_kas', [
            'id_jurnal_kas' => $jurnalKas->id_jurnal_kas,
            'jumlah' => 250000,
        ]);

        // Verify automated update of Jurnal Umum
        $jurnalKas->refresh();
        $jurnal = Jurnal::find($jurnalKas->id_jurnal);
        $this->assertEquals('Kas Keluar: Bayar listrik Juni', $jurnal->deskripsi);

        // Verify Details: Debit Beban (5-1001) 250k, Kredit Kas (1-1001) 250k
        $this->assertDatabaseHas('jurnal_detail', [
            'id_jurnal' => $jurnal->id_jurnal,
            'kode_akun' => '5-1001',
            'debit' => 250000,
            'kredit' => 0,
        ]);
        
        $this->assertDatabaseHas('jurnal_detail', [
            'id_jurnal' => $jurnal->id_jurnal,
            'kode_akun' => '1-1001',
            'debit' => 0,
            'kredit' => 250000,
        ]);

        // Verify no duplicate details remaining from old record
        $this->assertEquals(2, JurnalDetail::where('id_jurnal', $jurnal->id_jurnal)->count());
    }

    public function test_user_can_delete_jurnal_kas()
    {
        $user = User::create([
            'nama_user' => 'admin_user',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'jabatan' => 'Administrator',
        ]);

        $jurnalKas = JurnalKas::create([
            'no_bukti' => 'KK000002',
            'tanggal' => '2026-06-03',
            'tipe' => 'Keluar',
            'akun_kas' => '1-1001',
            'akun_lawan' => '5-1001',
            'jumlah' => 150000,
            'keterangan' => 'Bayar air',
        ]);

        $jurnalId = $jurnalKas->id_jurnal;

        $response = $this->actingAs($user)->delete(route('jurnal-kas.destroy', $jurnalKas->id_jurnal_kas));

        $response->assertRedirect(route('jurnal-kas.index'));
        $this->assertDatabaseMissing('jurnal_kas', [
            'id_jurnal_kas' => $jurnalKas->id_jurnal_kas,
        ]);

        // Verify associated Jurnal Umum is deleted as well
        $this->assertDatabaseMissing('jurnal_umum', [
            'id_jurnal' => $jurnalId,
        ]);
        $this->assertDatabaseMissing('jurnal_detail', [
            'id_jurnal' => $jurnalId,
        ]);
    }
}
