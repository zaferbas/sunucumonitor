<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Collector'ların metrik gönderirken kullanacağı API anahtarı.
    | Boş bırakılırsa API key kontrolü yapılmaz.
    |
    */
    'api_key' => env('MONITOR_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Verilerin ne kadar süre saklanacağı (gün cinsinden).
    |
    */
    'retention' => [
        'raw_metrics' => env('MONITOR_RETENTION_RAW', 7),      // Ham metrikler
        'processes' => env('MONITOR_RETENTION_PROCESSES', 3),   // Process kayıtları
        'hourly_summary' => env('MONITOR_RETENTION_HOURLY', 90), // Saatlik özetler
        'daily_summary' => env('MONITOR_RETENTION_DAILY', 365),  // Günlük özetler
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Offline Threshold
    |--------------------------------------------------------------------------
    |
    | Sunucunun offline sayılması için geçmesi gereken süre (dakika).
    |
    */
    'offline_threshold' => env('MONITOR_OFFLINE_THRESHOLD', 5),
];
