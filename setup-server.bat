@echo off
setlocal enabledelayedexpansion
title Posyandu Digital - Setup Server & Database Restore
echo =====================================================================
echo       SETUP OTOMATIS POSYANDU DIGITAL PADA SERVER BARU
echo =====================================================================
echo.

REM 1. Pindah ke direktori skrip berada
cd /d "%~dp0"

REM 2. Cek apakah .env ada, jika belum salin dari .env.example
if not exist ".env" (
    echo [1/6] Membuat file .env dari .env.example...
    copy ".env.example" ".env" >nul
) else (
    echo [1/6] File .env sudah tersedia.
)

REM 3. Deteksi binary PHP
set "PHP_CMD=php"
if exist "C:\Users\Firdaus\.config\herd-lite\bin\php.exe" (
    set "PHP_CMD=C:\Users\Firdaus\.config\herd-lite\bin\php.exe"
)

echo [2/6] Memeriksa PHP...
%PHP_CMD% -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak ditemukan di PATH sistem. Mohon install PHP ^>= 8.2 terlebih dahulu.
    pause
    exit /b 1
)

REM 4. Generate APP_KEY jika belum terpasang
echo [3/6] Menyiapkan Application Key...
findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if %errorlevel% neq 0 (
    %PHP_CMD% artisan key:generate --force
)

REM 5. Restore Database dari Snapshot Backup
echo [4/6] Mengembalikan (Restore) Database dan Seluruh Data...
%PHP_CMD% scripts/restore_database.php

REM 6. Jalankan migrasi tabel (jika ada skema baru tanpa menghapus data)
echo [5/6] Memastikan skema tabel mutakhir...
%PHP_CMD% artisan migrate --force

REM 7. Storage link & Clear cache
echo [6/6] Menautkan storage dan membersihkan cache aplikasi...
%PHP_CMD% artisan storage:link >nul 2>&1
%PHP_CMD% artisan config:clear >nul 2>&1
%PHP_CMD% artisan route:clear >nul 2>&1
%PHP_CMD% artisan view:clear >nul 2>&1

echo.
echo =====================================================================
echo  SETUP SELESAI! Seluruh data (Kader, Bidan, Balita, KMS) siap dipakai.
echo =====================================================================
echo.
echo Langkah selanjutnya:
echo  1. Jalankan aplikasi : run-app.bat
echo  2. Jalankan tunneling: tunnel.bat (Cloudflare / Ngrok / SSH)
echo.
pause
