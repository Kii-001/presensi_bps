<?php
$nama_hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$nama_bulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function format_tgl_indo($tgl_str, $nama_bulan, $nama_hari = null)
{
    $ts = strtotime($tgl_str);
    $d  = (int) date('j', $ts);
    $m  = (int) date('n', $ts);
    $y  = (int) date('Y', $ts);
    $teks = $d . ' ' . $nama_bulan[$m] . ' ' . $y;
    if ($nama_hari !== null) {
        $h = (int) date('N', $ts);
        $teks = $nama_hari[$h] . ', ' . $teks;
    }
    return $teks;
}