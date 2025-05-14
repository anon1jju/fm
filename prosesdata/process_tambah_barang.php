<?php
require_once '../functions.php'; // Pastikan path ini benar

// $farma sudah diinstansiasi di akhir functions.php

// Pastikan user sudah login dan memiliki role yang sesuai
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    $_SESSION['error_message'] = "Akses tidak sah atau sesi telah berakhir.";
    header("Location: ../signin.php"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = $farma->getPDO();
    if (!$pdo) {
        $_SESSION['error_message'] = "Koneksi database gagal.";
        $_SESSION['form_data'] = $_POST;
        header("Location: ../admin/item_masuk.php");
        exit();
    }

    // Ambil data dari form dengan sanitasi dasar
    $nama_produk        = trim($_POST['nama_produk'] ?? '');
    $kode_item_input    = trim($_POST['kode_item'] ?? ''); // Kode item dari input user
    $barcode            = trim($_POST['barcode'] ?? null);
    $category_id        = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
    $supplier_id        = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT) ?: null;
    $batch_number_input = trim($_POST['batch_number'] ?? null);
    $expire_date_input  = trim($_POST['expire_date'] ?? '');
    $cost_price         = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $price              = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock_quantity_masuk = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $minimum_stock      = filter_input(INPUT_POST, 'minimum_stock', FILTER_VALIDATE_INT);
    $unit               = trim($_POST['unit'] ?? ''); // Asumsi ini adalah NAMA unit (VARCHAR)
    $posisi             = trim($_POST['posisi'] ?? null);
    $reason_input       = trim($_POST['reason'] ?? 'Penambahan produk baru');
    $user_id            = $_SESSION['user_id'] ?? null;
    $kode_item          = null; // Akan diisi oleh hasil generate atau input user yang valid

    $errors = [];
    if (empty($nama_produk)) $errors[] = "Nama produk wajib diisi.";
    if (empty($category_id)) $errors[] = "Kategori wajib dipilih.";
    //if (empty($batch_number_input)) $errors[] = "Nomor batch wajib diisi.";
    if (empty($expire_date_input)) $errors[] = "Tanggal kedaluwarsa wajib diisi.";
    if ($cost_price === false || $cost_price < 0) $errors[] = "Harga modal tidak valid (harus angka >= 0).";
    if ($price === false || $price < 0) $errors[] = "Harga jual tidak valid (harus angka >= 0).";
    if ($stock_quantity_masuk === false || $stock_quantity_masuk <= 0) $errors[] = "Stok masuk awal tidak valid (harus angka > 0).";
    if ($minimum_stock === false || $minimum_stock < 0) $errors[] = "Stok minimum tidak valid (harus angka >= 0).";
    if (empty($unit)) $errors[] = "Unit dasar wajib dipilih.";
    if (empty($reason_input)) $errors[] = "Alasan item masuk wajib diisi.";
    if (empty($user_id)) $errors[] = "Sesi pengguna tidak ditemukan. Silakan login ulang.";

    if (!empty($expire_date_input) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $expire_date_input)) {
        $errors[] = "Format tanggal kedaluwarsa tidak valid. Gunakan YYYY-MM-DD.";
    }

    // Validasi Keunikan Barcode (Server-side)
    if (!empty($barcode)) {
        $stmt_check_barcode = $pdo->prepare("SELECT product_id FROM products WHERE barcode = :barcode LIMIT 1");
        $stmt_check_barcode->bindParam(':barcode', $barcode);
        $stmt_check_barcode->execute();
        if ($stmt_check_barcode->fetch()) {
            $errors[] = "Barcode '" . htmlspecialchars($barcode) . "' sudah terdaftar untuk produk lain.";
        }
    }

    // Penanganan Kode Item (Generate jika kosong, Validasi jika diisi)
    if (empty($kode_item_input)) {
        $unique_kode_item_found = false;
        $max_attempts = 100; 
        $generated_kode_item = '';
        for ($i = 0; $i < $max_attempts; $i++) {
            $generated_kode_item = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $stmt_check_kode = $pdo->prepare("SELECT COUNT(*) FROM products WHERE kode_item = :kode_item");
            $stmt_check_kode->bindParam(':kode_item', $generated_kode_item);
            $stmt_check_kode->execute();
            if ($stmt_check_kode->fetchColumn() == 0) {
                $unique_kode_item_found = true;
                $kode_item = $generated_kode_item;
                break;
            }
        }
        if (!$unique_kode_item_found) {
            $errors[] = "Gagal men-generate Kode Item unik. Coba masukkan manual atau ulangi.";
        }
    } else {
        $stmt_check_kode = $pdo->prepare("SELECT product_id FROM products WHERE kode_item = :kode_item LIMIT 1");
        $stmt_check_kode->bindParam(':kode_item', $kode_item_input);
        $stmt_check_kode->execute();
        if ($stmt_check_kode->fetch()) {
            $errors[] = "Kode Item '" . htmlspecialchars($kode_item_input) . "' sudah digunakan.";
        } else {
            $kode_item = $kode_item_input;
        }
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        $_SESSION['form_data'] = $_POST; 
        header("Location: ../admin/item_masuk.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $sql_product = "INSERT INTO products 
                            (product_name, kode_item, barcode, category_id, 
                             cost_price, price, unit, stock_quantity, minimum_stock, posisi, 
                             is_active, created_at, updated_at) 
                        VALUES 
                            (:product_name, :kode_item, :barcode, :category_id,
                             :cost_price, :price, :unit, :stock_quantity, :minimum_stock, :posisi,
                             1, NOW(), NOW())";
        $stmt_product = $pdo->prepare($sql_product);
        $stmt_product->bindParam(':product_name', $nama_produk);
        $stmt_product->bindParam(':kode_item', $kode_item); // $kode_item yang sudah final
        $stmt_product->bindParam(':barcode', $barcode);
        $stmt_product->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        //$stmt_product->bindParam(':supplier_id', $supplier_id, $supplier_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt_product->bindParam(':cost_price', $cost_price);
        $stmt_product->bindParam(':price', $price);
        $stmt_product->bindParam(':unit', $unit); 
        $stmt_product->bindParam(':stock_quantity', $stock_quantity_masuk, PDO::PARAM_INT);
        $stmt_product->bindParam(':minimum_stock', $minimum_stock, PDO::PARAM_INT);
        $stmt_product->bindParam(':posisi', $posisi);
        $stmt_product->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_product->execute();
        $product_id_baru = $pdo->lastInsertId();

        $sql_batch = "INSERT INTO product_batches 
                        (product_id, supplier_id, batch_number, expiry_date, purchase_price, 
                         quantity_received, remaining_quantity, created_at, updated_at)
                      VALUES 
                        (:product_id, :supplier_id, :batch_number, :expiry_date, :purchase_price,
                         :quantity_received, :remaining_quantity, NOW(), NOW())";
        $stmt_batch = $pdo->prepare($sql_batch);
        $stmt_batch->bindParam(':product_id', $product_id_baru, PDO::PARAM_INT);
        $stmt_batch->bindParam(':supplier_id', $supplier_id, $supplier_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt_batch->bindParam(':batch_number', $batch_number_input);
        $stmt_batch->bindParam(':expiry_date', $expire_date_input);
        $stmt_batch->bindParam(':purchase_price', $cost_price);
        $stmt_batch->bindParam(':quantity_received', $stock_quantity_masuk, PDO::PARAM_INT);
        $stmt_batch->bindParam(':remaining_quantity', $stock_quantity_masuk, PDO::PARAM_INT);
        $stmt_batch->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_batch->execute();
        // $batch_id_baru = $pdo->lastInsertId(); // Jika akan menyimpan batch_id di stock_movements

        $movement_type = 'barang_baru'; 
        $current_stock_after = $stock_quantity_masuk; 

        $sql_stock_movement = "INSERT INTO stock_movements 
                                (product_id, movement_type, quantity_changed, current_stock_after_movement, 
                                 movement_date, user_id, reason, related_transaction_id)
                               VALUES 
                                (:product_id, :movement_type, :quantity_changed, :current_stock_after_movement,
                                 NOW(), :user_id, :reason, :related_transaction_id)";
        $stmt_stock_movement = $pdo->prepare($sql_stock_movement);
        $stmt_stock_movement->bindParam(':product_id', $product_id_baru, PDO::PARAM_INT);
        $stmt_stock_movement->bindParam(':movement_type', $movement_type);
        $stmt_stock_movement->bindParam(':quantity_changed', $stock_quantity_masuk); 
        $stmt_stock_movement->bindParam(':current_stock_after_movement', $current_stock_after); 
        $stmt_stock_movement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_stock_movement->bindParam(':reason', $reason_input);
        $stmt_stock_movement->bindParam(':related_transaction_id', $batch_number_input); 
        $stmt_stock_movement->execute();

        $pdo->commit();
        $_SESSION['success_message'] = "Produk baru '" . htmlspecialchars($nama_produk) . "' (Kode: " . htmlspecialchars($kode_item) . ", Batch: " . htmlspecialchars($batch_number_input) . ") berhasil ditambahkan dengan stok awal $stock_quantity_masuk.";
        unset($_SESSION['form_data']); 

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("PDOException saat menambah barang: " . $e->getMessage() . " - Input: " . print_r($_POST, true));
        $_SESSION['error_message'] = "Gagal menambahkan barang baru: Terjadi kesalahan database. Detail: " . $e->getMessage();
        $_SESSION['form_data'] = $_POST;
    } catch (Exception $e) {
         if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Exception umum saat menambah barang: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan umum: " . $e->getMessage();
        $_SESSION['form_data'] = $_POST;
    }

    header("Location: ../admin/item_masuk.php");
    exit();

} else {
    $_SESSION['error_message'] = "Metode request tidak valid.";
    header("Location: ../admin/item_masuk.php");
    exit();
}
?>
