# Sunucu Monitor

Çoklu sunucudan sistem metriklerini (CPU, RAM, Disk, Network, Process) toplayan ve grafiklerle görselleştiren izleme sistemi.

![Dashboard](https://via.placeholder.com/800x400?text=Dashboard+Screenshot)

## Özellikler

- 🖥️ **Çoklu Sunucu Desteği** - Sınırsız sayıda sunucu izleme
- 📊 **Gerçek Zamanlı Grafikler** - CPU, RAM, Load Average
- 👥 **Kullanıcı Bazlı Analiz** - Her kullanıcının kaynak kullanımı
- 🔄 **Güvenilir Collector** - Offline durumda veri kaybı yok
- 📱 **Responsive Dashboard** - Mobil uyumlu arayüz
- 🐳 **Coolify Ready** - Dockerfile ile kolay deployment

## Mimari

```
┌─────────────────┐     HTTP POST      ┌─────────────────┐
│  Kaynak Sunucu  │ ─────────────────► │  Laravel API    │
│  (collector.py) │      JSON          │  + Dashboard    │
└─────────────────┘                    └─────────────────┘
         │                                      │
         └──────── Retry + Local Queue ─────────┘
```

## Hızlı Başlangıç

### 1. Laravel Projesini Kur (Coolify)

1. Coolify'da yeni bir proje oluşturun
2. GitHub repo'sunu bağlayın
3. Build Pack: **Dockerfile**
4. Environment Variables ekleyin:

```env
APP_NAME="Sunucu Monitor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=sunucumonitor
DB_USERNAME=your-username
DB_PASSWORD=your-password

MONITOR_API_KEY=your-secret-api-key
```

5. Deploy edin

### 2. Collector'ı Kaynak Sunuculara Kur

```bash
# Dosyaları sunucuya kopyala
scp -r collector/ user@server:/tmp/

# SSH ile bağlan
ssh user@server

# Kurulumu çalıştır
cd /tmp/collector
sudo bash install.sh

# Konfigürasyonu düzenle
sudo nano /opt/sunucumonitor/config.json
```

**config.json örneği:**
```json
{
  "target_url": "https://your-monitor.com/api/metrics",
  "api_key": "your-secret-api-key",
  "server_id": "web-server-01",
  "interval": 60,
  "top_processes": 10
}
```

```bash
# Servisi başlat
sudo systemctl start sunucumonitor
sudo systemctl enable sunucumonitor

# Logları kontrol et
sudo journalctl -u sunucumonitor -f
```

## Collector Özellikleri

| Özellik | Açıklama |
|---------|----------|
| **Retry** | Bağlantı hatalarında 3x deneme (exponential backoff) |
| **Local Queue** | Offline durumda verileri `queue.json`'a kaydet |
| **Timestamp** | Orijinal ölçüm zamanı korunur |
| **Auto Sync** | Bağlantı gelince kuyruk otomatik gönderilir |

## API Endpoints

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/metrics` | Metrik gönder |
| `GET` | `/api/servers` | Sunucu listesi |
| `GET` | `/api/servers/{id}` | Sunucu detay |
| `GET` | `/api/servers/{id}/metrics` | Metrik geçmişi |
| `GET` | `/api/servers/{id}/users` | Kullanıcı özeti |

## Veri Saklama

| Veri Tipi | Varsayılan Süre |
|-----------|-----------------|
| Ham metrikler | 7 gün |
| Process verileri | 3 gün |
| Saatlik özetler | 90 gün |
| Günlük özetler | 1 yıl |

`.env` ile özelleştirin:
```env
MONITOR_RETENTION_RAW=7
MONITOR_RETENTION_PROCESSES=3
MONITOR_RETENTION_HOURLY=90
MONITOR_RETENTION_DAILY=365
```

## Komutlar

```bash
# Saatlik özet oluştur
php artisan metrics:aggregate --period=hourly

# Günlük özet oluştur
php artisan metrics:aggregate --period=daily

# Eski verileri sil (dry-run)
php artisan metrics:prune --dry-run

# Eski verileri sil
php artisan metrics:prune
```

## Geliştirme

```bash
# Clone
git clone https://github.com/your-repo/sunucumonitor.git
cd sunucumonitor

# Dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Server
php artisan serve
```

## Collector Test

```bash
cd collector

# Bağımlılıkları yükle
pip3 install -r requirements.txt

# JSON çıktısını gör (gönderme)
python3 collector.py --dry-run

# Tek seferlik gönder
python3 collector.py --once

# Bağlantı testi
python3 collector.py --test
```

## Lisans

MIT
