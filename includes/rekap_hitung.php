<?php
// Fungsi menghitung rekapitulasi kehadiran dan alfa dinamis mahasiswa
function ambil_rekap(PDO $pdo, int $user_id, string $tgl_dari, string $tgl_sampai): array
{
    $hari_ini = date('Y-m-d');

    // Ambil periode magang mahasiswa
    $stmt = $pdo->prepare('SELECT tgl_mulai, tgl_selesai FROM mahasiswa WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $data_mhs = $stmt->fetch();

    // Ambil data presensi yang sudah tercatat
    $stmt = $pdo->prepare(
        'SELECT tanggal, jam_masuk, jam_pulang, status, keterangan
         FROM presensi
         WHERE user_id = ? AND tanggal BETWEEN ? AND ?'
    );
    $stmt->execute([$user_id, $tgl_dari, $tgl_sampai]);
    $presensi_index = [];
    foreach ($stmt->fetchAll() as $row) {
        $presensi_index[$row['tanggal']] = $row;
    }

    $ringkasan = [
        'hadir'     => 0,
        'terlambat' => 0,
        'izin'      => 0,
        'sakit'     => 0,
        'alfa'      => 0,
    ];

    $cur_tgl = $tgl_dari;
    while ($cur_tgl <= $tgl_sampai) {
        if ($cur_tgl > $hari_ini) {
            $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
            continue;
        }

        $hari_angka   = (int) date('N', strtotime($cur_tgl)); // 1=Senin ... 7=Minggu
        $ada_presensi = isset($presensi_index[$cur_tgl]);

        // Lewati akhir pekan jika tidak ada presensi
        if ($hari_angka >= 6 && !$ada_presensi) {
            $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
            continue;
        }

        if ($ada_presensi) {
            $status = $presensi_index[$cur_tgl]['status'];
            if (isset($ringkasan[$status])) {
                $ringkasan[$status]++;
            }
        } else {
            // Hitung alfa untuk hari kerja lampau dalam rentang magang
            $dalam_periode = $data_mhs
                && ($cur_tgl >= $data_mhs['tgl_mulai'] && $cur_tgl <= $data_mhs['tgl_selesai']);

            if ($hari_angka <= 5 && $dalam_periode && $cur_tgl < $hari_ini) {
                $ringkasan['alfa']++;
            }
        }

        $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
    }

    return $ringkasan;
}