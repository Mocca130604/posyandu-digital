<?php

declare(strict_types=1);

/**
 * Script untuk mengekspor data database SQLite ke format SQL Dump & JSON
 * Menjamin portabilitas penuh saat migrasi ke server lain.
 */

$sqlitePath = __DIR__ . '/../database/database.sqlite';
$backupDir = __DIR__ . '/../database/backup';

if (!file_exists($sqlitePath)) {
    echo "File database.sqlite tidak ditemukan.\n";
    exit(1);
}

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $sqlitePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ambil daftar semua tabel
    $tablesStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    $sqlDump = "-- Posyandu Digital Database Backup Snapshot\n";
    $sqlDump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "PRAGMA foreign_keys = OFF;\n\n";

    $jsonData = [];

    foreach ($tables as $table) {
        // DDL Create Table
        $ddlStmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = '{$table}';");
        $createSql = $ddlStmt->fetchColumn();

        $sqlDump .= "-- Table: {$table}\n";
        $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sqlDump .= $createSql . ";\n";

        // Rows
        $rowsStmt = $pdo->query("SELECT * FROM `{$table}`;");
        $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
        $jsonData[$table] = $rows;

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $escapedCols = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", $cols);

                $escapedVals = array_map(function ($val) use ($pdo) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string)$val);
                }, array_values($row));

                $sqlDump .= "INSERT INTO `{$table}` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $escapedVals) . ");\n";
            }
        }
        $sqlDump .= "\n";
    }

    $sqlDump .= "PRAGMA foreign_keys = ON;\n";

    file_put_contents($backupDir . '/posyandu_snapshot.sql', $sqlDump);
    file_put_contents($backupDir . '/posyandu_data.json', json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "Berhasil mengekspor database:\n";
    echo "- " . realpath($backupDir . '/posyandu_snapshot.sql') . " (" . filesize($backupDir . '/posyandu_snapshot.sql') . " bytes)\n";
    echo "- " . realpath($backupDir . '/posyandu_data.json') . " (" . filesize($backupDir . '/posyandu_data.json') . " bytes)\n";
} catch (Exception $e) {
    echo "Error ekspor: " . $e->getMessage() . "\n";
    exit(1);
}
