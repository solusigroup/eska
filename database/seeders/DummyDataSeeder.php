<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use App\Models\Pemasok;
use App\Models\Persediaan;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        $this->command->info('Mulai membuat dummy data...');

        // 1. Dummy Pelanggan
        for ($i = 1; $i <= 5; $i++) {
            Pelanggan::create([
                'nama_pelanggan' => $faker->company,
                'alamat' => $faker->address,
                'telepon' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'saldo_awal_piutang' => $faker->randomElement([0, 500000, 1500000]),
                'saldo_terkini_piutang' => 0,
            ]);
        }
        $this->command->info('- 5 Pelanggan berhasil dibuat.');

        // 2. Dummy Pemasok
        for ($i = 1; $i <= 5; $i++) {
            Pemasok::create([
                'nama_pemasok' => $faker->company,
                'alamat' => $faker->address,
                'telepon' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'saldo_awal_hutang' => $faker->randomElement([0, 1000000, 2000000]),
                'saldo_terkini_hutang' => 0,
            ]);
        }
        $this->command->info('- 5 Pemasok berhasil dibuat.');

        // 3. Dummy Persediaan
        $barang = [
            ['nama' => 'Semen Gresik 50KG', 'satuan' => 'Sak', 'beli' => 50000, 'jual' => 60000],
            ['nama' => 'Besi Beton 10mm', 'satuan' => 'Batang', 'beli' => 45000, 'jual' => 55000],
            ['nama' => 'Cat Tembok Dulux 25KG', 'satuan' => 'Pail', 'beli' => 1200000, 'jual' => 1400000],
            ['nama' => 'Pasir Lumajang', 'satuan' => 'Pick Up', 'beli' => 300000, 'jual' => 400000],
            ['nama' => 'Paku Kayu 5cm', 'satuan' => 'Kg', 'beli' => 15000, 'jual' => 20000],
        ];

        foreach ($barang as $index => $item) {
            Persediaan::create([
                'kode_barang' => 'BRG-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'nama_barang' => $item['nama'],
                'satuan' => $item['satuan'],
                'stok_awal' => $faker->numberBetween(10, 100),
                'stok_saat_ini' => $faker->numberBetween(10, 100),
                'harga_beli' => $item['beli'],
                'harga_jual' => $item['jual'],
            ]);
        }
        $this->command->info('- 5 Barang / Persediaan berhasil dibuat.');

        $this->command->info('Semua dummy data selesai dimasukkan!');
    }
}
