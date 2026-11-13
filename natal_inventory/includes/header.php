<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activePage */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'NATAL INVENTORY', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
<?php require __DIR__ . '/sidebar.php'; ?>
<main class="admin-main">
    <header class="topbar">
        <button class="btn btn-link d-lg-none text-maroon p-0" type="button" id="sidebarToggle" aria-label="Buka menu"><i class="bi bi-list fs-3"></i></button>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block"><div class="small text-muted">Administrator</div><strong><?= htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <span class="avatar"><i class="bi bi-person-fill"></i></span>
        </div>
    </header>
    <div class="page-content">
