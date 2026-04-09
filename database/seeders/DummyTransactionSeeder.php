<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use App\Models\Pemasok;
use App\Models\Persediaan;
use App\Models\Proyek;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\Akun;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DummyTransactionSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Pastikan master data ada
        if (Pelanggan::count() == 0 || Pemasok::count() == 0 || Persediaan::count() == 0 || Akun::count() == 0) {
            $this->command->error('Data master (Pelanggan, Pemasok, Persediaan, atau Akun) masih kosong. Jalankan db:seed sebelumnya.');
            return;
        }

        $this->command->info('Mulai membuat dummy transaksi...');

        // 1. Buat 3 Proyek
        $proyeks = [];
        for ($i = 1; $i <= 3; $i++) {
            $proyek = Proyek::create([
                'kode_proyek' => 'PRJ-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama_proyek' => 'Proyek Pembangunan ' . $faker->company,
                'deskripsi' => $faker->sentence,
                'status' => $faker->randomElement(['Aktif', 'Selesai']),
                'tanggal_mulai' => Carbon::now()->subMonths(rand(1, 6)),
                'tanggal_selesai' => Carbon::now()->addMonths(rand(1, 6)),
                'anggaran' => $faker->randomElement([50000000, 100000000, 200000000]),
                'lokasi' => $faker->city,
                'pelanggan' => Pelanggan::inRandomOrder()->first()->nama_pelanggan,
            ]);
            $proyeks[] = $proyek->id_proyek;
        }
        $this->command->info('- 3 Proyek dummy berhasil dibuat.');

        // Reference Variables
        $akunKas = Akun::where('tipe_akun', 'Kas')->orWhere('tipe_akun', 'Bank')->first();
        $akunKasKode = $akunKas ? $akunKas->kode_akun : '1100'; // Default fallback bila array kosong

        $akunPersediaan = Akun::where('tipe_akun', 'Persediaan')->first();
        $akunPersediaanKode = $akunPersediaan ? $akunPersediaan->kode_akun : '1400';

        $akunUtang = Akun::where('tipe_akun', 'Utang Usaha')->first();
        $akunUtangKode = $akunUtang ? $akunUtang->kode_akun : '2100';

        $akunPiutang = Akun::where('tipe_akun', 'Piutang Usaha')->first();
        $akunPiutangKode = $akunPiutang ? $akunPiutang->kode_akun : '1300';
        
        $akunPendapatan = Akun::where('tipe_akun', 'Pendapatan')->first();
        $akunPendapatanKode = $akunPendapatan ? $akunPendapatan->kode_akun : '4100';

        $akunHPP = Akun::where('tipe_akun', 'HPP')->first();
        $akunHPPKode = $akunHPP ? $akunHPP->kode_akun : '5100';

        // 2. Buat Transaksi Pembelian (5 transaksi)
        for ($i = 1; $i <= 5; $i++) {
            $pemasok = Pemasok::inRandomOrder()->first();
            $barang = Persediaan::inRandomOrder()->first();
            $qty = $faker->numberBetween(5, 50);
            $harga = $barang->harga_beli;
            $total = $qty * $harga;
            $isLunas = $faker->boolean(70);
            
            // Jurnal Pembelian
            $jurnal = Jurnal::create([
                'no_transaksi' => 'JBL-' . time() . rand(10, 99),
                'tanggal' => Carbon::now()->subDays(rand(1, 30)),
                'deskripsi' => 'Pembelian dari ' . $pemasok->nama_pemasok,
                'sumber_jurnal' => 'Pembelian',
                'id_proyek' => $faker->boolean(50) ? $faker->randomElement($proyeks) : null,
            ]);

            // Jurnal Detail (Debit: Persediaan, Kredit: Utang/Kas)
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunPersediaanKode,
                'debit' => $total,
                'kredit' => 0,
                'id_proyek' => $jurnal->id_proyek
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $isLunas ? $akunKasKode : $akunUtangKode,
                'debit' => 0,
                'kredit' => $total,
                'id_proyek' => $jurnal->id_proyek
            ]);

            $pembelian = Pembelian::create([
                'id_pemasok' => $pemasok->id_pemasok,
                'id_jurnal' => $jurnal->id_jurnal,
                'no_faktur_pembelian' => 'INV-IN-' . date('Ymd') . rand(100, 999),
                'tanggal_faktur' => $jurnal->tanggal,
                'total' => $total,
                'keterangan' => 'Pembelian barang material',
                'metode_pembayaran' => $isLunas ? 'Tunai' : 'Kredit',
                'akun_kas_bank' => $isLunas ? $akunKasKode : null,
                'sisa_tagihan' => $isLunas ? 0 : $total,
                'status_pembayaran' => $isLunas ? 'Lunas' : 'Belum Lunas',
                'id_proyek' => $jurnal->id_proyek
            ]);

            PembelianDetail::create([
                'id_pembelian' => $pembelian->id_pembelian,
                'id_barang' => $barang->id_barang,
                'kuantitas' => $qty,
                'harga' => $harga,
                'subtotal' => $total,
                'akun_beban_persediaan' => $akunPersediaanKode
            ]);
            
            // Tambah stok ke master
            $barang->stok_saat_ini += $qty;
            $barang->save();
        }
        $this->command->info('- 5 Transaksi Pembelian berhasil dibuat.');

        // 3. Buat Transaksi Penjualan (5 transaksi)
        for ($i = 1; $i <= 5; $i++) {
            $pelanggan = Pelanggan::inRandomOrder()->first();
            $barang = Persediaan::where('stok_saat_ini', '>', 5)->inRandomOrder()->first();
            
            if(!$barang) continue;
            
            $qty = rand(1, min(5, $barang->stok_saat_ini)); // Jual maks 5
            $harga = $barang->harga_jual;
            $total = $qty * $harga;
            $isLunas = $faker->boolean(80);
            
            // Jurnal Penjualan
            $jurnal = Jurnal::create([
                'no_transaksi' => 'JJL-' . time() . rand(10, 99),
                'tanggal' => Carbon::now()->subDays(rand(1, 15)),
                'deskripsi' => 'Penjualan ke ' . $pelanggan->nama_pelanggan,
                'sumber_jurnal' => 'Penjualan',
                'id_proyek' => $faker->boolean(50) ? $faker->randomElement($proyeks) : null,
            ]);

            // Jurnal Detail (Debit: Kas/Piutang, Kredit: Pendapatan)
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $isLunas ? $akunKasKode : $akunPiutangKode,
                'debit' => $total,
                'kredit' => 0,
                'id_proyek' => $jurnal->id_proyek
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunPendapatanKode,
                'debit' => 0,
                'kredit' => $total,
                'id_proyek' => $jurnal->id_proyek
            ]);
            
            // Jurnal HPP (Debit: HPP, Kredit: Persediaan)
            $totalHpp = $qty * $barang->harga_beli;
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunHPPKode,
                'debit' => $totalHpp,
                'kredit' => 0,
                'id_proyek' => $jurnal->id_proyek
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunPersediaanKode,
                'debit' => 0,
                'kredit' => $totalHpp,
                'id_proyek' => $jurnal->id_proyek
            ]);

            $penjualan = Penjualan::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_jurnal' => $jurnal->id_jurnal,
                'no_faktur' => 'INV-OUT-' . date('Ymd') . rand(100, 999),
                'tanggal_faktur' => $jurnal->tanggal,
                'total' => $total,
                'keterangan' => 'Penjualan barang material',
                'metode_pembayaran' => $isLunas ? 'Tunai' : 'Kredit',
                'akun_kas_bank' => $isLunas ? $akunKasKode : null,
                'sisa_tagihan' => $isLunas ? 0 : $total,
                'status_pembayaran' => $isLunas ? 'Lunas' : 'Belum Lunas',
                'id_proyek' => $jurnal->id_proyek
            ]);

            PenjualanDetail::create([
                'id_penjualan' => $penjualan->id_penjualan,
                'id_barang' => $barang->id_barang,
                'kuantitas' => $qty,
                'harga' => $harga,
                'subtotal' => $total,
                'akun_pendapatan' => $akunPendapatanKode
            ]);
            
            // Kurangi stok master
            $barang->stok_saat_ini -= $qty;
            $barang->save();
        }
        $this->command->info('- 5 Transaksi Penjualan berhasil dibuat.');

        // 4. Jurnal Umum Manual (3 Beban)
        for ($i = 1; $i <= 3; $i++) {
            $nominal = $faker->randomElement([150000, 250000, 500000]);
            
            $jurnal = Jurnal::create([
                'no_transaksi' => 'JU-' . time() . rand(10, 99),
                'tanggal' => Carbon::now()->subDays(rand(1, 10)),
                'deskripsi' => 'Pembayaran Beban Operasional / Lainnya',
                'sumber_jurnal' => 'Jurnal Umum',
                'id_proyek' => $faker->boolean(40) ? $faker->randomElement($proyeks) : null,
            ]);

            // Misal Debit 6100 Beban, Kredit Kas
            $akunBeban = Akun::where('tipe_akun', 'Beban')->inRandomOrder()->first();
            $akunBebanKode = $akunBeban ? $akunBeban->kode_akun : '6100';

            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunBebanKode,
                'debit' => $nominal,
                'kredit' => 0,
                'id_proyek' => $jurnal->id_proyek
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $akunKasKode,
                'debit' => 0,
                'kredit' => $nominal,
                'id_proyek' => $jurnal->id_proyek
            ]);
        }
        $this->command->info('- 3 Jurnal Umum (Operasional) berhasil dibuat.');

        $this->command->info('Semua dummy data transaksi berhasil dimasukkan!');
    }
}
