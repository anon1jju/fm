<?php
/**
 * save_transaction.php - API untuk menyimpan transaksi penjualan
 * 
 * File ini menangani permintaan untuk menyimpan transaksi penjualan obat
 * 
 * @version 1.1.0
 * @date 2025-04-20
 */

// Include file fungsi
require_once '../functions.php';

// API endpoint untuk menyimpan transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Dapatkan koneksi database
    $pdo = connectDatabase();
    
    if (!$pdo) {
        handleError("Koneksi database gagal", 500);
    }
    
    // Mulai transaksi database
    $pdo->beginTransaction();
    
    try {
        // Terima data JSON dari request
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);
        
        // Validasi data
        if (!isset($requestData['items']) || empty($requestData['items'])) {
            throw new Exception("Tidak ada item obat yang dipilih", 400);
        }
        
        // Validasi dan update stok
        foreach ($requestData['items'] as $item) {
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = :product_id FOR UPDATE");
            $stmt->execute([':product_id' => $item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Produk dengan ID {$item['product_id']} tidak ditemukan", 404);
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Stok produk {$item['product_id']} tidak mencukupi", 400);
            }
            
            // Update stok
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE product_id = :product_id");
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
        
        // Simpan transaksi (implementasi fungsi savePharmacyTransaction harus mendukung transaksi)
        $result = savePharmacyTransaction($pdo, $requestData);
        
        // Commit transaksi
        $pdo->commit();
        
        // Kembalikan response
        echo json_encode($result);
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $pdo->rollBack();
        handleError($e->getMessage(), $e->getCode() ?: 500);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Respond with a simple test message for GET requests
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "message" => "API Transaction Service is running. Please use POST method to save transactions."
    ]);
} else {
    // Respond with method not allowed for other request types
    header('Content-Type: application/json');
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode([
        "success" => false,
        "message" => "Method Not Allowed. Use POST to save transactions."
    ]);
}
?>
