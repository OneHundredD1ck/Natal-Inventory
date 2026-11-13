<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();

$summary = $pdo->query("
    SELECT COUNT(*) AS types,
           COALESCE(SUM(quantity_needed), 0) AS needed,
           COALESCE(SUM(quantity_available), 0) AS available,
           COALESCE(SUM(GREATEST(quantity_needed - quantity_available, 0)), 0) AS shortage,
           COALESCE(SUM(quantity_available < quantity_needed), 0) AS short_items
    FROM items
")->fetch() ?: ['types' => 0, 'needed' => 0, 'available' => 0, 'shortage' => 0, 'short_items' => 0];

$byCategory = $pdo->query("
    SELECT c.category_name, COUNT(i.id) AS total_items,
           COALESCE(SUM(i.quantity_needed), 0) AS needed,
           COALESCE(SUM(i.quantity_available), 0) AS available,
           COALESCE(SUM(GREATEST(i.quantity_needed - i.quantity_available, 0)), 0) AS shortage
    FROM categories c
    LEFT JOIN items i ON i.category_id = c.id
    GROUP BY c.id, c.category_name
    ORDER BY c.category_name
")->fetchAll();

$items = $pdo->query("
    SELECT i.*, c.category_name,
           GREATEST(i.quantity_needed - i.quantity_available, 0) AS shortage
    FROM items i
    LEFT JOIN categories c ON c.id = i.category_id
    ORDER BY c.category_name, i.item_name
")->fetchAll();

$activities = $pdo->query("
    SELECT a.activity, a.created_at, u.name
    FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Laporan | NATAL INVENTORY';
$activePage = 'reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="eyebrow mb-1">LAPORAN INVENTARIS</p>
        <h1 class="page-title">Laporan Perlengkapan</h1>
        <p class="text-muted mb-0">Ringkasan menyeluruh inventaris acara.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
        <div class="date-pill"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></div>
    </div>
</div>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-maroon"><i class="bi bi-boxes"></i></span><div><p>Total Jenis</p><h2><?= (int) $summary['types'] ?></h2><small>Barang tercatat</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-blue"><i class="bi bi-clipboard2-check"></i></span><div><p>Total Kebutuhan</p><h2><?= number_format((float) $summary['needed'], 0, ',', '.') ?></h2><small>Satuan</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-green"><i class="bi bi-check2-circle"></i></span><div><p>Tersedia</p><h2><?= number_format((float) $summary['available'], 0, ',', '.') ?></h2><small>Satuan</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-orange"><i class="bi bi-exclamation-triangle"></i></span><div><p>Kekurangan</p><h2><?= number_format((float) $summary['shortage'], 0, ',', '.') ?></h2><small><?= (int) $summary['short_items'] ?> jenis belum terpenuhi</small></div></div></div>
</section>

<section class="panel mb-4">
    <div class="panel-head">
        <div><h2>Rekap per Kategori</h2><p>Ringkasan kebutuhan tiap kategori.</p></div>
    </div>
    <div class="table-responsive">
        <table class="table inventory-table mb-0">
            <thead>
                <tr><th>Kategori</th><th>Jenis Barang</th><th>Kebutuhan</th><th>Tersedia</th><th>Kekurangan</th></tr>
            </thead>
            <tbody>
                <?php if (!$byCategory): ?>
                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-tags"></i><p>Belum ada data kategori.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($byCategory as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($c['category_name']) ?></td>
                        <td><?= (int) $c['total_items'] ?></td>
                        <td><?= number_format((float) $c['needed'], 0, ',', '.') ?></td>
                        <td><?= number_format((float) $c['available'], 0, ',', '.') ?></td>
                        <td><?php if ((int) $c['shortage'] > 0): ?><span class="status-badge danger">-<?= number_format((float) $c['shortage'], 0, ',', '.') ?></span><?php else: ?>0<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel mb-4">
    <div class="panel-head">
        <div><h2>Rincian Barang</h2><p>Seluruh barang terdaftar.</p></div>
        <span class="count-badge"><?= count($items) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table inventory-table mb-0">
            <thead>
                <tr><th>Barang</th><th>Kategori</th><th>Kebutuhan</th><th>Tersedia</th><th>Status</th><th>Metode</th></tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-box"></i><p>Belum ada barang.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $i): ?>
                    <?php [$status, $class] = item_status((int) $i['quantity_needed'], (int) $i['quantity_available']); ?>
                    <tr>
                        <td><div class="fw-semibold"><?= h($i['item_name']) ?></div><small class="text-muted"><?= h($i['specification']) ?></small></td>
                        <td><?= h($i['category_name'] ?? '-') ?></td>
                        <td><?= (int) $i['quantity_needed'] ?> <?= h($i['unit']) ?></td>
                        <td><?= (int) $i['quantity_available'] ?></td>
                        <td><span class="status-badge <?= $class ?>"><?= h($status) ?></span></td>
                        <td><?= h($i['procurement_method']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <div><h2>Aktivitas Terbaru</h2><p>10 riwayat aktivitas terakhir.</p></div>
    </div>
    <?php if ($activities): ?>
        <div class="activity-list">
            <?php foreach ($activities as $a): ?>
                <div class="activity-item">
                    <span><i class="bi bi-activity"></i></span>
                    <div>
                        <strong><?= h($a['activity']) ?></strong>
                        <p><?= h($a['name'] ?? 'Administrator') ?> · <?= h(date('d M Y, H:i', strtotime($a['created_at']))) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class="bi bi-clock-history"></i><p>Belum ada aktivitas.</p></div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>