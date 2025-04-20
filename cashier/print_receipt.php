<?php
/**
 * print_receipt.php - Mencetak struk transaksi
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

// Include file fungsi cashier
require_once '../functions.php';

// Cek parameter id
if (!isset($_GET['id'])) {
    echo 'Parameter ID transaksi diperlukan';
    exit;
}

$saleId = intval($_GET['id']);

// Dapatkan data struk
$receiptData = $farma->getReceiptData($saleId);

if (!$receiptData) {
    echo 'Transaksi tidak ditemukan';
    exit;
}

// Generate HTML struk
$receiptHTML = $farma->generateReceiptHTML($receiptData);

// Tampilkan HTML
echo $receiptHTML;
?>
