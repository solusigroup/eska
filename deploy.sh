#!/bin/bash

# ======================================================================
# SimpleAkunting ESKA - Autodeploy Script for aaPanel Webhook
# ======================================================================

# Konfigurasi
PROJECT_PATH="/home/kurniawan/simpleakunting_eska"
PHP_BIN="php" # Ganti dengan path PHP spesifik jika perlu, misal: /www/server/php/82/bin/php
COMPOSER_BIN="composer" # Ganti dengan path Composer jika perlu

echo "🚀 Memulai proses deployment: $(date)"
cd $PROJECT_PATH || exit

# 1. Tarik kode terbaru dari GitHub
echo "📥 Menarik kode terbaru dari branch main..."
git pull origin main

# 2. Install/Update dependencies
echo "📦 Menginstal dependencies (no-dev)..."
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction

# 3. Jalankan migrasi database
echo "🗄️ Menjalankan migrasi database..."
$PHP_BIN artisan migrate --force

# 4. Optimasi Laravel
echo "⚡ Mengoptimalkan cache Laravel..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 5. Set Permission (Opsional, pastikan user sesuai)
# chown -R www:www $PROJECT_PATH
# chmod -R 775 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache

echo "✅ Deployment selesai: $(date)"
echo "--------------------------------------------------"
