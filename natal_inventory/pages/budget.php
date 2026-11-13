<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$byCategory = $pdo->query("
    SELECT c.category_name,
           COUNT(i.id) AS item_count,
           COALESCE(SUM(GREATEST(i.quantity_needed - i.quantity_available, 0)), 0) AS shortage_qty,
           COALESCE(SUM(GREATEST(i.quantity_needed - i.quantity_available, 0) * i.unit_price), 0) AS subtotal
    FROM categories c
    LEFT JOIN items i ON i.category_id = c.id AND i.procurement_method = 'Beli'
    GROUP BY c.id, c.category_name
    ORDER BY subtotal DESC
")->fetchAll();

$byMethod = $pdo->query("
    SELECT procurement_method,
           COUNT(*) AS total,
           COALESCE(SUM(quantity_needed), 0) AS needed,
           COALESCE(SUM(quantity_available), 0) AS available,
           COALESCE(SUM(GREATEST(quantity_needed - quantity_available, 0)), 0) AS shortage
    FROM items
    GROUP BY procurement_method
    ORDER BY procurement_method
")->fetchAll();

$totalBudget = (float) $pdo->query("
    SELECT COALESCE(SUM(GREATEST(quantity_needed - quantity_available, 0) * unit_price), 0)
    FROM items WHERE procurement_method = 'Beli'
")->fetchColumn();

$items = $pdo->query("
    SELECT i.*, c.category_name,
           GREATEST(i.quantity_needed - i.quantity_available, 0) AS shortage,
           GREATEST(i.quantity_needed - i.quantity_available, 0) * i.unit_price AS subtotal
    FROM items i
    LEFT JOIN categories c ON c.id = i.category_id
    WHERE i.procurement_method = 'Beli'
    ORDER BY subtotal DESC
")->fetchAll();

function rupiah(float $value): string { return 'Rp' . number_format($value, 0, ',', '.'); }

$pageTitle = 'Anggaran | NATAL INVENTORY';
$activePage = 'budget';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="eyebrow mb-1">PERENCANAAN ANGGARAN</p>
        <h1 class="page-title">Anggaran Pengadaan</h1>
        <p class="text-muted mb-0">Estimasi biaya berdasarkan barang dengan metode beli.</p>
    </div>
    <div class="date-pill"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></div>
</div>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon icon-maroon"><i class="bi bi-wallet2"></i></span>
            <div><p>Total Anggaran</p><h2><?= rupiah($totalBudget) ?></h2><small>Estimasi pengadaan</small></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon icon-blue"><i class="bi bi-bag-check"></i></span>
            <div><p>Jenis Barang Beli</p><h2><?= count($items) ?></h2><small>Barang metode beli</small></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon icon-orange"><i class="bi bi-exclamation-triangle"></i></span>
            <div><p>Barang Kurang</p><h2><?= count(array_filter($items, fn($i) => (int) $i['shortage'] > 0)) ?></h2><small>Perlu ditambah</small></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-icon icon-green"><i class="bi bi-graph-up"></i></span>
            <div><p>Kategori Terdampak</p><h2><?= count(array_filter($byCategory, fn($c) => (float) $c['subtotal'] > 0)) ?></h2><small>Kategori dengan anggaran</small></div>
        </div>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-7">
        <article class="panel h-100">
            <div class="panel-head">
                <div><h2>Anggaran per Kategori</h2><p>Distribusi estimasi biaya per kategori.</p></div>
            </div>
            <div class="table-responsive">
                <table class="table inventory-table mb-0">
                    <thead>
                        <tr><th>Kategori</th><th>Jenis</th><th>Kekurangan</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$byCategory): ?>
                            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-wallet2"></i><p>Belum ada data.</p></div></td></tr>
                        <?php endif; ?>
                        <?php foreach ($byCategory as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?= h($c['category_name']) ?></td>
                                <td><?= (int) $c['item_count'] ?></td>
                                <td><?= number_format((float) $c['shortage_qty'], 0, ',', '.') ?></td>
                                <td class="fw-semibold"><?= rupiah((float) $c['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
    <div class="col-lg-5">
        <article class="panel h-100">
            <div class="panel-head">
                <div><h2>Metode Pengadaan</h2><p>Ringkasan barang per metode.</p></div>
                <i class="bi bi-clipboard-data panel-icon"></i>
            </div>
            <div class="activity-list">
                <?php if (!$byMethod): ?>
                    <div class="empty-state"><i class="bi bi-clipboard-data"></i><p>Belum ada data barang.</p></div>
                <?php endif; ?>
                <?php foreach ($byMethod as $m): ?>
                    <div class="activity-item">
                        <span><i class="bi bi-box"></i></span>
                        <div>
                            <strong><?= h($m['procurement_method']) ?></strong>
                            <p><?= (int) $m['total'] ?> barang · <?= number_format((float) $m['needed'], 0, ',', '.') ?> kebutuhan · <?= number_format((float) $m['shortage'], 0, ',', '.') ?> kurang</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <div><h2>Rincian Anggaran Pengadaan</h2><p>Detail barang dengan metode beli.</p></div>
        <span class="count-badge"><?= count($items) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table inventory-table mb-0">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Kategori</th>
                    <th>Kebutuhan</th>
                    <th>Tersedia</th>
                    <th>Kurang</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>Belum ada barang dengan metode beli.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= h($i['item_name']) ?></div><small class="text-muted"><?= h($i['specification']) ?></small></td>
                        <td><?= h($i['category_name'] ?? '-') ?></td>
                        <td><?= (int) $i['quantity_needed'] ?> <?= h($i['unit']) ?></td>
                        <td><?= (int) $i['quantity_available'] ?></td>
                        <td><?php if ((int) $i['shortage'] > 0): ?><span class="status-badge danger">-<?= (int) $i['shortage'] ?></span><?php else: ?>0<?php endif; ?></td>
                        <td><?= rupiah((float) $i['unit_price']) ?></td>
                        <td class="fw-semibold"><?= rupiah((float) $i['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Total Anggaran</th>
                    <th><?= rupiah($totalBudget) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>