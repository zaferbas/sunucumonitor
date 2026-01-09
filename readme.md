# Sunucu Monitor

Çoklu sunucudan sistem metriklerini (CPU, RAM, Disk, Network, Process) toplayan ve grafiklerle görselleştiren izleme sistemi.

## Özellikler

- 🖥️ **Çoklu Sunucu Desteği** - Sınırsız sayıda sunucu izleme
- 📊 **Gerçek Zamanlı Grafikler** - CPU, RAM, Load Average
- 👥 **Kullanıcı Bazlı Analiz** - Her kullanıcının kaynak kullanımı
- 🔄 **Güvenilir Collector** - Offline durumda veri kaybı yok
- 📱 **Responsive Dashboard** - Mobil uyumlu arayüz
- 🐳 **Coolify Ready** - Dockerfile ile kolay deployment
- 💾 **SQLite** - Harici veritabanı gerektirmez

## Mimari

```
┌─────────────────┐     HTTP POST      ┌─────────────────┐
│  Kaynak Sunucu  │ ─────────────────► │  Laravel API    │
│  (collector.py) │      JSON          │  + Dashboard    │
└─────────────────┘                    └─────────────────┘
         │                                      │
         └──────── Retry + Local Queue ─────────┘
```

---

## Coolify Deployment

### 1. Yeni Uygulama Oluştur

| Ayar | Değer |
|------|-------|
| **Repository** | `https://github.com/zaferbas/sunucumonitor` |
| **Branch** | `main` |
| **Build Pack** | `Dockerfile` |
| **Port** | `80` |
| **Base Directory** | `/` |

### 2. Persistent Storage (ÖNEMLİ!)

SQLite veritabanı container içinde tutulduğu için **Persistent Storage** gereklidir. Yoksa her deploy'da veriler silinir!

**Coolify > Configuration > Persistent Storage:**

| Host Path | Container Path | Açıklama |
|-----------|---------------|----------|
| `/data/sunucumonitor/database` | `/var/www/html/database` | SQLite veritabanı |
| `/data/sunucumonitor/storage` | `/var/www/html/storage` | Laravel storage |

**Adımlar:**
1. Sol menüden **Persistent Storage** sekmesine gidin
2. **Add Storage** butonuna tıklayın
3. İlk satırı ekleyin:
   - **Host Path:** `/data/sunucumonitor/database`
   - **Container Path:** `/var/www/html/database`
4. İkinci satırı ekleyin:
   - **Host Path:** `/data/sunucumonitor/storage`
   - **Container Path:** `/var/www/html/storage`

### 3. Environment Variables

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

> ⚠️ **MONITOR_API_KEY** değerini güçlü bir şifre yapın ve collector'larda aynısını kullanın!

### 4. Deploy

**Deploy** butonuna basın. İlk deployment'da:
- SQLite veritabanı otomatik oluşturulur
- Migrations otomatik çalışır
- Cache'ler oluşturulur

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

# Konfigürasyonu düzenle
sudo nano /opt/sunucumonitor/config.json
```

### config.json

```json
{
  "target_url": "https://your-domain.com/api/metrics",
  "api_key": "guclu-bir-api-key-buraya",
  "server_id": "web-server-01",
  "interval": 60,
  "top_processes": 10,
  "retry_count": 3,
  "retry_delay": 2,
  "max_queue_size": 1000
}
```

### Servisi Başlat

```bash
sudo systemctl start sunucumonitor
sudo systemctl enable sunucumonitor
sudo systemctl status sunucumonitor

# Logları izle
sudo journalctl -u sunucumonitor -f
```

---

## API Endpoints

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| `GET` | `/api/health` | Health check |
| `POST` | `/api/metrics` | Metrik gönder (collector) |
| `GET` | `/api/servers` | Sunucu listesi |
| `GET` | `/api/servers/{id}` | Sunucu detay |
| `GET` | `/api/servers/{id}/metrics` | Metrik geçmişi |

Tüm POST/GET istekleri `X-API-Key` header'ı gerektirir.

---

## Veri Saklama

| Veri Tipi | Varsayılan Süre |
|-----------|-----------------|
| Ham metrikler | 7 gün |
| Process verileri | 3 gün |
| Saatlik özetler | 90 gün |
| Günlük özetler | 1 yıl |

---

## Collector Özellikleri

| Özellik | Açıklama |
|---------|----------|
| **Retry** | Bağlantı hatalarında 3x deneme (exponential backoff) |
| **Local Queue** | Offline durumda verileri kaydet |
| **Timestamp** | Orijinal ölçüm zamanı korunur |
| **Auto Sync** | Bağlantı gelince kuyruk otomatik gönderilir |

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
