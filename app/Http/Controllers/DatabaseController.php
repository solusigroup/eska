<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    /**
     * Check if user is superuser
     */
    private function isSuperuser()
    {
        $user = auth()->user();
        return $user && $user->role === 'superuser';
    }

    /**
     * Show database management page
     */
    public function index()
    {
        if (!$this->isSuperuser()) {
            abort(403, 'Akses ditolak. Hanya superuser yang dapat mengakses fitur ini.');
        }

        // Get database info
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        
        $tableInfo = [];
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $count = DB::table($tableName)->count();
            $tableInfo[] = [
                'name' => $tableName,
                'rows' => $count,
            ];
        }

        return view('database.index', compact('tableInfo', 'dbName'));
    }

    /**
     * Truncate all tables (keep structure, delete data)
     */
    public function truncate(Request $request)
    {
        if (!$this->isSuperuser()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'confirmation' => 'required|in:HAPUS DATA',
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');
        $excludeTables = ['migrations', 'users']; // Tables to keep

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            if (!in_array($tableName, $excludeTables)) {
                DB::table($tableName)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->route('database.index')
            ->with('success', 'Semua data berhasil dihapus (struktur tabel dipertahankan).');
    }

    /**
     * Fresh migration (drop all tables and recreate)
     */
    public function fresh(Request $request)
    {
        if (!$this->isSuperuser()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'confirmation' => 'required|in:RESET DATABASE',
        ]);

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            
            return redirect()->route('database.index')
                ->with('success', 'Database berhasil di-reset. Semua tabel dibuat ulang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal reset database: ' . $e->getMessage());
        }
    }

    /**
     * Drop database and recreate
     */
    public function drop(Request $request)
    {
        if (!$this->isSuperuser()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'confirmation' => 'required|in:HAPUS DATABASE',
            'password' => 'required',
        ]);

        // Verify password
        $user = auth()->user();
        if (!password_verify($request->password, $user->password_hash)) {
            return back()->with('error', 'Password salah.');
        }

        $dbName = config('database.connections.mysql.database');

        try {
            // Drop and recreate database
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            DB::statement("CREATE DATABASE `{$dbName}`");
            
            // Reconnect
            DB::reconnect();
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            return redirect()->route('database.index')
                ->with('success', 'Database berhasil dihapus dan dibuat ulang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus database: ' . $e->getMessage());
        }
    }

    /**
     * Run seeders
     */
    public function seed(Request $request)
    {
        if (!$this->isSuperuser()) {
            abort(403, 'Akses ditolak.');
        }

        $seeder = $request->input('seeder', 'DatabaseSeeder');

        // Scan files in database/seeders dynamically to avoid hardcoding allowed seeders list
        $seederFiles = glob(database_path('seeders/*.php'));
        $allowedSeeders = array_map(function ($file) {
            return basename($file, '.php');
        }, $seederFiles);

        if (!in_array($seeder, $allowedSeeders)) {
            return back()->with('error', 'Seeder tidak diizinkan atau tidak valid.');
        }

        try {
            if ($seeder === 'DatabaseSeeder') {
                Artisan::call('db:seed', ['--force' => true]);
            } else {
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
            }

            return redirect()->route('database.index')
                ->with('success', 'Seeder ' . $seeder . ' berhasil dijalankan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menjalankan seeder: ' . $e->getMessage());
        }
    }
}
