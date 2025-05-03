<?php
require_once '../functions.php'; // Pastikan file functions.php sudah ada dan termasuk

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $product_id = $_POST['product_id'] ?? '';
    $nama_produk = $_POST['nama_produk'] ?? '';
    $barcode = $_POST['barcode'] ?? null;
    $category_id = $_POST['category_id'] ?? '';
    $harga_modal = $_POST['harga_modal'] ?? '';
    $harga_jual = $_POST['harga_jual'] ?? '';
    $expire = $_POST['expire'] ?? '';
    $batch_number = $_POST['batch_number'] ?? null;
    $kode_item = $_POST['kode_item'] ?? null;
    $posisi = $_POST['posisi'] ?? null;
    $unit = $_POST['unit'] ?? null;
    $stok_barang = $_POST['stok_barang'] ?? '';
    $stok_minimum = $_POST['stok_minimum'] ?? '';
    $supplier_id = $_POST['supplier_id'] ?? null;

    // Validasi data
    if (
        empty($product_id) || empty($nama_produk) || empty($harga_modal) || empty($harga_jual) || empty($expire) || empty($stok_barang) || empty($stok_minimum)
    ) {
        echo "Data tidak lengkap. Pastikan semua field yang wajib diisi telah diisi.";
        exit;
    }

    try {
        $pdo = $farma->getPDO(); // Gunakan getter untuk mengakses $pdo
        if (!$pdo) {
            throw new Exception("Koneksi database gagal.");
        }

        $pdo->beginTransaction(); // Mulai transaksi

        // Step 1: Update data di tabel products
        $queryProduct = "UPDATE products 
                         SET product_name = :product_name, 
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

        // Step 2: Update data di tabel product_batches
        $queryBatch = "UPDATE product_batches 
                       SET batch_number = :batch_number, 
                           expiry_date = :expiry_date, 
                           quantity = :quantity, 
                           remaining_quantity = :remaining_quantity, 
                           supplier_id = :supplier_id, 
                           updated_at = NOW() 
                       WHERE product_id = :product_id";
        $stmtBatch = $pdo->prepare($queryBatch);
        $stmtBatch->bindParam(':batch_number', $batch_number);
        $stmtBatch->bindParam(':expiry_date', $expire);
        $stmtBatch->bindParam(':quantity', $stok_barang);
        $stmtBatch->bindParam(':remaining_quantity', $stok_barang);
        $stmtBatch->bindParam(':supplier_id', $supplier_id);
        $stmtBatch->bindParam(':product_id', $product_id);
        $stmtBatch->execute();

        // Commit transaksi
        $pdo->commit();

        // Redirect atau tampilkan pesan sukses
        header("Location: ../admin/list_barang.php"); // Redirect ke halaman daftar barang
    } catch (Exception $e) {
        // Rollback jika terjadi kesalahan
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
