<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_produk = $_POST['nama_produk'] ?? '';
    $barcode = $_POST['barcode'] ?? null;
    $category_id = $_POST['category_id'] ?? null; // Changed to null as default
    $harga_modal = $_POST['harga_modal'] ?? '';
    $harga_jual = $_POST['harga_jual'] ?? '';
    $expire = $_POST['expire'] ?? '';
    $batch_number = $_POST['batch_number'] ?? null; // Changed to null as default
    $kode_item = $_POST['kode_item'] ?? null;
    $posisi = $_POST['posisi'] ?? null;
    $unit = $_POST['unit'] ?? null; // Changed to null as default
    $stok_barang = $_POST['stok_barang'] ?? '';
    $stok_minimum = $_POST['stok_minimum'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? null; // Changed to null as default

    // Validasi data (removed optional fields)
    if (empty($nama_produk) || empty($harga_modal) || empty($harga_jual) || empty($expire) || empty($stok_barang) || empty($stok_minimum)) {
        echo "Data tidak lengkap. Pastikan semua field yang wajib diisi telah diisi.";
        exit;
    }

    try {
        $pdo = $farma->getPDO();
        if (!$pdo) {
            throw new Exception("Koneksi database gagal.");
        }

        $pdo->beginTransaction();

        // Step 1: Simpan data ke tabel products
        $queryProduct = "INSERT INTO products (product_name, barcode, category_id, cost_price, price, kode_item, posisi, unit, minimum_stock, stock_quantity, is_active, created_at) 
                         VALUES (:product_name, :barcode, :category_id, :cost_price, :price, :kode_item, :posisi, :unit, :minimum_stock, :stock_quantity, 1, NOW())";
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
        $stmtProduct->execute();
        $product_id = $pdo->lastInsertId();

        // Step 2: Simpan data ke tabel product_batches (only if batch_number is provided)
        if ($batch_number !== null) {
            $queryBatch = "INSERT INTO product_batches (product_id, batch_number, expiry_date, quantity, remaining_quantity, supplier_id, created_at) 
                          VALUES (:product_id, :batch_number, :expiry_date, :quantity, :remaining_quantity, :supplier_id, NOW())";
            $stmtBatch = $pdo->prepare($queryBatch);
            $stmtBatch->bindParam(':product_id', $product_id);
            $stmtBatch->bindParam(':batch_number', $batch_number);
            $stmtBatch->bindParam(':expiry_date', $expire);
            $stmtBatch->bindParam(':quantity', $stok_barang);
            $stmtBatch->bindParam(':remaining_quantity', $stok_barang);
            $stmtBatch->bindParam(':supplier_id', $supplier_id);
            $stmtBatch->execute();
        }

        $pdo->commit();
        header("Location: ../admin/list_barang.php");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error saat menambahkan produk: " . $e->getMessage());
        echo "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
    }
} else {
    echo "Akses tidak valid.";
    exit;
}
