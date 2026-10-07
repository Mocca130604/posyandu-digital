<?php

declare(strict_types=1);

/**
 * Script Otomatis Restore Database Posyandu Digital
 * Mengembalikan seluruh data (Kader, Bidan, Balita, Pengukuran KMS, Imunisasi)
 * pada server baru secara instan.
 */

$root = dirname(__DIR__);
$sqliteTarget = $root . '/database/database.sqlite';
$sqliteSnapshot = $root . '/database/backup/posyandu_snapshot.sqlite';
$sqlSnapshot = $root . '/database/backup/posyandu_snapshot.sql';

echo "=== RESTORE DATABASE POSYANDU DIGITAL ===\n";

if (!is_dir($root . '/database')) {
    mkdir($root . '/database', 0755, true);
}

// 1. Cek apakah ada binary snapshot SQLite
if (file_exists($sqliteSnapshot) && (!file_exists($sqliteTarget) || filesize($sqliteTarget) < 10000)) {
    echo "Menyalin snapshot database SQLite...\n";
    copy($sqliteSnapshot, $sqliteTarget);
    echo "✓ Database SQLite berhasil disalin ke: {$sqliteTarget}\n";
    exit(0);
}

// 2. Jika database.sqlite belum ada dan ada SQL snapshot
if (!file_exists($sqliteTarget) && file_exists($sqlSnapshot)) {
    echo "Membuat database.sqlite dari file posyandu_snapshot.sql...\n";
    touch($sqliteTarget);
    $pdo = new PDO('sqlite:' . $sqliteTarget);
    $sqlContent = file_get_contents($sqlSnapshot);
    $pdo->exec($sqlContent);
    echo "✓ Database SQLite berhasil di-restore dari SQL snapshot!\n";
    exit(0);
}

if (file_exists($sqliteTarget) && filesize($sqliteTarget) > 10000) {
    echo "✓ Database aktif sudah ada dan memiliki isi (" . filesize($sqliteTarget) . " bytes).\n";
    exit(0);
}

echo "Peringatan: File backup database tidak ditemukan di database/backup/\n";
exit(1);
