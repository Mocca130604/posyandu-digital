@echo off
setlocal enabledelayedexpansion
title Posyandu Digital - Internet Tunneling
echo =====================================================================
echo        TUNNELING POSYANDU DIGITAL KE INTERNET (ONLINE)
echo =====================================================================
echo.
echo Pilih opsi tunneling yang ingin Anda gunakan:
echo.
echo [1] Cloudflare Tunnel (cloudflared) - Rekomendasi (Gratis, HTTPS, Cepat)
echo [2] Ngrok (ngrok http 8000)
echo [3] SSH Localhost.run (Tanpa install aplikasi tambahan, butuh OpenSSH)
echo.

set /p TUNNEL_CHOICE="Masukkan nomor pilihan (1/2/3) [default: 1]: "
if "%TUNNEL_CHOICE%"=="" set TUNNEL_CHOICE=1

if "%TUNNEL_CHOICE%"=="1" (
    echo.
    echo Menjalankan Cloudflare Tunnel ke http://127.0.0.1:8000...
    echo Salin URL HTTPS yang berakhiran .trycloudflare.com untuk dibagikan ke pengguna.
    echo.
    where cloudflared >nul 2>&1
    if %errorlevel% equ 0 (
        cloudflared tunnel --url http://127.0.0.1:8000
    ) else (
        echo [INFO] Perintah 'cloudflared' tidak ditemukan di PATH.
        echo Menggunakan cloudflared portable jika ada, atau silakan unduh dari:
        echo https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
        echo.
        echo Menjalankan opsi alternatif SSH Tunnel...
        goto SSH_OPTION
    )
)

if "%TUNNEL_CHOICE%"=="2" (
    echo.
    echo Menjalankan Ngrok Tunnel ke port 8000...
    ngrok http 8000
    goto END
)

:SSH_OPTION
if "%TUNNEL_CHOICE%"=="3" or "%TUNNEL_CHOICE%"=="1" (
    echo.
    echo Menghubungkan ke localhost.run via SSH...
    echo Tunggu beberapa detik, tautan HTTPS publik akan tampil di bawah:
    echo.
    ssh -R 80:localhost:8000 nokey@localhost.run
)

:END
pause
