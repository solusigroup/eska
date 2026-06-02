<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Perusahaan profil
        DB::table('perusahaan')->insert([
            'id' => 1,
            'nama_perusahaan' => 'PT Test Akuntansi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup Accounts (Coa)
        Akun::create(['kode_akun' => '1-1001', 'nama_akun' => 'Kas & Bank', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit']);
        Akun::create(['kode_akun' => '1-2001', 'nama_akun' => 'Piutang Usaha', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit']);
        Akun::create(['kode_akun' => '3-1001', 'nama_akun' => 'Modal Pemilik', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '4-1001', 'nama_akun' => 'Pendapatan Jasa', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '5-1001', 'nama_akun' => 'Beban Operasional', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit']);

        // Input transaction data
        // 1. Initial Investment: Debit Kas 10,000,000, Kredit Modal 10,000,000
        $j1 = Jurnal::create(['no_transaksi' => 'JU-001', 'tanggal' => '2026-06-01', 'sumber_jurnal' => 'Jurnal Umum']);
        JurnalDetail::create(['id_jurnal' => $j1->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 10000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j1->id_jurnal, 'kode_akun' => '3-1001', 'debit' => 0, 'kredit' => 10000000]);

        // 2. Revenue Transaction: Debit Kas 3,000,000, Kredit Pendapatan 3,000,000
        $j2 = Jurnal::create(['no_transaksi' => 'JU-002', 'tanggal' => '2026-06-02', 'sumber_jurnal' => 'Jurnal Umum']);
        JurnalDetail::create(['id_jurnal' => $j2->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 3000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j2->id_jurnal, 'kode_akun' => '4-1001', 'debit' => 0, 'kredit' => 3000000]);

        // 3. Expense Transaction: Debit Beban 1,000,000, Kredit Kas 1,000,000
        $j3 = Jurnal::create(['no_transaksi' => 'JU-003', 'tanggal' => '2026-06-03', 'sumber_jurnal' => 'Jurnal Umum']);
        JurnalDetail::create(['id_jurnal' => $j3->id_jurnal, 'kode_akun' => '5-1001', 'debit' => 1000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j3->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 0, 'kredit' => 1000000]);
    }

    public function test_laba_rugi_calculation()
    {
        $user = User::create([
            'nama_user' => 'manajer_user',
            'password_hash' => bcrypt('password'),
            'role' => 'manajer',
            'jabatan' => 'Manajer Keuangan',
        ]);

        $response = $this->actingAs($user)->get(route('laporan.labarugi', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30'
        ]));

        $response->assertStatus(200);

        // Revenue: 3,000,000, Expense: 1,000,000. Net Profit should be 2,000,000.
        // The view receives variables: pendapatan, hpp, beban
        $pendapatan = $response->viewData('pendapatan');
        $beban = $response->viewData('beban');

        $this->assertEquals(3000000, $pendapatan->sum('saldo_periode'));
        $this->assertEquals(1000000, $beban->sum('saldo_periode'));
    }

    public function test_neraca_calculation()
    {
        $user = User::create([
            'nama_user' => 'manajer_user',
            'password_hash' => bcrypt('password'),
            'role' => 'manajer',
            'jabatan' => 'Manajer Keuangan',
        ]);

        $response = $this->actingAs($user)->get(route('laporan.neraca', [
            'per_tanggal' => '2026-06-03'
        ]));

        $response->assertStatus(200);

        // Cash & Bank balance: 10M (in) + 3M (in) - 1M (out) = 12M
        $asetLancar = $response->viewData('asetLancar');
        $kasAkun = $asetLancar->where('kode_akun', '1-1001')->first();
        $this->assertEquals(12000000, $kasAkun->saldo_akhir);

        // Modal balance: 10M
        $ekuitas = $response->viewData('ekuitas');
        $modalAkun = $ekuitas->where('kode_akun', '3-1001')->first();
        $this->assertEquals(10000000, $modalAkun->saldo_akhir);

        // Current P&L (Laba berjalan): 2M
        $labaRugiBerjalan = $response->viewData('labaRugiBerjalan');
        $this->assertEquals(2000000, $labaRugiBerjalan);
    }
}
