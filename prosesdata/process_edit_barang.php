<?php
require_once '../functions.php'; // Pastikan file functions.php sudah ada dan termasuk

// Sebaiknya gunakan session untuk pesan feedback daripada echo langsung
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data product_id, ini wajib ada
    $product_id = $_POST['product_id'] ?? '';

    if (empty($product_id)) {
        // Sebaiknya redirect dengan pesan error
        $_SESSION['error_message'] = "ID Produk tidak valid atau tidak disertakan.";
        header("Location: ../admin/list_barang.php");
        exit;
    }

    // Ambil semua data POST yang mungkin ada
    $post_data = $_POST;

    // Validasi data bawaan Anda (mungkin perlu disesuaikan dengan logika baru)
    // Jika sebuah field sekarang opsional untuk diupdate, validasi ini mungkin terlalu ketat.
    // Misalnya, jika 'stok_barang' tidak diisi karena menggunakan 'jumlah_perubahan_stok'.
    if (
        empty($post_data['nama_produk']) || 
        empty($post_data['harga_modal']) || 
        empty($post_data['harga_jual']) || 
        // empty($post_data['expire']) || // 'expire' untuk product_batches, mungkin tidak selalu diisi
        // empty($post_data['stok_barang']) || // 'stok_barang' mungkin tidak diisi jika pakai 'jumlah_perubahan_stok'
        empty($post_data['stok_minimum'])
    ) {
        // Komentar: Validasi ini mungkin perlu Anda sesuaikan.
        // Jika field seperti nama_produk kosong, dengan logika baru, field tersebut tidak akan diupdate.
        // Namun, validasi ini akan menghentikan proses.
        // Untuk sementara, saya akan membiarkannya sesuai permintaan "jangan dikurangi",
        // tapi Anda mungkin ingin menghapus atau melonggarkan beberapa pemeriksaan 'empty' di sini.
        $_SESSION['error_message'] = "Data wajib (seperti Nama Produk, Harga, Stok Minimum) ada yang kosong menurut validasi awal.";
        // echo "Data tidak lengkap. Pastikan semua field yang wajib diisi telah diisi (sesuai validasi awal).";
        header("Location: ../admin/list_barang.php");
        exit;
    }


    try {
        $pdo = $farma->getPDO(); 
        if (!$pdo) {
            throw new Exception("Koneksi database gagal.");
        }

        $pdo->beginTransaction(); 

        // --- Logic untuk update tabel 'products' ---
        $update_fields_product = [];
        $params_product = [];

        // Ambil stok saat ini untuk perhitungan jika ada perubahan stok
        $stmt_current_stock = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = :product_id");
        $stmt_current_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt_current_stock->execute();
        $current_product_info = $stmt_current_stock->fetch(PDO::FETCH_ASSOC);

        if (!$current_product_info) {
            throw new Exception("Produk dengan ID ".$product_id." tidak ditemukan.");
        }
        $stok_saat_ini = (int)$current_product_info['stock_quantity'];
        $new_stock_value_to_set = null; // Akan diisi jika ada update stok

        // Logika perhitungan stok baru
        $jumlah_perubahan_stok = isset($post_data['jumlah_perubahan_stok']) ? (int)$post_data['jumlah_perubahan_stok'] : 0;
        $stok_barang_langsung = $post_data['stok_barang'] ?? '';

        if ($jumlah_perubahan_stok > 0) {
            $operasi_stok_key = 'operasi_stok_' . $product_id; // Sesuaikan jika nama di form berbeda
            if (!isset($post_data[$operasi_stok_key])) $operasi_stok_key = 'operasi_stok'; // Fallback

            $operasi_stok = isset($post_data[$operasi_stok_key]) ? $post_data[$operasi_stok_key] : 'tambah';
            
            if ($operasi_stok === 'tambah') {
                $new_stock_value_to_set = $stok_saat_ini + $jumlah_perubahan_stok;
            } elseif ($operasi_stok === 'kurang') {
                $new_stock_value_to_set = $stok_saat_ini - $jumlah_perubahan_stok;
                if ($new_stock_value_to_set < 0) $new_stock_value_to_set = 0; // Stok tidak boleh minus
            }
        } elseif (trim($stok_barang_langsung) !== '' && is_numeric($stok_barang_langsung)) {
            // Jika tidak ada 'jumlah_perubahan_stok', tapi 'stok_barang' diisi langsung
            $new_stock_value_to_set = (int)$stok_barang_langsung;
        }

        // Field mapping untuk tabel 'products' (key: nama di $_POST, value: nama kolom DB)
        $product_field_map = [
            'nama_produk'   => 'product_name',
            'barcode'       => 'barcode',
            'category_id'   => 'category_id',
            'harga_modal'   => 'cost_price',
            'harga_jual'    => 'price',
            'kode_item'     => 'kode_item',
            'posisi'        => 'posisi',
            'unit'          => 'unit',
            'stok_minimum'  => 'minimum_stock'
        ];

        foreach ($product_field_map as $post_key => $db_column) {
            if (isset($post_data[$post_key])) {
                $value = trim($post_data[$post_key]);
                if ($value !== '') { // Hanya update jika field diisi
                    $update_fields_product[] = "{$db_column} = :{$db_column}";
                    $params_product[":{$db_column}"] = $value;
                } elseif ($post_data[$post_key] === '' && ($db_column === 'barcode' || $db_column === 'kode_item' || $db_column === 'posisi' || $db_column === 'unit')) {
                    // Jika ingin mengizinkan mengosongkan field tertentu (menjadi NULL atau empty string di DB)
                    // $update_fields_product[] = "{$db_column} = :{$db_column}";
                    // $params_product[":{$db_column}"] = ($db_column === 'barcode' || ...) ? null : ''; // Contoh: set ke NULL atau ''
                }
            }
        }
        
        // Tambahkan stock_quantity ke update jika ada perubahan
        if ($new_stock_value_to_set !== null) {
            $update_fields_product[] = "stock_quantity = :stock_quantity";
            $params_product[':stock_quantity'] = $new_stock_value_to_set;
        }

        // Lakukan update tabel 'products' jika ada field yang diubah
        if (!empty($update_fields_product)) {
            $update_fields_product[] = "updated_at = NOW()"; // Selalu update timestamp
            $queryProduct = "UPDATE products SET " . implode(", ", $update_fields_product) . " WHERE product_id = :product_id_where";
            $stmtProduct = $pdo->prepare($queryProduct);

            foreach ($params_product as $placeholder => $value) {
                // Tentukan tipe data jika perlu, misal untuk ID atau angka
                $param_type = PDO::PARAM_STR;
                if ($placeholder === ':category_id' || $placeholder === ':stock_quantity' || $placeholder === ':minimum_stock') {
                    $param_type = PDO::PARAM_INT;
                } elseif ($placeholder === ':cost_price' || $placeholder === ':price') {
                    // Jika harga bisa desimal, pastikan validasi dan tipe data sesuai
                }
                $stmtProduct->bindValue($placeholder, $value, $param_type);
            }
            $stmtProduct->bindParam(':product_id_where', $product_id, PDO::PARAM_INT);
            $stmtProduct->execute();
        }

        // --- Logic untuk update tabel 'product_batches' ---
        $update_fields_batch = [];
        $params_batch = [];

        $batch_field_map = [
            'batch_number' => 'batch_number',
            'expire'       => 'expiry_date', // 'expire' dari form adalah 'expiry_date' di DB
            'supplier_id'  => 'supplier_id'
        ];

        foreach ($batch_field_map as $post_key => $db_column) {
            if (isset($post_data[$post_key])) {
                $value = trim($post_data[$post_key]);
                if ($value !== '') {
                    $update_fields_batch[] = "{$db_column} = :{$db_column}";
                    $params_batch[":{$db_column}"] = $value;
                }
            }
        }

        // Jika stok diupdate, perbarui juga quantity di product_batches
        // Asumsi: product_batches merefleksikan total stok produk, atau ada logika lain untuk batch tertentu.
        // Skrip asli Anda mengupdate quantity & remaining_quantity dengan stok_barang.
        // Jika ada beberapa batch per produk, logika ini mungkin perlu lebih spesifik.
        // Untuk saat ini, saya akan mengikuti pola update quantity jika stok utama berubah.
        if ($new_stock_value_to_set !== null) {
            $update_fields_batch[] = "quantity = :quantity";
            $params_batch[':quantity'] = $new_stock_value_to_set;
            $update_fields_batch[] = "remaining_quantity = :remaining_quantity";
            $params_batch[':remaining_quantity'] = $new_stock_value_to_set;
        }

        // Lakukan update tabel 'product_batches' jika ada field yang diubah
        // Perhatian: Ini akan mengupdate SEMUA batch yang terkait dengan product_id.
        // Jika Anda memiliki satu baris per product_id di product_batches, ini OK.
        // Jika ada banyak batch, Anda mungkin perlu ID batch spesifik untuk diupdate.
        if (!empty($update_fields_batch)) {
            $update_fields_batch[] = "updated_at = NOW()";
            $queryBatch = "UPDATE product_batches SET " . implode(", ", $update_fields_batch) . " WHERE product_id = :product_id_where";
            $stmtBatch = $pdo->prepare($queryBatch);

            foreach ($params_batch as $placeholder => $value) {
                 $param_type = PDO::PARAM_STR;
                 if ($placeholder === ':supplier_id' || $placeholder === ':quantity' || $placeholder === ':remaining_quantity' ) {
                    $param_type = PDO::PARAM_INT;
                 }
                $stmtBatch->bindValue($placeholder, $value, $param_type);
            }
            $stmtBatch->bindParam(':product_id_where', $product_id, PDO::PARAM_INT);
            $stmtBatch->execute();
        }
        
        $pdo->commit();

        $_SESSION['success_message'] = "Produk berhasil diperbarui.";
        header("Location: ../admin/list_barang.php"); 
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error saat memperbarui produk (ID: $product_id): " . $e->getMessage());
        // Menggunakan session untuk pesan error lebih baik
        $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui data: " . $e->getMessage();
        header("Location: ../admin/list_barang.php"); // Redirect kembali dengan pesan error
        // echo "Terjadi kesalahan saat memperbarui data: " . $e->getMessage(); // Baris asli Anda
        exit;
    }
} else {
    $_SESSION['error_message'] = "Akses tidak valid.";
    header("Location: ../admin/list_barang.php");
    // echo "Akses tidak valid."; // Baris asli Anda
    exit;
}
?>
