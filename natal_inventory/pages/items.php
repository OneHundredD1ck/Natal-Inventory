<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name')->fetchAll();
$items = $pdo->query('SELECT i.*, c.category_name FROM items i LEFT JOIN categories c ON c.id = i.category_id ORDER BY i.item_name')->fetchAll();

function rupiah(float $value): string { return 'Rp' . number_format($value, 0, ',', '.'); }

$pageTitle = 'Manajemen Barang | NATAL INVENTORY';
$activePage = 'items';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">Manajemen Barang</h1>
        <p class="text-muted mb-0">Kelola daftar perlengkapan dan kebutuhan acara.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addItem">
        <i class="bi bi-plus-lg"></i> Tambah Barang
    </button>
</div>

<?php foreach (['success' => 'success', 'error' => 'danger'] as $k => $c): ?>
    <?php if ($m = flash($k)): ?>
        <div class="alert alert-<?= $c ?>"><?= h($m) ?></div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="panel">
    <div class="table-responsive">
        <table class="table inventory-table mb-0">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Kategori</th>
                    <th>Kebutuhan</th>
                    <th>Tersedia</th>
                    <th>Status</th>
                    <th>Metode</th>
                    <th>Harga Satuan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-box"></i><p>Belum ada barang. Tambahkan barang pertama.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $r): ?>
                    <?php [$status, $class] = item_status((int) $r['quantity_needed'], (int) $r['quantity_available']); ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= h($r['item_name']) ?></div>
                            <small class="text-muted"><?= h($r['specification']) ?></small>
                        </td>
                        <td><?= h($r['category_name'] ?? '-') ?></td>
                        <td><?= (int) $r['quantity_needed'] ?> <?= h($r['unit']) ?></td>
                        <td><?= (int) $r['quantity_available'] ?> <?= h($r['unit']) ?></td>
                        <td><span class="status-badge <?= $class ?>"><?= h($status) ?></span></td>
                        <td><?= h($r['procurement_method']) ?></td>
                        <td><?= rupiah((float) $r['unit_price']) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit<?= (int) $r['id'] ?>">Edit</button>
                            <form class="d-inline" method="post" action="<?= APP_URL ?>/actions/item_delete.php" onsubmit="return confirm('Hapus barang ini?')">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($items as $r): ?>
    <div class="modal fade" id="edit<?= (int) $r['id'] ?>">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" method="post" action="<?= APP_URL ?>/actions/item_save.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Barang</label>
                            <input class="form-control" name="item_name" value="<?= h($r['item_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $r['category_id'] ? 'selected' : '' ?>><?= h($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Spesifikasi</label>
                            <input class="form-control" name="specification" value="<?= h($r['specification']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Kebutuhan</label>
                            <input class="form-control" type="number" min="0" name="quantity_needed" value="<?= (int) $r['quantity_needed'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Tersedia</label>
                            <input class="form-control" type="number" min="0" name="quantity_available" value="<?= (int) $r['quantity_available'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input class="form-control" name="unit" value="<?= h($r['unit']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pengadaan</label>
                            <select class="form-select" name="procurement_method" required>
                                <?php foreach (['Beli', 'Pinjam', 'Inventaris'] as $m): ?>
                                    <option value="<?= $m ?>" <?= $r['procurement_method'] === $m ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Satuan</label>
                            <input class="form-control" type="number" min="0" step="0.01" name="unit_price" value="<?= (float) $r['unit_price'] ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" name="notes" rows="2"><?= h($r['notes']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="addItem">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post" action="<?= APP_URL ?>/actions/item_save.php">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Barang</label>
                        <input class="form-control" name="item_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= h($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Spesifikasi</label>
                        <input class="form-control" name="specification" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jumlah Kebutuhan</label>
                        <input class="form-control" type="number" min="0" name="quantity_needed" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jumlah Tersedia</label>
                        <input class="form-control" type="number" min="0" name="quantity_available" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <input class="form-control" name="unit" placeholder="pcs / box / unit" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Metode Pengadaan</label>
                        <select class="form-select" name="procurement_method" required>
                            <option value="Beli">Beli</option>
                            <option value="Pinjam">Pinjam</option>
                            <option value="Inventaris">Inventaris</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Satuan</label>
                        <input class="form-control" type="number" min="0" step="0.01" name="unit_price" value="0" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger">Tambah</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>