<?php
/**
 * search_products.php - API untuk pencarian produk di kasir
 */

// Set header JSON
header('Content-Type: application/json');

// Load functions
require_once '../cashier_functions.php';

// Koneksi ke database
$pdo = connectDatabase();

if (!$pdo) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terhubung ke database'
    ]);
    exit;
}

// Proses pencarian
if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
    
    if (empty($keyword)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Kata kunci pencarian tidak boleh kosong'
        ]);
        exit;
    }
    
    $products = searchProductsForCashier($pdo, $keyword);
    
    echo json_encode([
        'status' => 'success',
        'data' => $products
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter pencarian tidak tersedia'
    ]);
}