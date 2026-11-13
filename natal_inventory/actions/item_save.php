<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
require_post_csrf();

$id = (int) ($_POST['id'] ?? 0);

$categoryId   = (int) ($_POST['category_id'] ?? 0);
$itemName     = trim((string) ($_POST['item_name'] ?? ''));
$specification= trim((string) ($_POST['specification'] ?? ''));
$unit         = trim((string) ($_POST['unit'] ?? ''));
$method       = (string) ($_POST['procurement_method'] ?? '');
$notes        = trim((string) ($_POST['notes'] ?? ''));

$qtyNeeded    = filter_var($_POST['quantity_needed'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$qtyAvailable = filter_var($_POST['quantity_available'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$unitPrice    = filter_var($_POST['unit_price'] ?? null, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

$validMethods = ['Beli', 'Pinjam', 'Inventaris'];

if (
    $categoryId < 1
    || $itemName === ''
    || $specification === ''
    || $unit === ''
    || $qtyNeeded === false
    || $qtyAvailable === false
    || $unitPrice === false
    || !in_array($method, $validMethods, true)
) {
    flash('error', 'Lengkapi data barang dengan nilai jumlah dan harga yang valid.');
    redirect_to('/pages/items.php');
}

try {
    $pdo = db();

    // Pastikan kategori benar-benar ada (dump belum tentu punya foreign key)
    $check = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
    $check->execute([$categoryId]);
    if (!$check->fetchColumn()) {
        flash('error', 'Kategori tidak ditemukan.');
        redirect_to('/pages/items.php');
    }

    $data = [
        'category_id'        => $categoryId,
        'item_name'          => $itemName,
        'specification'      => $specification,
        'quantity_needed'    => (int) $qtyNeeded,
        'quantity_available' => (int) $qtyAvailable,
        'unit'               => $unit,
        'procurement_method' => $method,
        'unit_price'         => (float) $unitPrice,
        'notes'              => $notes,
    ];

    if ($id > 0) {
        $sql = 'UPDATE items SET
                    category_id = :category_id,
                    item_name = :item_name,
                    specification = :specification,
                    quantity_needed = :quantity_needed,
                    quantity_available = :quantity_available,
                    unit = :unit,
                    procurement_method = :procurement_method,
                    unit_price = :unit_price,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id';
        $data['id'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        if ($stmt->rowCount() === 0) {
            // Bisa jadi data tidak ada, atau nilai sama persis. Cek keberadaan.
            $exists = $pdo->prepare('SELECT 1 FROM items WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                flash('error', 'Barang tidak ditemukan.');
                redirect_to('/pages/items.php');
            }
        }

        $activity = 'Mengubah barang: ' . $itemName;
        $message  = 'Barang berhasil diperbarui.';
    } else {
        $sql = 'INSERT INTO items
                    (category_id, item_name, specification, quantity_needed, quantity_available,
                     unit, procurement_method, unit_price, notes, created_at, updated_at)
                VALUES
                    (:category_id, :item_name, :specification, :quantity_needed, :quantity_available,
                     :unit, :procurement_method, :unit_price, :notes, NOW(), NOW())';
        $pdo->prepare($sql)->execute($data);

        $activity = 'Menambahkan barang: ' . $itemName;
        $message  = 'Barang berhasil ditambahkan.';
    }

    log_activity($pdo, (int) $_SESSION['user_id'], $activity);
    flash('success', $message);
} catch (PDOException $e) {
    error_log('Item save failed: ' . $e->getMessage());
    flash('error', 'Barang tidak dapat disimpan.');
}

redirect_to('/pages/items.php');