<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $product_id = $_POST['product_id'] ?? '';
    $nama_produk = $_POST['nama_produk'] ?? '';
    $barcode = $_POST['barcode'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $harga_modal = $_POST['harga_modal'] ?? '';
    $harga_jual = $_POST['harga_jual'] ?? '';
    $expire = $_POST['expire'] ?? '';
    $batch_number = $_POST['batch_number'] ?? '';
    $kode_item = $_POST['kode_item'] ?? null;
    $posisi = $_POST['posisi'] ?? null;
    $unit = $_POST['unit'] ?? null;
    $stok_barang = $_POST['stok_barang'] ?? '';
    $stok_minimum = $_POST['stok_minimum'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? null;

    // Validasi data
    if (empty($product_id) || empty($nama_produk) || empty($harga_modal) || 
        empty($harga_jual) || empty($expire) || empty($stok_barang) || 
        empty($stok_minimum) || empty($supplier_id)) {
        echo "Data tidak lengkap. Pastikan semua field yang wajib diisi telah diisi.";
        exit;
    }

    try {
        $pdo = $farma->getPDO();
        if (!$pdo) {
            throw new Exception("Koneksi database gagal.");
        }

        $pdo->beginTransaction();

        // Step 1: Update data di tabel products
        $queryProduct = "UPDATE products SET 
            product_name = :product_name,
            barcode = :barcode,
            category_id = :category_id,
            cost_price = :cost_price,
            price = :price,
            kode_item = :kode_item,
            posisi = :posisi,
            unit = :unit,
            minimum_stock = :minimum_stock,
            stock_quantity = :stock_quantity,
            updated_at = NOW()
            WHERE product_id = :product_id";
            
        $stmtProduct = $pdo->prepare($queryProduct);
        $stmtProduct->bindParam(':product_name', $nama_produk);
        $stmtProduct->bindParam(':barcode', $barcode);
        $stmtProduct->bindParam(':category_id', $category_id);
        $stmtProduct->bindParam(':cost_price', $harga_modal);
        $stmtProduct->bindParam(':price', $harga_jual);
        $stmtProduct->bindParam(':kode_item', $kode_item);
        $stmtProduct->bindParam(':posisi', $posisi);
        $stmtProduct->bindParam(':unit', $unit);
        $stmtProduct->bindParam(':minimum_stock', $stok_minimum);
        $stmtProduct->bindParam(':stock_quantity', $stok_barang);
        $stmtProduct->bindParam(':product_id', $product_id);
        $stmtProduct->execute();

        // Step 2: Check if batch exists
        $checkBatch = "SELECT COUNT(*) FROM product_batches WHERE product_id = :product_id";
        $stmtCheck = $pdo->prepare($checkBatch);
        $stmtCheck->bindParam(':product_id', $product_id);
        $stmtCheck->execute();
        $batchExists = $stmtCheck->fetchColumn() > 0;

        if ($batchExists) {
            // Update existing batch
            $queryBatch = "UPDATE product_batches SET 
                batch_number = :batch_number,
                expiry_date = :expiry_date,
                quantity = :quantity,
                remaining_quantity = :remaining_quantity,
                supplier_id = :supplier_id,
                updated_at = NOW()
                WHERE product_id = :product_id";
        } else {
            // Insert new batch
            $queryBatch = "INSERT INTO product_batches (
                product_id, batch_number, expiry_date, quantity,
                remaining_quantity, supplier_id, created_at
            ) VALUES (
                :product_id, :batch_number, :expiry_date, :quantity,
                :remaining_quantity, :supplier_id, NOW()
            )";
        }

        $stmtBatch = $pdo->prepare($queryBatch);
        $stmtBatch->bindParam(':product_id', $product_id);
        $stmtBatch->bindParam(':batch_number', $batch_number);
        $stmtBatch->bindParam(':expiry_date', $expire);
        $stmtBatch->bindParam(':quantity', $stok_barang);
        $stmtBatch->bindParam(':remaining_quantity', $stok_barang);
        $stmtBatch->bindParam(':supplier_id', $supplier_id);
        $stmtBatch->execute();

        $pdo->commit();
        header("Location: ../admin/list_barang.php");
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error saat memperbarui produk: " . $e->getMessage());
        echo "Terjadi kesalahan saat memperbarui data: " . $e->getMessage();
    }
} else {
    echo "Akses tidak valid.";
    exit;
}
