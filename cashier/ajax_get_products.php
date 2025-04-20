<?php
/**
 * ajax_get_products.php - API untuk mendapatkan produk berdasarkan kategori
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

// Include file fungsi cashier
require_once '../functions.php';


// Set header JSON
header('Content-Type: application/json');

// Ambil produk berdasarkan kategori
if (isset($_GET['category_id'])) {
    $categoryId = intval($_GET['category_id']);
    
    if ($categoryId === 0) {
        // Tampilkan produk populer jika kategori "Semua"
        $products = $farma->getPopularProductsForCashier(50);
    } else {
        // Tampilkan produk berdasarkan kategori
        $products = $farma->getProductsByCategoryForCashier($categoryId);
    }
    
    echo json_encode($products);
} else {
    echo json_encode([
        'error' => 'Parameter category_id diperlukan'
    ]);
}
?>
