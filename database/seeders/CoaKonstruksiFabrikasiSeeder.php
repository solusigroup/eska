<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoaKonstruksiFabrikasiSeeder extends Seeder
{
    /**
     * COA untuk Perusahaan Jasa Konstruksi & Fabrikasi Mesin
     * Menggabungkan akun-akun yang relevan untuk kedua jenis usaha
     * Standar Akuntansi Indonesia (PSAK 201)
     */
    public function run(): void
    {
        $akun = [
            // ===============================
            // 1. ASET (Asset)
            // ===============================
            // 1.1 Kas & Bank
            ['kode_akun' => '1-1100', 'nama_akun' => 'Kas', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1101', 'nama_akun' => 'Kas Proyek', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1102', 'nama_akun' => 'Kas Produksi', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1200', 'nama_akun' => 'Bank BCA', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1201', 'nama_akun' => 'Bank Mandiri', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-1202', 'nama_akun' => 'Bank BRI', 'tipe_akun' => 'Kas & Bank', 'saldo_normal' => 'Debit'],

            // 1.2 Piutang
            ['kode_akun' => '1-2100', 'nama_akun' => 'Piutang Usaha', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2200', 'nama_akun' => 'Piutang Retensi', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2300', 'nama_akun' => 'Piutang Termin', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2400', 'nama_akun' => 'Piutang Pesanan (Order)', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-2900', 'nama_akun' => 'Cadangan Kerugian Piutang', 'tipe_akun' => 'Piutang', 'saldo_normal' => 'Kredit'],

            // 1.3 Persediaan (Konstruksi)
            ['kode_akun' => '1-3100', 'nama_akun' => 'Persediaan Bahan Bangunan', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3110', 'nama_akun' => 'Persediaan Semen', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3120', 'nama_akun' => 'Persediaan Pasir & Batu', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3130', 'nama_akun' => 'Persediaan Kayu', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],

            // 1.3 Persediaan (Fabrikasi)
            ['kode_akun' => '1-3200', 'nama_akun' => 'Persediaan Besi & Baja', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3210', 'nama_akun' => 'Persediaan Stainless Steel', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3220', 'nama_akun' => 'Persediaan Aluminium', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3230', 'nama_akun' => 'Persediaan Komponen Elektrik', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3240', 'nama_akun' => 'Persediaan Bearing & Seal', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3300', 'nama_akun' => 'Persediaan Barang Dalam Proses (WIP)', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3400', 'nama_akun' => 'Persediaan Barang Jadi', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-3500', 'nama_akun' => 'Persediaan Spare Part', 'tipe_akun' => 'Persediaan', 'saldo_normal' => 'Debit'],

            // 1.4 Aset Lancar Lainnya
            ['kode_akun' => '1-4100', 'nama_akun' => 'Uang Muka Pembelian', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4200', 'nama_akun' => 'Uang Muka Subkontraktor', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4300', 'nama_akun' => 'Biaya Dibayar Dimuka', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4400', 'nama_akun' => 'PPN Masukan', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-4500', 'nama_akun' => 'Pekerjaan Dalam Proses (WIP Konstruksi)', 'tipe_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'Debit'],

            // 1.5 Aset Tetap
            ['kode_akun' => '1-5100', 'nama_akun' => 'Tanah', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5200', 'nama_akun' => 'Bangunan Kantor & Pabrik', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5201', 'nama_akun' => 'Akumulasi Penyusutan Bangunan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5300', 'nama_akun' => 'Mesin CNC (Bubut, Milling, dll)', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5301', 'nama_akun' => 'Akumulasi Penyusutan Mesin CNC', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5400', 'nama_akun' => 'Mesin Las & Cutting', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5401', 'nama_akun' => 'Akumulasi Penyusutan Mesin Las', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5500', 'nama_akun' => 'Mesin Press & Bending', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5501', 'nama_akun' => 'Akumulasi Penyusutan Mesin Press', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5600', 'nama_akun' => 'Alat Berat (Excavator, Crane, dll)', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5601', 'nama_akun' => 'Akumulasi Penyusutan Alat Berat', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5700', 'nama_akun' => 'Peralatan Konstruksi & Fabrikasi', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5701', 'nama_akun' => 'Akumulasi Penyusutan Peralatan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5800', 'nama_akun' => 'Scaffolding & Bekisting', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5801', 'nama_akun' => 'Akumulasi Penyusutan Scaffolding', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '1-5900', 'nama_akun' => 'Kendaraan Operasional', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '1-5901', 'nama_akun' => 'Akumulasi Penyusutan Kendaraan', 'tipe_akun' => 'Aset Tetap', 'saldo_normal' => 'Kredit'],

            // ===============================
            // 2. LIABILITAS (Kewajiban)
            // ===============================
            ['kode_akun' => '2-1100', 'nama_akun' => 'Utang Usaha', 'tipe_akun' => 'Utang Usaha', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1200', 'nama_akun' => 'Utang Subkontraktor', 'tipe_akun' => 'Utang Usaha', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1300', 'nama_akun' => 'Utang Supplier Bahan', 'tipe_akun' => 'Utang Usaha', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1400', 'nama_akun' => 'Utang Retensi', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1500', 'nama_akun' => 'Utang Gaji & Upah', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1600', 'nama_akun' => 'Utang Pajak', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1700', 'nama_akun' => 'PPN Keluaran', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1800', 'nama_akun' => 'Uang Muka Proyek (DP Owner)', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-1900', 'nama_akun' => 'Uang Muka Pesanan (DP Customer)', 'tipe_akun' => 'Kewajiban Lancar Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-2100', 'nama_akun' => 'Utang Bank Jangka Panjang', 'tipe_akun' => 'Kewajiban Jangka Panjang', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '2-2200', 'nama_akun' => 'Utang Leasing Alat/Mesin', 'tipe_akun' => 'Kewajiban Jangka Panjang', 'saldo_normal' => 'Kredit'],

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
            // Pendapatan Konstruksi
            ['kode_akun' => '4-1100', 'nama_akun' => 'Pendapatan Jasa Konstruksi', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1200', 'nama_akun' => 'Pendapatan Termin Proyek', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-1300', 'nama_akun' => 'Pendapatan Pekerjaan Tambah (VO)', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            // Pendapatan Fabrikasi
            ['kode_akun' => '4-2100', 'nama_akun' => 'Pendapatan Penjualan Mesin', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-2200', 'nama_akun' => 'Pendapatan Jasa Fabrikasi', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-2300', 'nama_akun' => 'Pendapatan Jasa Machining', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-2400', 'nama_akun' => 'Pendapatan Jasa Repair & Maintenance', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-2500', 'nama_akun' => 'Pendapatan Penjualan Spare Part', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            // Pendapatan Lainnya
            ['kode_akun' => '4-3100', 'nama_akun' => 'Pendapatan Sewa Alat', 'tipe_akun' => 'Pendapatan', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-9100', 'nama_akun' => 'Pendapatan Bunga', 'tipe_akun' => 'Pendapatan Lainnya', 'saldo_normal' => 'Kredit'],
            ['kode_akun' => '4-9200', 'nama_akun' => 'Pendapatan Lain-lain', 'tipe_akun' => 'Pendapatan Lainnya', 'saldo_normal' => 'Kredit'],

            // ===============================
            // 5. HARGA POKOK (HPP/HPP Proyek)
            // ===============================
            // HPP Konstruksi
            ['kode_akun' => '5-1100', 'nama_akun' => 'HPP Bahan Bangunan', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1200', 'nama_akun' => 'HPP Upah Tenaga Kerja Langsung', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1300', 'nama_akun' => 'HPP Subkontraktor', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1400', 'nama_akun' => 'HPP Sewa Alat Berat', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1500', 'nama_akun' => 'HPP Overhead Proyek', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-1600', 'nama_akun' => 'HPP Transportasi Material', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            // HPP Fabrikasi
            ['kode_akun' => '5-2100', 'nama_akun' => 'HPP Bahan Baku', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2200', 'nama_akun' => 'HPP Tenaga Kerja Langsung Produksi', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2300', 'nama_akun' => 'HPP Overhead Pabrik', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2310', 'nama_akun' => 'HPP Listrik Pabrik', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2320', 'nama_akun' => 'HPP Gas & Bahan Bakar Produksi', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2330', 'nama_akun' => 'HPP Penyusutan Mesin', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2340', 'nama_akun' => 'HPP Pemeliharaan Mesin', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '5-2400', 'nama_akun' => 'HPP Jasa Outsourcing Produksi', 'tipe_akun' => 'HPP', 'saldo_normal' => 'Debit'],

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
            ['kode_akun' => '6-3300', 'nama_akun' => 'Beban Penyusutan Alat Berat', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-3400', 'nama_akun' => 'Beban Penyusutan Peralatan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4100', 'nama_akun' => 'Beban Transportasi', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4200', 'nama_akun' => 'Beban Perjalanan Dinas', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4300', 'nama_akun' => 'Beban Tender & Proposal', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4400', 'nama_akun' => 'Beban Pemeliharaan Kantor', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4500', 'nama_akun' => 'Beban Asuransi', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4600', 'nama_akun' => 'Beban Jaminan Pelaksanaan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4700', 'nama_akun' => 'Beban Administrasi Bank', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4800', 'nama_akun' => 'Beban Pajak', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
            ['kode_akun' => '6-4900', 'nama_akun' => 'Beban Riset & Development', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit'],
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

        $this->command->info('COA Jasa Konstruksi & Fabrikasi Mesin berhasil di-seed! Total: ' . count($akun) . ' akun');
    }
}
