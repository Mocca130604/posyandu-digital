@echo off
title Posyandu Digital - Development Server
echo ===================================================
echo     MEMULAI SERVER POSYANDU DIGITAL LOKAL
echo ===================================================
echo.
echo URL Aplikasi: http://127.0.0.1:8000
echo Akun Login  : Lihat file KREDENSIAL_DEFAULT.md
echo.
echo Menjalankan Laravel Development Server...
echo Tekan CTRL + C untuk menghentikan server.
echo.

REM Cek apakah PHP Herd tersedia, jika tidak gunakan php default di PATH
if exist "C:\Users\Firdaus\.config\herd-lite\bin\php.exe" (
    "C:\Users\Firdaus\.config\herd-lite\bin\php.exe" artisan serve --host=127.0.0.1 --port=8000
) else (
    php artisan serve --host=127.0.0.1 --port=8000
)
pause
