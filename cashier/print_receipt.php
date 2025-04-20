<?php
/**
 * print_receipt.php - Mencetak struk transaksi
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

// Include file fungsi cashier
require_once '../functions.php';

// Dapatkan koneksi database
$pdo = connectDatabase();

// Cek parameter id
if (!isset($_GET['id'])) {
    echo 'Parameter ID transaksi diperlukan';
    exit;
}

$saleId = intval($_GET['id']);

// Dapatkan data struk
$receiptData = getReceiptData($pdo, $saleId);

if (!$receiptData) {
    echo 'Transaksi tidak ditemukan';
    exit;
}

// Generate HTML struk
$receiptHTML = generateReceiptHTML($receiptData);

// Tampilkan HTML
echo $receiptHTML;
?>
