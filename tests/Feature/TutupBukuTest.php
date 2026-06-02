<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\TutupBuku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TutupBukuTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Perusahaan profil
        DB::table('perusahaan')->insert([
            'id' => 1,
            'nama_perusahaan' => 'PT Test Tutup Buku',
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
        Akun::create(['kode_akun' => '3-30000', 'nama_akun' => 'Laba Ditahan', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '4-1001', 'nama_akun' => 'Pendapatan Jasa', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit']);
        Akun::create(['kode_akun' => '5-1001', 'nama_akun' => 'Beban Operasional', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit']);
    }

    public function test_close_period_generates_closing_journal()
    {
        // 1. Catat transaksi Pendapatan & Beban sebelum tutup buku
        // Pendapatan Jasa: Kredit 5,000,000
        $j1 = Jurnal::create(['no_transaksi' => 'JU-001', 'tanggal' => '2026-06-15', 'sumber_jurnal' => 'Jurnal Umum']);
        JurnalDetail::create(['id_jurnal' => $j1->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 5000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j1->id_jurnal, 'kode_akun' => '4-1001', 'debit' => 0, 'kredit' => 5000000]);

        // Beban Operasional: Debit 2,000,000
        $j2 = Jurnal::create(['no_transaksi' => 'JU-002', 'tanggal' => '2026-06-20', 'sumber_jurnal' => 'Jurnal Umum']);
        JurnalDetail::create(['id_jurnal' => $j2->id_jurnal, 'kode_akun' => '5-1001', 'debit' => 2000000, 'kredit' => 0]);
        JurnalDetail::create(['id_jurnal' => $j2->id_jurnal, 'kode_akun' => '1-1001', 'debit' => 0, 'kredit' => 2000000]);

        // Laba bersih s.d. tanggal tutup buku: 5M - 2M = 3,000,000

        // 2. Jalankan Tutup Buku per 30 Juni 2026
        $response = $this->actingAs($this->adminUser)->post(route('tutup-buku.store'), [
            'tanggal_tutup' => '2026-06-30',
            'keterangan' => 'Tutup Buku Juni 2026'
        ]);

        $response->assertRedirect(route('tutup-buku.index'));

        // Cek log Tutup Buku tercatat
        $this->assertDatabaseHas('tutup_buku', [
            'tanggal_tutup' => '2026-06-30 00:00:00',
            'keterangan' => 'Tutup Buku Juni 2026',
        ]);

        $log = TutupBuku::first();
        $this->assertNotNull($log->id_jurnal_penutup);

        // Cek Jurnal Penutup:
        // - Harus menihilkan Pendapatan (Debit Pendapatan Jasa 5,000,000)
        // - Harus menihilkan Beban (Kredit Beban Operasional 2,000,000)
        // - Harus mengalihkan Laba Bersih ke Laba Ditahan (Kredit Laba Ditahan 3,000,000)
        $jurnalPenutup = Jurnal::with('details')->find($log->id_jurnal_penutup);
        $this->assertNotNull($jurnalPenutup);
        $this->assertEquals('2026-06-30', $jurnalPenutup->tanggal->format('Y-m-d'));

        $detailPendapatan = $jurnalPenutup->details->where('kode_akun', '4-1001')->first();
        $detailBeban = $jurnalPenutup->details->where('kode_akun', '5-1001')->first();
        $detailLabaDitahan = $jurnalPenutup->details->where('kode_akun', '3-30000')->first();

        // Pendapatan didebit 5,000,000 untuk menolkan saldo kreditnya
        $this->assertEquals(5000000, $detailPendapatan->debit);
        $this->assertEquals(0, $detailPendapatan->kredit);

        // Beban dikredit 2,000,000 untuk menolkan saldo debitnya
        $this->assertEquals(0, $detailBeban->debit);
        $this->assertEquals(2000000, $detailBeban->kredit);

        // Laba bersih dikredit ke Laba Ditahan 3,000,000
        $this->assertEquals(0, $detailLabaDitahan->debit);
        $this->assertEquals(3000000, $detailLabaDitahan->kredit);
    }

    public function test_transactions_are_blocked_on_locked_dates()
    {
        // 1. Buat Tutup Buku per 30 Juni 2026
        TutupBuku::create([
            'tanggal_tutup' => '2026-06-30',
            'id_jurnal_penutup' => null,
            'user_id' => $this->adminUser->id_user,
            'keterangan' => 'Kunci Periode Juni'
        ]);

        // 2. Coba buat transaksi baru dengan tanggal 25 Juni 2026 (sebelum/pada tanggal tutup)
        // Harus memicu HttpResponseException atau kembali dengan error
        $response = $this->actingAs($this->adminUser)->post(route('jurnal.store'), [
            'tanggal' => '2026-06-25',
            'no_transaksi' => 'JU-BLOCKED',
            'deskripsi' => 'Transaksi Terlarang',
            'details' => [
                ['kode_akun' => '1-1001', 'debit' => 1000, 'kredit' => 0],
                ['kode_akun' => '3-30000', 'debit' => 0, 'kredit' => 1000]
            ]
        ]);

        // Trait check melempar 403 Forbidden
        $response->assertStatus(403);
        
        // Memastikan transaksi tersebut TIDAK tersimpan di database
        $this->assertDatabaseMissing('jurnal_umum', [
            'no_transaksi' => 'JU-BLOCKED'
        ]);
    }

    public function test_reopen_period_releases_lock()
    {
        // 1. Buat Tutup Buku per 30 Juni 2026
        $jurnal = Jurnal::create(['no_transaksi' => 'CL-20260630', 'tanggal' => '2026-06-30', 'sumber_jurnal' => 'Jurnal Penutup', 'is_locked' => 1]);
        $close = TutupBuku::create([
            'tanggal_tutup' => '2026-06-30',
            'id_jurnal_penutup' => $jurnal->id_jurnal,
            'user_id' => $this->adminUser->id_user,
            'keterangan' => 'Kunci Periode Juni'
        ]);

        // 2. Batalkan Tutup Buku
        $response = $this->actingAs($this->adminUser)->delete(route('tutup-buku.destroy', $close->id));
        $response->assertRedirect(route('tutup-buku.index'));

        // Cek log dan jurnal sudah terhapus
        $this->assertDatabaseMissing('tutup_buku', ['id' => $close->id]);
        $this->assertDatabaseMissing('jurnal_umum', ['id_jurnal' => $jurnal->id_jurnal]);
        
        // 3. Sekarang coba tambahkan jurnal di tanggal 25 Juni 2026 (sebelumnya terblokir)
        // Harus sukses!
        $response2 = $this->actingAs($this->adminUser)->post(route('jurnal.store'), [
            'tanggal' => '2026-06-25',
            'no_transaksi' => 'JU-ALLOWED',
            'deskripsi' => 'Transaksi Boleh',
            'is_locked' => 0,
            'sumber_jurnal' => 'Jurnal Umum',
            'details' => [
                [
                    'kode_akun' => '1-1001',
                    'debit' => 500000,
                    'kredit' => 0
                ],
                [
                    'kode_akun' => '3-30000',
                    'debit' => 0,
                    'kredit' => 500000
                ]
            ]
        ]);

        // Transaksi diperbolehkan
        $this->assertDatabaseHas('jurnal_umum', [
            'no_transaksi' => 'JU-ALLOWED'
        ]);
    }
}
