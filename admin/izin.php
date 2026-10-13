<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';
require_once '../includes/fungsi.php';

// Ambil filter status dari GET (default: menunggu)
$filter_status = trim($_GET['status'] ?? 'menunggu');
if (!in_array($filter_status, ['menunggu', 'disetujui', 'ditolak', 'semua'], true)) {
    $filter_status = 'menunggu';
}

// Proses aksi POST: setujui atau tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi    = $_POST['aksi'] ?? '';
    $izin_id = (int) ($_POST['id'] ?? 0);

    if ($izin_id <= 0) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'ID pengajuan tidak valid.'];
        header('Location: izin.php?status=' . urlencode($filter_status));
        exit;
    }

    if ($aksi === 'setujui') {
        try {
            $pdo->beginTransaction();

            // Kunci baris pengajuan izin
            $stmt = $pdo->prepare('SELECT id, user_id, tanggal, jenis, alasan, status FROM izin WHERE id = ? FOR UPDATE');
            $stmt->execute([$izin_id]);
            $izin = $stmt->fetch();

            if (!$izin || $izin['status'] !== 'menunggu') {
                $pdo->rollBack();
                $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Pengajuan tidak ditemukan atau sudah tidak berstatus menunggu.'];
                header('Location: izin.php?status=' . urlencode($filter_status));
                exit;
            }

            // Periksa apakah mahasiswa sudah melakukan presensi masuk pada tanggal tersebut
            $stmt_presensi = $pdo->prepare('SELECT id, jam_masuk FROM presensi WHERE user_id = ? AND tanggal = ?');
            $stmt_presensi->execute([$izin['user_id'], $izin['tanggal']]);
            $presensi_ada = $stmt_presensi->fetch();

            if ($presensi_ada && !empty($presensi_ada['jam_masuk'])) {
                $pdo->rollBack();
                $_SESSION['flash'] = [
                    'tipe' => 'danger',
                    'teks' => 'Persetujuan dibatalkan: Mahasiswa bersangkutan sudah melakukan presensi pada tanggal tersebut.'
                ];
                header('Location: izin.php?status=' . urlencode($filter_status));
                exit;
            }

            // Ubah status izin menjadi disetujui
            $stmt_update = $pdo->prepare('UPDATE izin SET status = "disetujui" WHERE id = ? AND status = "menunggu"');
            $stmt_update->execute([$izin_id]);

            // Buat atau perbarui baris presensi dengan status izin/sakit
            $stmt_upsert = $pdo->prepare(
                'INSERT INTO presensi (user_id, tanggal, jam_masuk, jam_pulang, status, keterangan)
                 VALUES (?, ?, NULL, NULL, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     status = VALUES(status),
                     keterangan = VALUES(keterangan),
                     jam_masuk = NULL,
                     jam_pulang = NULL'
            );
            $stmt_upsert->execute([
                $izin['user_id'],
                $izin['tanggal'],
                $izin['jenis'],
                $izin['alasan']
            ]);

            $pdo->commit();
            $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Pengajuan izin berhasil disetujui dan data presensi telah diperbarui.'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Terjadi kesalahan sistem saat memproses persetujuan.'];
        }
    } elseif ($aksi === 'tolak') {
        $stmt = $pdo->prepare('UPDATE izin SET status = "ditolak" WHERE id = ? AND status = "menunggu"');
        $stmt->execute([$izin_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Pengajuan izin berhasil ditolak.'];
        } else {
            $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Pengajuan tidak ditemukan atau sudah tidak berstatus menunggu.'];
        }
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Aksi tidak dikenal.'];
    }

    header('Location: izin.php?status=' . urlencode($filter_status));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);



// Ambil data pengajuan izin sesuai filter
$sql = 'SELECT i.id, i.tanggal, i.jenis, i.alasan, i.bukti, i.status, i.created_at,
               u.nama, m.nim
        FROM izin i
        JOIN users u ON u.id = i.user_id
        JOIN mahasiswa m ON m.user_id = u.id ';

$params = [];
if ($filter_status !== 'semua') {
    $sql .= 'WHERE i.status = ? ';
    $params[] = $filter_status;
}
$sql .= 'ORDER BY i.created_at DESC, i.tanggal DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_izin = $stmt->fetchAll();

$judul_halaman = 'Persetujuan Izin & Sakit';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Persetujuan Izin & Sakit</h4>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Status -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="status" class="col-form-label col-form-label-sm fw-semibold">Status Pengajuan:</label>
            </div>
            <div class="col-auto">
                <select id="status" name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="menunggu"  <?= $filter_status === 'menunggu'  ? 'selected' : '' ?>>Menunggu</option>
                    <option value="disetujui" <?= $filter_status === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="ditolak"   <?= $filter_status === 'ditolak'   ? 'selected' : '' ?>>Ditolak</option>
                    <option value="semua"     <?= $filter_status === 'semua'     ? 'selected' : '' ?>>Semua Status</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($daftar_izin)): ?>
    <div class="alert alert-info">Tidak ada pengajuan izin/sakit dengan status <?= htmlspecialchars($filter_status) ?>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Alasan</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($daftar_izin as $idx => $row):
                $badge_status = [
                    'menunggu'  => 'warning text-dark',
                    'disetujui' => 'success',
                    'ditolak'   => 'danger',
                ];
                $kelas_badge = $badge_status[$row['status']] ?? 'secondary';
            ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['nim']) ?></td>
                    <td><?= format_tgl_indo($row['tanggal'], $nama_bulan, $nama_hari) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><?= htmlspecialchars($row['alasan']) ?></td>
                    <td>
                        <?php if (!empty($row['bukti'])): ?>
                            <a href="../assets/uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0">
                                Lihat
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $kelas_badge ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'menunggu'): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Setujui pengajuan izin ini?')">
                                <input type="hidden" name="aksi" value="setujui">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm py-0">Setujui</button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Tolak pengajuan izin ini?')">
                                <input type="hidden" name="aksi" value="tolak">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm py-0">Tolak</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>