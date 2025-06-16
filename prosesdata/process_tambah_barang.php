<?php
require_once '../functions.php'; // Pastikan path ini benar

// $farma sudah diinstansiasi di akhir functions.php

// 1. Set header JSON di awal dan inisialisasi $response default
header('Content-Type: application/json');
$response = [
    'status' => 'error', // Default status
    'message' => 'Terjadi kesalahan yang tidak diketahui dalam pemrosesan.' // Default message
];

// 2. Cek sesi dan role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    $response['message'] = "Akses tidak sah atau sesi telah berakhir.";
    // Tidak ada redirect, kirim respons JSON dan exit
    echo json_encode($response);
    exit();
}

// Hapus blok 'if ($berhasil)' yang ada di sini sebelumnya karena $berhasil belum terdefinisi

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = $farma->getPDO();
    if (!$pdo) {
        // 3. Koneksi database gagal
        $response['message'] = "Koneksi database gagal.";
        // $_SESSION['form_data'] tidak relevan untuk AJAX response ini
        echo json_encode($response);
        exit();
    }

    // Data yang mungkin tunggal untuk seluruh batch (jika ada di form di luar tabel)
    $supplier_id_global = filter_input(INPUT_POST, 'supplier_id_global', FILTER_VALIDATE_INT) ?: null;
    $user_id = $_SESSION['user_id'] ?? null;

    // Ambil data array dari form (logika Anda tetap sama)
    $kode_items_input = $_POST['kode_item'] ?? [];
    $barcodes = $_POST['barcode'] ?? [];
    $nama_produks = $_POST['nama_produk'] ?? [];
    $category_ids = $_POST['category_id'] ?? [];
    $units = $_POST['unit'] ?? [];
    $minimum_stocks = $_POST['minimum_stock'] ?? [];
    $cost_prices = $_POST['cost_price'] ?? []; // Akan diterima sebagai "xxxx.00"
    $prices = $_POST['price'] ?? [];         // Akan diterima sebagai "xxxx.00"
    $batch_numbers_input = $_POST['batch_number'] ?? [];
    $expire_dates_input = $_POST['expire_date'] ?? [];
    $reasons_input = $_POST['reason'] ?? [];
    $stock_quantities_masuk = $_POST['stock_quantity'] ?? [];
    $posisis = $_POST['posisi'] ?? [];

    $errors = []; // Array ini akan mengakumulasi semua jenis error (global & item)
    $success_messages = [];
    $processed_count = 0;
    $total_items = count($nama_produks);

    // 4. Validasi Awal (Global)
    if (empty($user_id)) {
        // Langsung tambahkan ke $errors jika ingin dikumpulkan, atau handle terpisah
        $response['message'] = "Sesi pengguna tidak ditemukan. Silakan login ulang.";
        echo json_encode($response);
        exit();
    }
    if ($total_items === 0 && empty($_POST['nama_produk'])) { // Pastikan ada data produk
        $response['message'] = "Tidak ada data produk yang dikirim.";
        echo json_encode($response);
        exit();
    }
    // Jika ada validasi global lain yang ingin menghentikan proses dan mengirim JSON:
    // if (kondisi_error_global_lain) {
    //     $response['message'] = "Pesan error global lain.";
    //     echo json_encode($response);
    //     exit();
    // }


    // Variabel $errors akan digunakan untuk mengakumulasi error per item di dalam loop
    // Jika ada error global yang ingin dikumpulkan bersama error item, tambahkan ke $errors di sini.
    // Namun, untuk SweetAlert, lebih baik mengirim satu pesan error yang jelas jika ada masalah global.

    try {
        $pdo->beginTransaction();

        for ($i = 0; $i < $total_items; $i++) {
            $current_item_errors = [];
            $nama_produk = trim($nama_produks[$i] ?? '');
            $kode_item_input = trim($kode_items_input[$i] ?? '');
            $barcode = trim($barcodes[$i] ?? null);
            $category_id = filter_var($category_ids[$i] ?? null, FILTER_VALIDATE_INT) ?: null;
            $unit = trim($units[$i] ?? '');
            $minimum_stock_str = $minimum_stocks[$i] ?? '0';
            $minimum_stock = filter_var($minimum_stock_str, FILTER_VALIDATE_INT);
            
            // Konversi harga dari "xxxx.00" ke float
            $cost_price_str = $cost_prices[$i] ?? '0';
            $price_str = $prices[$i] ?? '0';
            $cost_price = floatval($cost_price_str); // floatval() akan menangani format "xxxx.00"
            $price = floatval($price_str);

            $batch_number_input = trim($batch_numbers_input[$i] ?? null);
            $expire_date_str = trim($expire_dates_input[$i] ?? '');
            $reason_input = trim($reasons_input[$i] ?? 'Penambahan produk baru');
            $stock_quantity_masuk_str = $stock_quantities_masuk[$i] ?? '0';
            $stock_quantity_masuk = filter_var($stock_quantity_masuk_str, FILTER_VALIDATE_INT);

            $posisi = trim($posisis[$i] ?? null);

            // Validasi per item (logika Anda tetap sama)
            if (empty($nama_produk)) $current_item_errors[] = "Nama produk (Baris ".($i+1).") wajib diisi.";
            if (empty($category_id)) $current_item_errors[] = "Kategori (Baris ".($i+1).") wajib dipilih.";
            
            $expire_date_db = null; // Inisialisasi
            if (empty($expire_date_str)) {
                 $current_item_errors[] = "Tanggal kedaluwarsa (Baris ".($i+1).") wajib diisi.";
            } else {
                $date_parts = explode('-', $expire_date_str);
                if (count($date_parts) === 3 && checkdate((int)$date_parts[1], (int)$date_parts[0], (int)$date_parts[2])) {
                    $expire_date_db = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
                } else {
                    $current_item_errors[] = "Format tanggal kedaluwarsa (Baris ".($i+1).": $expire_date_str) tidak valid. Gunakan DD-MM-YYYY.";
                }
            }
            if ($cost_price < 0) $current_item_errors[] = "Harga modal (Baris ".($i+1).") tidak valid."; // cost_price sudah float
            if ($price < 0) $current_item_errors[] = "Harga jual (Baris ".($i+1).") tidak valid.";    // price sudah float
            
            // Anda memiliki validasi untuk stock_quantity yang dikomentari, jika aktifkan:
            /*if ($stock_quantity_masuk === false || $stock_quantity_masuk <= 0) {
                 $current_item_errors[] = "Stok masuk awal (Baris ".($i+1).") tidak valid (harus angka > 0).";
            }*/
            if ($minimum_stock === false || $minimum_stock < 0) $current_item_errors[] = "Stok minimum (Baris ".($i+1).") tidak valid.";
            if (empty($unit)) $current_item_errors[] = "Unit dasar (Baris ".($i+1).") wajib dipilih.";

            // Validasi Keunikan Barcode (Server-side) - Logika Anda tetap
            if (!empty($barcode)) {
                $stmt_check_barcode = $pdo->prepare("SELECT product_id FROM products WHERE barcode = :barcode LIMIT 1");
                $stmt_check_barcode->bindParam(':barcode', $barcode);
                $stmt_check_barcode->execute();
                if ($stmt_check_barcode->fetch()) {
                    $current_item_errors[] = "Barcode '".htmlspecialchars($barcode)."' (Baris ".($i+1).") sudah terdaftar.";
                }
            }

            // Penanganan Kode Item - Logika Anda tetap
            $kode_item = null;
            if (empty($kode_item_input)) {
                $unique_kode_item_found = false;
                $max_attempts = 100; // Anda bisa sesuaikan
                for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
                    $generated_kode_item = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
                    $stmt_check_kode = $pdo->prepare("SELECT COUNT(*) FROM products WHERE kode_item = :kode_item");
                    $stmt_check_kode->bindParam(':kode_item', $generated_kode_item);
                    $stmt_check_kode->execute();
                    if ($stmt_check_kode->fetchColumn() == 0) {
                        $kode_item = $generated_kode_item;
                        $unique_kode_item_found = true;
                        break;
                    }
                }
                if (!$unique_kode_item_found) {
                    $current_item_errors[] = "Gagal men-generate Kode Item unik (Baris ".($i+1)."). Coba masukkan manual atau ulangi.";
                }
            } else {
                $stmt_check_kode = $pdo->prepare("SELECT product_id FROM products WHERE kode_item = :kode_item LIMIT 1");
                $stmt_check_kode->bindParam(':kode_item', $kode_item_input);
                $stmt_check_kode->execute();
                if ($stmt_check_kode->fetch()) {
                    $current_item_errors[] = "Kode Item '".htmlspecialchars($kode_item_input)."' (Baris ".($i+1).") sudah digunakan.";
                } else {
                    $kode_item = $kode_item_input;
                }
            }

            if (!empty($current_item_errors)) {
                $errors = array_merge($errors, $current_item_errors); // Kumpulkan error per item
                continue; // Lanjut ke item berikutnya jika ada error pada item ini
            }

            // Jika tidak ada error untuk item ini, lanjutkan proses insert (Logika Anda tetap)
            $sql_product = "INSERT INTO products (product_name, kode_item, barcode, category_id, cost_price, price, unit, stock_quantity, minimum_stock, posisi, is_active, created_at, updated_at) VALUES (:product_name, :kode_item, :barcode, :category_id, :cost_price, :price, :unit, :stock_quantity, :minimum_stock, :posisi, 1, NOW(), NOW())";
            $stmt_product = $pdo->prepare($sql_product);
            $stmt_product->bindParam(':product_name', $nama_produk);
            $stmt_product->bindParam(':kode_item', $kode_item);
            $stmt_product->bindParam(':barcode', $barcode);
            $stmt_product->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt_product->bindParam(':cost_price', $cost_price); // Sudah float
            $stmt_product->bindParam(':price', $price);           // Sudah float
            $stmt_product->bindParam(':unit', $unit);
            $stmt_product->bindParam(':stock_quantity', $stock_quantity_masuk, PDO::PARAM_INT);
            $stmt_product->bindParam(':minimum_stock', $minimum_stock, PDO::PARAM_INT);
            $stmt_product->bindParam(':posisi', $posisi);
            $stmt_product->execute();
            $product_id_baru = $pdo->lastInsertId();

            $sql_batch = "INSERT INTO product_batches (product_id, supplier_id, batch_number, expiry_date, purchase_price, quantity, remaining_quantity, created_at, updated_at) VALUES (:product_id, :supplier_id, :batch_number, :expiry_date, :purchase_price, :quantity, :remaining_quantity, NOW(), NOW())";
            $stmt_batch = $pdo->prepare($sql_batch);
            $stmt_batch->bindParam(':product_id', $product_id_baru, PDO::PARAM_INT);
            $stmt_batch->bindParam(':supplier_id', $supplier_id_global, $supplier_id_global ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt_batch->bindParam(':batch_number', $batch_number_input);
            $stmt_batch->bindParam(':expiry_date', $expire_date_db);
            $stmt_batch->bindParam(':purchase_price', $cost_price); // Sudah float
            $stmt_batch->bindParam(':quantity', $stock_quantity_masuk, PDO::PARAM_INT);
            $stmt_batch->bindParam(':remaining_quantity', $stock_quantity_masuk, PDO::PARAM_INT);
            $stmt_batch->execute();

            $movement_type = 'barang_baru';
            $current_stock_after = $stock_quantity_masuk;

            $sql_stock_movement = "INSERT INTO stock_movements (product_id, movement_type, quantity_changed, current_stock_after_movement, movement_date, user_id, reason, related_transaction_id) VALUES (:product_id, :movement_type, :quantity_changed, :current_stock_after_movement, NOW(), :user_id, :reason, :related_transaction_id)";
            $stmt_stock_movement = $pdo->prepare($sql_stock_movement);
            $stmt_stock_movement->bindParam(':product_id', $product_id_baru, PDO::PARAM_INT);
            $stmt_stock_movement->bindParam(':movement_type', $movement_type);
            $stmt_stock_movement->bindParam(':quantity_changed', $stock_quantity_masuk);
            $stmt_stock_movement->bindParam(':current_stock_after_movement', $current_stock_after);
            $stmt_stock_movement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_stock_movement->bindParam(':reason', $reason_input);
            $stmt_stock_movement->bindParam(':related_transaction_id', $batch_number_input);
            $stmt_stock_movement->execute();

            $success_messages[] = "Produk '".htmlspecialchars($nama_produk)."' (Kode: ".htmlspecialchars($kode_item).") berhasil ditambahkan.";
            $processed_count++;
        }

        // 5. Setelah Loop Selesai
        if (!empty($errors)) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $response['status'] = 'error';
            $response['message'] = "Terdapat kesalahan pada beberapa item:<br>" . implode("<br>", $errors);
            // $_SESSION['form_data'] tidak relevan untuk AJAX response
        } else {
            $pdo->commit();
            $response['status'] = 'success';
            $response['message'] = "$processed_count dari $total_items produk berhasil ditambahkan.<br>" . implode("<br>", $success_messages);
            // unset($_SESSION['form_data']); // Tidak relevan untuk AJAX response
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("PDOException saat menambah barang batch: " . $e->getMessage() . " - Input: " . print_r($_POST, true));
        $response['status'] = 'error';
        $response['message'] = "Gagal menambahkan barang: Terjadi kesalahan database. Silakan cek log server.";
        // $_SESSION['form_data'] tidak relevan untuk AJAX response
    } catch (Exception $e) {
         if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Exception umum saat menambah barang batch: " . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = "Terjadi kesalahan umum: " . $e->getMessage();
        // $_SESSION['form_data'] tidak relevan untuk AJAX response
    }

    // 6. Selalu kirim respons JSON di akhir blok POST
    echo json_encode($response);
    exit();

} else {
    // 7. Metode request tidak valid
    $response['message'] = "Metode request tidak valid.";
    // $_SESSION['error_message'] tidak relevan
    echo json_encode($response);
    exit();
}


?>
