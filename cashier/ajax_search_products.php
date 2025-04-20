<?php
/**
 * ajax_search_products.php - API untuk mencari produk
 * 
 * @version 1.0.0
 * @date 2025-04-17
 * @updated 2025-04-18 - Ditambahkan dukungan penanganan barcode
 */

// Include file fungsi cashier
require_once 'cashier_functions.php';

// Dapatkan koneksi database
$pdo = connectDatabase();

// Set header JSON
header('Content-Type: application/json');

// Cari produk berdasarkan keyword
if (isset($_GET['keyword'])) {
    $keyword = $_GET['keyword'];
    $isBarcode = isset($_GET['is_barcode']) && $_GET['is_barcode'] == 1;
    
    // Untuk barcode, kita tidak butuh minimal panjang karakter
    // Untuk pencarian biasa, minimal 2 karakter
    if (!$isBarcode && strlen($keyword) < 2) {
        echo json_encode([]);
        exit;
    }
    
    // Jika ini adalah scan barcode, coba cari berdasarkan barcode dulu
    if ($isBarcode) {
        try {
            // Query untuk mencari produk dengan barcode yang sama persis
            $query = "SELECT p.product_id, p.product_name, p.kode_item,
                         p.price, p.posisi, p.stock_quantity, p.unit, p.requires_prescription
                  FROM products p
                  WHERE p.is_active = 1 AND
                        p.barcode = :barcode
                  LIMIT 1";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':barcode', $keyword);
            $stmt->execute();
            
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Jika produk ditemukan berdasarkan barcode, langsung return
            if ($product) {
                echo json_encode([$product]); // Kirim sebagai array dengan satu produk
                exit;
            }
        } catch(PDOException $e) {
            error_log("Database Error (searchByBarcode): " . $e->getMessage());
        }
    }
    
    // Jika tidak ditemukan produk dengan barcode atau bukan pencarian barcode,
    // gunakan fungsi pencarian normal
    $products = searchProductsForCashier($pdo, $keyword);
    echo json_encode($products);
} else {
    echo json_encode([
        'error' => 'Parameter keyword diperlukan'
    ]);
}
?>
