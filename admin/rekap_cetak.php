<?php
require_once '../includes/cek_login.php';
cek_login('admin');

// Atur mode cetak dan teruskan ke rekap.php
$_GET['cetak'] = '1';
require_once 'rekap.php';