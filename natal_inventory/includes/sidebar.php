<?php
declare(strict_types=1);
$activePage = $activePage ?? '';
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= APP_URL ?>/pages/dashboard.php"><span class="brand-mark"><i class="bi bi-box-seam-fill"></i></span><span>NATAL<br><small>INVENTORY</small></span></a>
    <nav class="nav flex-column mt-4 gap-1">
        <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a class="nav-link <?= $activePage === 'items' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/items.php"><i class="bi bi-boxes"></i> Manajemen Barang</a>
        <a class="nav-link <?= $activePage === 'categories' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/categories.php"><i class="bi bi-tags-fill"></i> Kategori</a>
        <a class="nav-link <?= $activePage === 'budget' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/budget.php"><i class="bi bi-wallet2"></i> Anggaran</a>
        <a class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/reports.php"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan</a>
        <a class="nav-link <?= $activePage === 'activity' ? 'active' : '' ?>" href="<?= APP_URL ?>/pages/activity.php"><i class="bi bi-clock-history"></i> Aktivitas</a>
    </nav>
    <div class="sidebar-bottom"><a class="nav-link logout-link" href="<?= APP_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-left"></i> Keluar</a></div>
</aside>
