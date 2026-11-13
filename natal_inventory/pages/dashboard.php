<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

$pdo = db();
$summary = ['types' => 0, 'needed' => 0, 'available' => 0, 'shortage' => 0, 'shortItems' => 0, 'budget' => 0];
$categoryData = []; $shortItems = []; $activities = []; $budgetReady = false;
try {
    $summary = array_merge($summary, $pdo->query("SELECT COUNT(*) AS types, COALESCE(SUM(quantity_needed),0) AS needed, COALESCE(SUM(quantity_available),0) AS available, COALESCE(SUM(GREATEST(quantity_needed - quantity_available, 0)),0) AS shortage, COALESCE(SUM(quantity_available < quantity_needed),0) AS shortItems FROM items")->fetch() ?: []);
    $categoryData = $pdo->query("SELECT c.category_name, COALESCE(SUM(i.quantity_needed),0) AS total FROM categories c LEFT JOIN items i ON i.category_id = c.id GROUP BY c.id, c.category_name HAVING total > 0 ORDER BY total DESC")->fetchAll();
    $statement = $pdo->query("SELECT i.item_name, i.quantity_needed, i.quantity_available, i.unit, c.category_name, GREATEST(i.quantity_needed - i.quantity_available, 0) AS shortage FROM items i INNER JOIN categories c ON c.id = i.category_id WHERE i.quantity_available < i.quantity_needed ORDER BY shortage DESC, i.item_name ASC LIMIT 6");
    $shortItems = $statement->fetchAll();
    $activities = $pdo->query("SELECT a.activity, a.created_at, u.name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();
    $hasProcurement = (bool) $pdo->query("SHOW COLUMNS FROM items LIKE 'procurement_method'")->fetch();
    if ($hasProcurement) {
        $summary['budget'] = (float) $pdo->query("SELECT COALESCE(SUM(GREATEST(quantity_needed - quantity_available, 0) * unit_price),0) FROM items WHERE procurement_method = 'Beli'")->fetchColumn();
        $budgetReady = true;
    }
} catch (PDOException $exception) {
    error_log('Dashboard query failed: ' . $exception->getMessage());
}
function rupiah(float $value): string { return 'Rp' . number_format($value, 0, ',', '.'); }
function format_date_id(string $date): string { $time = strtotime($date); return $time ? date('d M Y, H:i', $time) : '-'; }
$pageTitle = 'Dashboard | NATAL INVENTORY'; $activePage = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><p class="eyebrow mb-1">RINGKASAN PERLENGKAPAN</p><h1 class="page-title">Dashboard Natal</h1><p class="text-muted mb-0">Pantau kebutuhan perlengkapan acara secara real-time.</p></div>
    <div class="date-pill"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></div>
</div>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-maroon"><i class="bi bi-boxes"></i></span><div><p>Total Jenis Barang</p><h2><?= (int) $summary['types'] ?></h2><small>Barang tercatat</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-blue"><i class="bi bi-clipboard2-check-fill"></i></span><div><p>Total Kebutuhan</p><h2><?= number_format((float) $summary['needed'], 0, ',', '.') ?></h2><small>Satuan kebutuhan</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-green"><i class="bi bi-check2-circle"></i></span><div><p>Barang Tersedia</p><h2><?= number_format((float) $summary['available'], 0, ',', '.') ?></h2><small>Satuan tersedia</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><span class="stat-icon icon-orange"><i class="bi bi-exclamation-triangle-fill"></i></span><div><p>Total Kekurangan</p><h2><?= number_format((float) $summary['shortage'], 0, ',', '.') ?></h2><small><?= (int) $summary['shortItems'] ?> jenis belum terpenuhi</small></div></div></div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-7"><article class="panel h-100"><div class="panel-head"><div><h2>Kebutuhan per Kategori</h2><p>Jumlah kebutuhan barang berdasarkan kategori.</p></div></div><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></article></div>
    <div class="col-lg-5"><article class="panel h-100"><div class="panel-head"><div><h2>Anggaran Pengadaan</h2><p>Estimasi barang dengan metode beli.</p></div><i class="bi bi-wallet2 panel-icon"></i></div><?php if ($budgetReady): ?><div class="budget-number"><?= rupiah((float) $summary['budget']) ?></div><p class="text-muted small mb-0">Dihitung dari kekurangan × harga satuan.</p><?php else: ?><div class="empty-note"><i class="bi bi-info-circle"></i><span>Estimasi anggaran akan aktif setelah kolom metode pengadaan tersedia di tabel barang.</span></div><?php endif; ?></article></div>
</section>

<section class="row g-4">
    <div class="col-xl-7"><article class="panel h-100"><div class="panel-head"><div><h2>Barang Masih Kurang</h2><p>Prioritas pemenuhan kebutuhan acara.</p></div><span class="count-badge"><?= count($shortItems) ?></span></div><?php if ($shortItems): ?><div class="table-responsive"><table class="table inventory-table mb-0"><thead><tr><th>Barang</th><th>Kategori</th><th>Kebutuhan</th><th>Tersedia</th><th>Kekurangan</th></tr></thead><tbody><?php foreach ($shortItems as $item): ?><tr><td class="fw-semibold"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($item['category_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $item['quantity_needed'] ?> <?= htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $item['quantity_available'] ?></td><td><span class="status-badge danger">-<?= (int) $item['shortage'] ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="bi bi-check2-circle"></i><p>Belum ada barang yang perlu dipenuhi.</p></div><?php endif; ?></article></div>
    <div class="col-xl-5"><article class="panel h-100"><div class="panel-head"><div><h2>Aktivitas Terbaru</h2><p>Riwayat aktivitas administrator.</p></div></div><?php if ($activities): ?><div class="activity-list"><?php foreach ($activities as $activity): ?><div class="activity-item"><span><i class="bi bi-activity"></i></span><div><strong><?= htmlspecialchars($activity['activity'], ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($activity['name'] ?: 'Administrator', ENT_QUOTES, 'UTF-8') ?> · <?= format_date_id($activity['created_at']) ?></p></div></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><i class="bi bi-clock-history"></i><p>Belum ada aktivitas tercatat.</p></div><?php endif; ?></article></div>
</section>
<script>window.categoryChartData = <?= json_encode($categoryData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
