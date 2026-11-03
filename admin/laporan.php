<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$jenis = $_GET['jenis'] ?? 'harian';
if (!in_array($jenis, ['harian', 'bulanan', 'universitas', 'terlambat'], true)) $jenis = 'harian';
$tg = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : date('Y-m-d');
$bln = preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');
$from = $bln . '-01'; $to = date('Y-m-t', strtotime($from));
$mhs = q("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama")->fetchAll();
$head = []; $data = []; $judul = '';

if ($jenis === 'harian') {
    $judul = 'Laporan Harian - ' . tgl($tg, true);
    $head = ['Mahasiswa', 'NIM', 'Universitas', 'Clock In', 'Clock Out', 'Status'];
    $byUser = []; foreach (q('SELECT * FROM presensi WHERE tanggal=?', [$tg])->fetchAll() as $p) $byUser[$p['user_id']] = $p;
    foreach ($mhs as $m) {
        if (($m['mulai_magang'] && $tg < $m['mulai_magang']) || ($m['selesai_magang'] && $tg > $m['selesai_magang'])) continue;
        $p = $byUser[$m['id']] ?? null;
        if ($p) $s = $p['status']; elseif (date('N', strtotime($tg)) >= 6) $s = 'libur'; else $s = $tg < date('Y-m-d') ? 'alpa' : 'belum';
        $data[] = [$m['nama'], $m['nim'], $m['universitas'], jam($p['jam_masuk'] ?? null), jam($p['jam_pulang'] ?? null), $s];
    }
} elseif ($jenis === 'bulanan') {
    $judul = 'Rekap Bulanan - ' . BULAN[(int)date('n', strtotime($from))] . ' ' . date('Y', strtotime($from));
    $head = ['Mahasiswa', 'NIM', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Kehadiran'];
    foreach ($mhs as $m) { $r = rekap_user($m, $from, $to); $tot = array_sum($r);
        $data[] = [$m['nama'], $m['nim'], $r['hadir'], $r['terlambat'], $r['izin'], $r['sakit'], $r['alpa'], $tot ? round(($r['hadir'] + $r['terlambat']) / $tot * 100) . '%' : '-']; }
} elseif ($jenis === 'universitas') {
    $judul = 'Rekap per Universitas - ' . BULAN[(int)date('n', strtotime($from))] . ' ' . date('Y', strtotime($from));
    $head = ['Universitas', 'Jumlah mahasiswa', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa']; $g = [];
    foreach ($mhs as $m) { $k = $m['universitas'] ?: '(Belum diisi)'; $r = rekap_user($m, $from, $to);
        $g[$k] ??= [0, 0, 0, 0, 0, 0]; $g[$k][0]++; $g[$k][1] += $r['hadir']; $g[$k][2] += $r['terlambat']; $g[$k][3] += $r['izin']; $g[$k][4] += $r['sakit']; $g[$k][5] += $r['alpa']; }
    foreach ($g as $k => $v) $data[] = array_merge([$k], $v);
} else {
    $judul = 'Keterlambatan - ' . BULAN[(int)date('n', strtotime($from))] . ' ' . date('Y', strtotime($from));
    $head = ['Tanggal', 'Mahasiswa', 'Universitas', 'Clock In', 'Terlambat (menit)'];
    foreach (q("SELECT p.*, u.nama, u.universitas FROM presensi p JOIN users u ON u.id=p.user_id WHERE p.status='terlambat' AND p.tanggal BETWEEN ? AND ? ORDER BY p.tanggal DESC, p.jam_masuk", [$from, $to])->fetchAll() as $p) {
        $menit = max(0, (int)round((strtotime($p['jam_masuk']) - strtotime(setting('jam_masuk', '07:30') . ':00')) / 60));
        $data[] = [tgl($p['tanggal']), $p['nama'], $p['universitas'], jam($p['jam_masuk']), $menit]; }
}
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="laporan-' . $jenis . '.csv"');
    echo "\xEF\xBB\xBF"; $o = fopen('php://output', 'w'); fputcsv($o, $head, ';'); foreach ($data as $r) fputcsv($o, $r, ';'); exit;
}
$tabs = ['harian' => 'Harian', 'bulanan' => 'Rekap Bulanan', 'universitas' => 'Per Universitas', 'terlambat' => 'Keterlambatan'];
page_start('Laporan', 'laporan');
?>
<div class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $jenis === $k ? 'on' : '' ?>" href="?jenis=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>
<div class="card"><form class="filter" method="get"><input type="hidden" name="jenis" value="<?= e($jenis) ?>">
<?php if ($jenis === 'harian'): ?><div><label for="t">Tanggal</label><input id="t" type="date" name="tanggal" value="<?= e($tg) ?>"></div>
<?php else: ?><div><label for="b">Bulan</label><input id="b" type="month" name="bulan" value="<?= e($bln) ?>"></div><?php endif; ?>
<button class="btn">Tampilkan</button><a class="btn btn-line" href="?<?= e(http_build_query(['jenis' => $jenis, 'tanggal' => $tg, 'bulan' => $bln, 'export' => 'csv'])) ?>">Export CSV</a><button type="button" class="btn btn-line" onclick="window.print()">Cetak</button></form>
<h2 style="margin-bottom:12px"><?= e($judul) ?></h2>
<div class="tw"><table><thead><tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($data as $r): ?><tr><?php foreach ($r as $i => $c): ?><td><?= ($jenis === 'harian' && $i === 5) ? badge($c) : e($c) ?></td><?php endforeach; ?></tr>
<?php endforeach; if (!$data): ?><tr><td colspan="<?= count($head) ?>" class="empty">Tidak ada data untuk periode ini.</td></tr><?php endif; ?></tbody></table></div>
<p class="muted small" style="margin:12px 0 0">Alpa dihitung dari hari kerja (Senin-Jumat) tanpa presensi atau izin. Hari libur nasional belum dikecualikan.</p></div>
<?php page_end();
