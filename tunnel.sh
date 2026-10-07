#!/bin/bash
# =====================================================================
#        TUNNELING POSYANDU DIGITAL KE INTERNET (LINUX / VPS)
# =====================================================================

echo "====================================================================="
echo "       TUNNELING POSYANDU DIGITAL KE INTERNET (ONLINE)"
echo "====================================================================="
echo ""
echo "Pilih metode tunneling:"
echo "[1] Cloudflare Tunnel (cloudflared) - Rekomendasi"
echo "[2] Ngrok"
echo "[3] SSH Localhost.run (Tanpa install aplikasi tambahan)"
echo ""
read -p "Pilihan Anda [1-3, default 1]: " choice
choice=${choice:-1}

if [ "$choice" = "1" ]; then
    if command -v cloudflared &> /dev/null; then
        echo "Menjalankan Cloudflare Tunnel ke http://127.0.0.1:8000..."
        cloudflared tunnel --url http://127.0.0.1:8000
    else
        echo "cloudflared belum terinstall. Menggunakan SSH Localhost.run sebagai alternatif..."
        ssh -R 80:localhost:8000 nokey@localhost.run
    fi
elif [ "$choice" = "2" ]; then
    ngrok http 8000
else
    echo "Menghubungkan ke localhost.run via SSH..."
    ssh -R 80:localhost:8000 nokey@localhost.run
fi
