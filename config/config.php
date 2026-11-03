<?php
// ====== KONFIGURASI DATABASE ======
define('DB_HOST', 'localhost');
define('DB_NAME', 'presensi_bps');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Presensi Magang BPS Kota Medan');
date_default_timezone_set('Asia/Jakarta');

// BASE_URL dideteksi otomatis (mis. /presensi-bps). Isi manual jika perlu.
$__root = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
$__doc  = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
define('BASE_URL', rtrim(str_replace($__doc, '', $__root), '/'));
