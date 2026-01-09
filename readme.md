# Sunucu Monitor

Çoklu sunucudan sistem metriklerini (CPU, RAM, Disk, Network, Process) toplayan ve grafiklerle görselleştiren izleme sistemi.

## Özellikler

- 🖥️ **Çoklu Sunucu Desteği** - Sınırsız sayıda sunucu izleme
- 📊 **Gerçek Zamanlı Grafikler** - CPU, RAM, Load Average
- 👥 **Kullanıcı Bazlı Analiz** - Her kullanıcının kaynak kullanımı
- 🔄 **Güvenilir Collector** - Offline durumda veri kaybı yok
- 📱 **Responsive Dashboard** - Mobil uyumlu arayüz
- 🐳 **Coolify Ready** - Nixpacks ile kolay deployment
- 💾 **SQLite** - Harici veritabanı gerektirmez

---

## Coolify Deployment (Nixpacks)

### 1. Yeni Uygulama Oluştur

| Ayar | Değer |
|------|-------|
| **Repository** | `https://github.com/zaferbas/sunucumonitor` |
| **Branch** | `main` |
| **Build Pack** | `Nixpacks` ✅ |
| **Port** | `80` |
| **Base Directory** | `/` |

> ⚠️ **Build Pack olarak Nixpacks seçin!** Dockerfile değil.

### 2. Environment Variables

**Coolify > Configuration > Environment Variables:**

```env
APP_NAME=SunucuMonitor
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:GENERATE_OR_LEAVE_EMPTY

DB_CONNECTION=sqlite

MONITOR_API_KEY=guclu-bir-api-key-buraya
MONITOR_RETENTION_RAW=7
MONITOR_RETENTION_PROCESSES=3
MONITOR_RETENTION_HOURLY=90
MONITOR_RETENTION_DAILY=365
```

### 3. Persistent Storage (ÖNEMLİ!)

SQLite veritabanı container içinde tutulduğu için **Persistent Storage** gereklidir.

**Coolify > Configuration > Persistent Storage:**

| Host Path | Container Path | Açıklama |
|-----------|---------------|----------|
| `/data/sunucumonitor/database` | `/app/database` | SQLite veritabanı |
| `/data/sunucumonitor/storage` | `/app/storage` | Laravel storage |

> ⚠️ Container path'ler Nixpacks için `/app` ile başlar!

### 4. Deploy

**Deploy** butonuna basın. Nixpacks otomatik olarak:
- PHP ve gerekli extension'ları yükler
- Composer dependencies kurar
- SQLite veritabanı oluşturur
- Migrations çalıştırır
- Nginx + PHP-FPM + Scheduler başlatır

---

## Collector Kurulumu

### Otomatik Kurulum (Linux)

```bash
# Dosyaları sunucuya kopyala
scp -r collector/ user@server:/tmp/

# SSH ile bağlan
ssh user@server

# Kurulumu çalıştır
cd /tmp/collector
sudo bash install.sh
```

### config.json

```bash
sudo nano /opt/sunucumonitor/config.json
```

```json
{
  "target_url": "https://your-domain.com/api/metrics",
  "api_key": "guclu-bir-api-key-buraya",
  "server_id": "web-server-01",
  "interval": 60,
  "top_processes": 10
}
```

### Servisi Başlat

```bash
sudo systemctl start sunucumonitor
sudo systemctl enable sunucumonitor
sudo journalctl -u sunucumonitor -f
```

---

## API Endpoints

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/metrics` | Metrik gönder |
| `GET` | `/api/servers` | Sunucu listesi |
| `GET` | `/api/servers/{id}` | Sunucu detay |

Header: `X-API-Key: your-api-key`

---

## Veri Saklama

| Veri Tipi | Varsayılan |
|-----------|------------|
| Ham metrikler | 7 gün |
| Process verileri | 3 gün |
| Saatlik özetler | 90 gün |
| Günlük özetler | 1 yıl |

---

## Lokal Geliştirme

```bash
git clone https://github.com/zaferbas/sunucumonitor.git
cd sunucumonitor
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

---

## Lisans

MIT
