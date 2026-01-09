#!/bin/bash
#
# Sunucu Monitor Collector Kurulum Script'i
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_NAME="sunucumonitor"
INSTALL_DIR="/opt/sunucumonitor"

echo "=== Sunucu Monitor Collector Kurulumu ==="
echo ""

# Root kontrolü
if [ "$EUID" -ne 0 ]; then
    echo "Bu script root olarak çalıştırılmalı."
    echo "Kullanım: sudo bash install.sh"
    exit 1
fi

# Python kontrolü
if ! command -v python3 &> /dev/null; then
    echo "Python3 bulunamadı. Yükleniyor..."
    apt-get update && apt-get install -y python3 python3-pip
fi

# pip kontrolü
if ! command -v pip3 &> /dev/null; then
    echo "pip3 bulunamadı. Yükleniyor..."
    apt-get install -y python3-pip
fi

# Kurulum dizini oluştur
echo "Kurulum dizini: $INSTALL_DIR"
mkdir -p "$INSTALL_DIR"

# Dosyaları kopyala
echo "Dosyalar kopyalanıyor..."
cp "$SCRIPT_DIR/collector.py" "$INSTALL_DIR/"
cp "$SCRIPT_DIR/requirements.txt" "$INSTALL_DIR/"

# config.json varsa kopyala, yoksa template oluştur
if [ -f "$INSTALL_DIR/config.json" ]; then
    echo "Mevcut config.json korunuyor..."
else
    cp "$SCRIPT_DIR/config.json" "$INSTALL_DIR/"
    echo ""
    echo "⚠️  IMPORTANT: config.json dosyasını düzenlemelisiniz!"
    echo "    nano $INSTALL_DIR/config.json"
    echo ""
fi

# Python bağımlılıklarını yükle
echo "Python bağımlılıkları yükleniyor..."
pip3 install -r "$INSTALL_DIR/requirements.txt" --break-system-packages 2>/dev/null || pip3 install -r "$INSTALL_DIR/requirements.txt"

# Systemd service oluştur
echo "Systemd service oluşturuluyor..."
cat > "/etc/systemd/system/${SERVICE_NAME}.service" << EOF
[Unit]
Description=Sunucu Monitor Collector
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=$INSTALL_DIR
ExecStart=/usr/bin/python3 $INSTALL_DIR/collector.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Systemd reload
systemctl daemon-reload

echo ""
echo "=== Kurulum Tamamlandı ==="
echo ""
echo "Sonraki adımlar:"
echo "1. Konfigürasyonu düzenleyin:"
echo "   nano $INSTALL_DIR/config.json"
echo ""
echo "2. Servisi başlatın:"
echo "   systemctl start $SERVICE_NAME"
echo ""
echo "3. Otomatik başlatma için:"
echo "   systemctl enable $SERVICE_NAME"
echo ""
echo "4. Durumu kontrol edin:"
echo "   systemctl status $SERVICE_NAME"
echo ""
echo "5. Logları görüntüleyin:"
echo "   journalctl -u $SERVICE_NAME -f"
echo ""
