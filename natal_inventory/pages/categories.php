<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$rows = db()->query('SELECT c.*, COUNT(i.id) AS total FROM categories c LEFT JOIN items i ON i.category_id = c.id GROUP BY c.id ORDER BY c.category_name')->fetchAll();

$pageTitle = 'Kategori | NATAL INVENTORY';
$activePage = 'categories';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">Kategori</h1>
        <p class="text-muted mb-0">Kelola kelompok barang perlengkapan acara.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addCategory">
        <i class="bi bi-plus-lg"></i> Tambah Kategori
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
                <tr><th>Kategori</th><th>Jumlah Barang</th><th>Dibuat</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="4"><div class="empty-state"><i class="bi bi-tags"></i><p>Belum ada kategori.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($r['category_name']) ?></td>
                        <td><span class="count-badge"><?= (int) $r['total'] ?></span></td>
                        <td><?= h($r['created_at']) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit<?= (int) $r['id'] ?>">Edit</button>
                            <form class="d-inline" method="post" action="<?= APP_URL ?>/actions/category_delete.php" onsubmit="return confirm('Hapus kategori ini?')">
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

<?php foreach ($rows as $r): ?>
    <div class="modal fade" id="edit<?= (int) $r['id'] ?>">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= APP_URL ?>/actions/category_edit.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <label class="form-label">Nama Kategori</label>
                    <input class="form-control" name="category_name" value="<?= h($r['category_name']) ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="addCategory">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= APP_URL ?>/actions/category_add.php">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="form-label">Nama Kategori</label>
                <input class="form-control" name="category_name" required maxlength="100">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger">Tambah</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>