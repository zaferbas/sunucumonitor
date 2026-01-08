#!/usr/bin/env python3
"""
Sunucu Monitor - Collector Script
Sistem metriklerini toplar ve API sunucusuna gönderir.
Offline durumda verileri lokale kaydeder.
"""

import json
import os
import sys
import time
import socket
import logging
from datetime import datetime
from pathlib import Path

try:
    import psutil
    import requests
except ImportError:
    print("Gerekli kütüphaneler yüklü değil. Kurulum:")
    print("pip install psutil requests")
    sys.exit(1)

# Logging ayarları
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Script dizini
SCRIPT_DIR = Path(__file__).parent.absolute()
CONFIG_FILE = SCRIPT_DIR / "config.json"
QUEUE_FILE = SCRIPT_DIR / "queue.json"

# Varsayılan konfigürasyon
DEFAULT_CONFIG = {
    "target_url": "https://your-server.com/api/metrics",
    "api_key": "your-api-key",
    "server_id": socket.gethostname(),
    "interval": 60,
    "top_processes": 10,
    "retry_count": 3,
    "retry_delay": 2,
    "max_queue_size": 1000
}


def load_config():
    """Konfigürasyon dosyasını yükle"""
    if not CONFIG_FILE.exists():
        logger.warning(f"Config dosyası bulunamadı, varsayılan oluşturuluyor: {CONFIG_FILE}")
        save_config(DEFAULT_CONFIG)
        return DEFAULT_CONFIG
    
    with open(CONFIG_FILE, 'r') as f:
        config = json.load(f)
    
    # Eksik anahtarları varsayılanlarla doldur
    for key, value in DEFAULT_CONFIG.items():
        if key not in config:
            config[key] = value
    
    return config


def save_config(config):
    """Konfigürasyonu kaydet"""
    with open(CONFIG_FILE, 'w') as f:
        json.dump(config, f, indent=2)


def load_queue():
    """Offline kuyruğu yükle"""
    if not QUEUE_FILE.exists():
        return []
    
    try:
        with open(QUEUE_FILE, 'r') as f:
            return json.load(f)
    except (json.JSONDecodeError, IOError):
        return []


def save_queue(queue):
    """Kuyruğu kaydet"""
    with open(QUEUE_FILE, 'w') as f:
        json.dump(queue, f)


def get_cpu_metrics():
    """CPU metriklerini topla"""
    cpu_times = psutil.cpu_times_percent(interval=1)
    
    return {
        "percent": psutil.cpu_percent(),
        "count": psutil.cpu_count(),
        "count_logical": psutil.cpu_count(logical=True),
        "user": cpu_times.user,
        "system": cpu_times.system,
        "idle": cpu_times.idle,
        "iowait": getattr(cpu_times, 'iowait', 0),
        "per_cpu": psutil.cpu_percent(percpu=True)
    }


def get_memory_metrics():
    """RAM metriklerini topla"""
    mem = psutil.virtual_memory()
    swap = psutil.swap_memory()
    
    return {
        "total": mem.total,
        "available": mem.available,
        "used": mem.used,
        "percent": mem.percent,
        "buffers": getattr(mem, 'buffers', 0),
        "cached": getattr(mem, 'cached', 0),
        "swap_total": swap.total,
        "swap_used": swap.used,
        "swap_percent": swap.percent
    }


def get_disk_metrics():
    """Disk metriklerini topla"""
    disks = []
    
    for partition in psutil.disk_partitions(all=False):
        try:
            usage = psutil.disk_usage(partition.mountpoint)
            disks.append({
                "device": partition.device,
                "mountpoint": partition.mountpoint,
                "fstype": partition.fstype,
                "total": usage.total,
                "used": usage.used,
                "free": usage.free,
                "percent": usage.percent
            })
        except (PermissionError, OSError):
            continue
    
    return disks


def get_network_metrics():
    """Network metriklerini topla"""
    net_io = psutil.net_io_counters(pernic=True)
    networks = []
    
    for interface, stats in net_io.items():
        # Loopback'i atla
        if interface == 'lo':
            continue
        
        networks.append({
            "interface": interface,
            "bytes_sent": stats.bytes_sent,
            "bytes_recv": stats.bytes_recv,
            "packets_sent": stats.packets_sent,
            "packets_recv": stats.packets_recv,
            "errin": stats.errin,
            "errout": stats.errout
        })
    
    return networks


def get_load_average():
    """Load average al"""
    try:
        load = os.getloadavg()
        return {
            "load_1": load[0],
            "load_5": load[1],
            "load_15": load[2]
        }
    except (OSError, AttributeError):
        # Windows'ta load average yok
        return {"load_1": 0, "load_5": 0, "load_15": 0}


def get_uptime():
    """Sistem uptime"""
    boot_time = psutil.boot_time()
    uptime_seconds = time.time() - boot_time
    return int(uptime_seconds)


def get_processes(top_n=10):
    """En çok kaynak kullanan process'leri al"""
    processes = []
    
    for proc in psutil.process_iter(['pid', 'name', 'username', 'cpu_percent', 
                                      'memory_percent', 'memory_info', 'status', 
                                      'cmdline']):
        try:
            pinfo = proc.info
            processes.append({
                "pid": pinfo['pid'],
                "name": pinfo['name'],
                "username": pinfo['username'] or 'unknown',
                "cpu_percent": pinfo['cpu_percent'] or 0,
                "memory_percent": pinfo['memory_percent'] or 0,
                "memory_rss": pinfo['memory_info'].rss if pinfo['memory_info'] else 0,
                "status": pinfo['status'],
                "command": ' '.join(pinfo['cmdline'][:5]) if pinfo['cmdline'] else pinfo['name']
            })
        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            continue
    
    # CPU + Memory'ye göre sırala ve top N al
    processes.sort(key=lambda x: x['cpu_percent'] + x['memory_percent'], reverse=True)
    return processes[:top_n]


def get_user_summary():
    """Kullanıcı bazlı kaynak kullanımı özeti"""
    users = {}
    
    for proc in psutil.process_iter(['username', 'cpu_percent', 'memory_percent']):
        try:
            pinfo = proc.info
            username = pinfo['username'] or 'unknown'
            
            if username not in users:
                users[username] = {
                    "username": username,
                    "process_count": 0,
                    "cpu_percent": 0,
                    "memory_percent": 0
                }
            
            users[username]['process_count'] += 1
            users[username]['cpu_percent'] += pinfo['cpu_percent'] or 0
            users[username]['memory_percent'] += pinfo['memory_percent'] or 0
        except (psutil.NoSuchProcess, psutil.AccessDenied):
            continue
    
    # CPU'ya göre sırala
    return sorted(users.values(), key=lambda x: x['cpu_percent'], reverse=True)


def collect_metrics(config):
    """Tüm metrikleri topla"""
    return {
        "server_id": config['server_id'],
        "hostname": socket.gethostname(),
        "timestamp": datetime.utcnow().isoformat() + 'Z',
        "uptime": get_uptime(),
        "load_average": get_load_average(),
        "cpu": get_cpu_metrics(),
        "memory": get_memory_metrics(),
        "disks": get_disk_metrics(),
        "networks": get_network_metrics(),
        "processes": get_processes(config['top_processes']),
        "users": get_user_summary()
    }


def send_metrics(config, data, is_queued=False):
    """Metrikleri API'ye gönder"""
    headers = {
        "Content-Type": "application/json",
        "X-API-Key": config['api_key'],
        "X-Server-ID": config['server_id']
    }
    
    retry_count = config['retry_count']
    retry_delay = config['retry_delay']
    
    for attempt in range(retry_count):
        try:
            response = requests.post(
                config['target_url'],
                json=data,
                headers=headers,
                timeout=30
            )
            
            if response.status_code in (200, 201):
                source = "kuyruktan" if is_queued else "anlık"
                logger.info(f"Metrikler gönderildi ({source})")
                return True
            else:
                logger.warning(f"API hatası: {response.status_code} - {response.text}")
        
        except requests.exceptions.RequestException as e:
            logger.warning(f"Bağlantı hatası (deneme {attempt + 1}/{retry_count}): {e}")
        
        if attempt < retry_count - 1:
            sleep_time = retry_delay * (2 ** attempt)  # Exponential backoff
            logger.info(f"{sleep_time} saniye bekleniyor...")
            time.sleep(sleep_time)
    
    return False


def process_queue(config):
    """Kuyruktaki verileri gönder"""
    queue = load_queue()
    
    if not queue:
        return
    
    logger.info(f"Kuyrukta {len(queue)} kayıt var, gönderiliyor...")
    
    sent_count = 0
    new_queue = []
    
    for item in queue:
        if send_metrics(config, item, is_queued=True):
            sent_count += 1
        else:
            new_queue.append(item)
            break  # Bağlantı hala yok, kuyruğu bırak
    
    # Kalanları geri kuyruğa ekle
    new_queue.extend(queue[sent_count + len(new_queue):])
    save_queue(new_queue)
    
    if sent_count > 0:
        logger.info(f"{sent_count} kayıt kuyruktan gönderildi")


def add_to_queue(config, data):
    """Veriyi kuyruğa ekle"""
    queue = load_queue()
    
    # Max queue size kontrolü
    if len(queue) >= config['max_queue_size']:
        logger.warning(f"Kuyruk dolu ({config['max_queue_size']}), en eski kayıt siliniyor")
        queue.pop(0)
    
    queue.append(data)
    save_queue(queue)
    logger.info(f"Veri kuyruğa eklendi. Kuyruk boyutu: {len(queue)}")


def run_once(config):
    """Tek seferlik çalıştır"""
    logger.info("Metrikler toplanıyor...")
    metrics = collect_metrics(config)
    
    # Önce kuyruğu işle
    process_queue(config)
    
    # Şimdiki veriyi gönder
    if not send_metrics(config, metrics):
        add_to_queue(config, metrics)


def run_daemon(config):
    """Sürekli çalış"""
    logger.info(f"Daemon başlatıldı. Interval: {config['interval']} saniye")
    
    while True:
        try:
            run_once(config)
        except Exception as e:
            logger.error(f"Hata: {e}")
        
        time.sleep(config['interval'])


def dry_run(config):
    """Test modu - sadece JSON çıktısı ver"""
    metrics = collect_metrics(config)
    print(json.dumps(metrics, indent=2, default=str))


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description='Sunucu Monitor Collector')
    parser.add_argument('--dry-run', action='store_true', help='Sadece JSON çıktısı ver, gönderme')
    parser.add_argument('--once', action='store_true', help='Tek seferlik çalıştır')
    parser.add_argument('--test', action='store_true', help='Bağlantı testi yap')
    
    args = parser.parse_args()
    config = load_config()
    
    if args.dry_run:
        dry_run(config)
    elif args.test:
        logger.info("Bağlantı testi yapılıyor...")
        metrics = collect_metrics(config)
        if send_metrics(config, metrics):
            logger.info("✓ Bağlantı başarılı!")
        else:
            logger.error("✗ Bağlantı başarısız!")
            sys.exit(1)
    elif args.once:
        run_once(config)
    else:
        run_daemon(config)


if __name__ == "__main__":
    main()
