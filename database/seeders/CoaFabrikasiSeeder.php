<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoaFabrikasiSeeder extends Seeder
{
    /**
     * COA Default untuk Fabrikasi Mesin
     * Standar Akuntansi Indonesia (PSAK 201)
     */
    public function run(): void
    {
        $akun = [
            // ===============================
            // 1. ASET (Asset)
            // ===============================
            // 1.1 Aset Lancar
            ['kode_akun' => '1-1100', 'nama_akun' => 'Kas', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1101', 'nama_akun' => 'Kas Produksi', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1200', 'nama_akun' => 'Bank BCA', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1201', 'nama_akun' => 'Bank Mandiri', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1202', 'nama_akun' => 'Bank BRI', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],

            ['kode_akun' => '1-2100', 'nama_akun' => 'Piutang Usaha', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2200', 'nama_akun' => 'Piutang Pesanan (Order)', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2900', 'nama_akun' => 'Cadangan Kerugian Piutang', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Kredit'],

            // Persediaan Manufaktur (3 Level)
            ['kode_akun' => '1-3100', 'nama_akun' => 'Persediaan Bahan Baku', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3110', 'nama_akun' => 'Persediaan Besi & Baja', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3120', 'nama_akun' => 'Persediaan Stainless Steel', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3130', 'nama_akun' => 'Persediaan Aluminium', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3140', 'nama_akun' => 'Persediaan Komponen Elektrik', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3150', 'nama_akun' => 'Persediaan Bearing & Seal', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3200', 'nama_akun' => 'Persediaan Barang Dalam Proses (WIP)', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3300', 'nama_akun' => 'Persediaan Barang Jadi', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3400', 'nama_akun' => 'Persediaan Spare Part', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],

            ['kode_akun' => '1-4100', 'nama_akun' => 'Uang Muka Pembelian', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4200', 'nama_akun' => 'Biaya Dibayar Dimuka', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4300', 'nama_akun' => 'PPN Masukan', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],

            // 1.2 Aset Tetap
            ['kode_akun' => '1-5100', 'nama_akun' => 'Tanah', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5200', 'nama_akun' => 'Bangunan Pabrik', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5201', 'nama_akun' => 'Akumulasi Penyusutan Bangunan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5300', 'nama_akun' => 'Mesin CNC (Bubut, Milling, dll)', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5301', 'nama_akun' => 'Akumulasi Penyusutan Mesin CNC', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5400', 'nama_akun' => 'Mesin Las & Cutting', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5401', 'nama_akun' => 'Akumulasi Penyusutan Mesin Las', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5500', 'nama_akun' => 'Mesin Press & Bending', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5501', 'nama_akun' => 'Akumulasi Penyusutan Mesin Press', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5600', 'nama_akun' => 'Overhead Crane & Forklift', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5601', 'nama_akun' => 'Akumulasi Penyusutan Crane', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5700', 'nama_akun' => 'Peralatan Tangan & Perkakas', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5701', 'nama_akun' => 'Akumulasi Penyusutan Peralatan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5800', 'nama_akun' => 'Kendaraan Operasional', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5801', 'nama_akun' => 'Akumulasi Penyusutan Kendaraan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],

            // ===============================
            // 2. LIABILITAS (Kewajiban)
            // ===============================
            ['kode_akun' => '2-1100', 'nama_akun' => 'Utang Usaha', 'tipe_akun' => 'Utang Usaha', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1200', 'nama_akun' => 'Utang Supplier Bahan', 'tipe_akun' => 'Utang Usaha', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1300', 'nama_akun' => 'Utang Gaji & Upah', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1400', 'nama_akun' => 'Utang Pajak', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1500', 'nama_akun' => 'PPN Keluaran', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1600', 'nama_akun' => 'Uang Muka Pesanan (DP Customer)', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-2100', 'nama_akun' => 'Utang Bank Jangka Panjang', 'tipe_akun' => 'Kewajiban Jangka Panjang', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-2200', 'nama_akun' => 'Utang Leasing Mesin', 'tipe_akun' => 'Kewajiban Jangka Panjang', 'saldo_normal' => 'Kredit'],

            // ===============================
            // 3. EKUITAS (Modal)
            // ===============================
            ['kode_akun' => '3-1100', 'nama_akun' => 'Modal Disetor', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '3-1200', 'nama_akun' => 'Laba Ditahan', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '3-1300', 'nama_akun' => 'Laba Tahun Berjalan', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '3-1400', 'nama_akun' => 'Prive', 'tipe_akun' => 'Ekuitas', 'saldo_normal' => 'Debit'],

            // ===============================
            // 4. PENDAPATAN
            // ===============================
            ['kode_akun' => '4-1100', 'nama_akun' => 'Pendapatan Penjualan Mesin', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1200', 'nama_akun' => 'Pendapatan Jasa Fabrikasi', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1300', 'nama_akun' => 'Pendapatan Jasa Machining', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1400', 'nama_akun' => 'Pendapatan Jasa Repair & Maintenance', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1500', 'nama_akun' => 'Pendapatan Penjualan Spare Part', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-9100', 'nama_akun' => 'Pendapatan Bunga', 'tipe_akun' => 'Pendapatan Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-9200', 'nama_akun' => 'Pendapatan Lain-lain', 'tipe_akun' => 'Pendapatan Lainnya', 'saldo_normal' => 'Kredit'],

            // ===============================
            // 5. HARGA POKOK PRODUKSI (HPP)
            // ===============================
            ['kode_akun' => '5-1100', 'nama_akun' => 'HPP Bahan Baku', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1200', 'nama_akun' => 'HPP Tenaga Kerja Langsung', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1300', 'nama_akun' => 'HPP Overhead Pabrik', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1310', 'nama_akun' => 'HPP Listrik Pabrik', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1320', 'nama_akun' => 'HPP Gas & Bahan Bakar Produksi', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1330', 'nama_akun' => 'HPP Penyusutan Mesin', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1340', 'nama_akun' => 'HPP Pemeliharaan Mesin', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1400', 'nama_akun' => 'HPP Jasa Outsourcing Produksi', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],

            // ===============================
            // 6. BEBAN OPERASIONAL
            // ===============================
            ['kode_akun' => '6-1100', 'nama_akun' => 'Beban Gaji Kantor', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-1200', 'nama_akun' => 'Beban Tunjangan Karyawan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-1300', 'nama_akun' => 'Beban BPJS', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-2100', 'nama_akun' => 'Beban Sewa Kantor', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-2200', 'nama_akun' => 'Beban Listrik & Air Kantor', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-2300', 'nama_akun' => 'Beban Telepon & Internet', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-2400', 'nama_akun' => 'Beban ATK & Perlengkapan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-3100', 'nama_akun' => 'Beban Penyusutan Bangunan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-3200', 'nama_akun' => 'Beban Penyusutan Kendaraan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4100', 'nama_akun' => 'Beban Transportasi', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4200', 'nama_akun' => 'Beban Pengiriman', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4300', 'nama_akun' => 'Beban Pemeliharaan Kantor', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4400', 'nama_akun' => 'Beban Asuransi', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4500', 'nama_akun' => 'Beban Pajak', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4600', 'nama_akun' => 'Beban Administrasi Bank', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4700', 'nama_akun' => 'Beban Riset & Development', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-9100', 'nama_akun' => 'Beban Bunga', 'tipe_akun' => 'Beban Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-9200', 'nama_akun' => 'Beban Lain-lain', 'tipe_akun' => 'Beban Lainnya', 'saldo_normal' => 'Debit'],
        ];

        foreach ($akun as $a) {
            DB::table('akun')->updateOrInsert(
                ['kode_akun' => $a['kode_akun']],
                array_merge($a, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('COA Fabrikasi Mesin berhasil di-seed! Total: ' . count($akun) . ' akun');
    }
}
