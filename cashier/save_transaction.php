<?php
/**
 * save_transaction.php - API untuk menyimpan transaksi penjualan
 * 
 * File ini menangani permintaan untuk menyimpan transaksi penjualan obat
 * 
 * @version 1.0.0
 * @date 2025-04-17
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
    
    // Terima data JSON dari request
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    // Log the received data for debugging
    error_log("Received data: " . $input);
    
    // Validasi data
    if (!isset($requestData['items']) || empty($requestData['items'])) {
        handleError("Tidak ada item obat yang dipilih", 400);
    }
    
    // Default values jika diperlukan
    if (!isset($requestData['customer_name'])) {
        $requestData['customer_name'] = NULL;
    }
    
    if (!isset($requestData['doctor_id'])) {
        $requestData['doctor_id'] = NULL;
    }
    
    if (!isset($requestData['prescription_number'])) {
        $requestData['prescription_number'] = NULL;
    }
    
    if (!isset($requestData['discount_amount'])) {
        $requestData['discount_amount'] = 0;
    }
    
    if (!isset($requestData['notes'])) {
        $requestData['notes'] = NULL;
    }
    
    // Set payment_status ke 'paid' secara default
    $requestData['payment_status'] = 'paid';
    
    // Validasi obat resep
    $hasPrescriptionMedicines = false;
    
    foreach ($requestData['items'] as $item) {
        $product = getProductById($pdo, $item['product_id']);
        
        if ($product && $product['requires_prescription'] && 
            (!isset($requestData['doctor_id']) || !isset($requestData['prescription_number']))) {
            handleError("Obat resep memerlukan data dokter dan nomor resep", 400);
        }
    }
    
    // Simpan transaksi
    $result = savePharmacyTransaction($pdo, $requestData);
    
    // Kembalikan response
    echo json_encode($result);
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