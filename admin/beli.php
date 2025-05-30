<?php
require_once '../functions.php'; // Make sure this path is correct

if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$suppliers = $farma->getSuppliers();
$products_list_for_js = $farma->getProductsForPurchaseForm(); // Used for JS
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = $farma->getPDO();
    if (!$pdo) {
        $error = "Gagal terhubung ke database.";
    } else {
        $supplier_id = $_POST['supplier_id'] ?? null;
        $invoice_number_supplier = $_POST['invoice_number_supplier'] ?? null;
        $purchase_date_input = $_POST['purchase_date'] ?? null;
        
        $due_date_input_manual = $_POST['due_date'] ?? null; 
        $due_days_input = $_POST['due_days'] ?? 30; 

        $payment_status = $_POST['payment_status'] ?? 'hutang'; 
        $received_status = $_POST['received_status'] ?? 'diterima'; 
        $notes = $_POST['notes'] ?? null;
        $user_id = $_SESSION['user_id'] ?? null;

        $product_ids = $_POST['product_id'] ?? []; 
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['purchase_price'] ?? [];
        $sell_prices_form = $_POST['sell_price'] ?? []; 
        $batch_numbers_form = $_POST['batch_number'] ?? [];
        $expiry_dates_form = $_POST['expiry_date'] ?? [];

        if (empty($supplier_id) || empty($invoice_number_supplier) || empty($purchase_date_input) || empty($user_id) || empty($product_ids) || count($product_ids) === 0) {
            $error = "Data pembelian utama tidak lengkap atau tidak ada item yang ditambahkan.";
        } else {
            $valid_items = true;
            foreach ($product_ids as $key => $pid) {
                if (empty($pid) || 
                    !isset($quantities[$key]) || $quantities[$key] <= 0 || 
                    !isset($unit_prices[$key]) || $unit_prices[$key] < 0 || 
                    !isset($sell_prices_form[$key]) || $sell_prices_form[$key] < 0) {
                    $error = "Data item pada baris ke-" . ($key + 1) . " tidak lengkap (Produk, Kuantitas, Harga Beli, atau Harga Jual). Pastikan produk dipilih dan semua field terisi.";
                    $valid_items = false;
                    break;
                }
            }

            if ($valid_items) {
                try {
                    $pdo->beginTransaction();
                    $total_purchase_amount = 0;

                    $final_due_date = null;
                    if (!empty($purchase_date_input)) {
                        $purchase_datetime = new DateTime($purchase_date_input);
                        if (!empty($due_date_input_manual)) {
                             $dt_check = DateTime::createFromFormat('Y-m-d', $due_date_input_manual);
                             if ($dt_check && $dt_check->format('Y-m-d') === $due_date_input_manual) {
                                $final_due_date = $due_date_input_manual;
                             }
                        }
                        if ($final_due_date === null && is_numeric($due_days_input)) { 
                            $days_to_add = (int)$due_days_input;
                            if ($days_to_add >= 0) { 
                                $due_datetime = clone $purchase_datetime;
                                $due_datetime->add(new DateInterval("P{$days_to_add}D"));
                                $final_due_date = $due_datetime->format('Y-m-d');
                            }
                        }
                    }

                    $sql_purchase = "INSERT INTO purchases (supplier_id, invoice_number, purchase_date, due_date, total_amount, payment_status, received_status, user_id, notes, created_at, updated_at) 
                                     VALUES (:supplier_id, :invoice_number, :purchase_date, :due_date, 0, :payment_status, :received_status, :user_id, :notes, NOW(), NOW())";
                    $stmt_purchase = $pdo->prepare($sql_purchase);
                    $stmt_purchase->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                    $stmt_purchase->bindParam(':invoice_number', $invoice_number_supplier);
                    $stmt_purchase->bindParam(':purchase_date', $purchase_date_input);
                    $stmt_purchase->bindParam(':due_date', $final_due_date);
                    $stmt_purchase->bindParam(':payment_status', $payment_status);
                    $stmt_purchase->bindParam(':received_status', $received_status);
                    $stmt_purchase->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt_purchase->bindParam(':notes', $notes);
                    $stmt_purchase->execute();
                    $purchase_id = $pdo->lastInsertId();

                    foreach ($product_ids as $key => $product_id_item) { 
                        if (empty($product_id_item)) continue; 

                        $quantity = $quantities[$key];
                        $unit_price = $unit_prices[$key];
                        $sell_price_item = $sell_prices_form[$key]; 
                        $batch_number_item = isset($batch_numbers_form[$key]) && !empty($batch_numbers_form[$key]) ? $batch_numbers_form[$key] : null;
                        $expiry_date_item = isset($expiry_dates_form[$key]) && !empty($expiry_dates_form[$key]) ? $expiry_dates_form[$key] : null;
                        
                        $item_total = $quantity * $unit_price;
                        $total_purchase_amount += $item_total;

                        $sql_item = "INSERT INTO purchase_items (purchase_id, product_id, batch_number, expiry_date, quantity, unit_price, item_total, received_quantity, created_at, updated_at) 
                                     VALUES (:purchase_id, :product_id, :batch_number, :expiry_date, :quantity, :unit_price, :item_total, :received_quantity, NOW(), NOW())";
                        $stmt_item = $pdo->prepare($sql_item);
                        $stmt_item->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                        $stmt_item->bindParam(':product_id', $product_id_item, PDO::PARAM_INT);
                        $stmt_item->bindParam(':batch_number', $batch_number_item);
                        $stmt_item->bindParam(':expiry_date', $expiry_date_item);
                        $stmt_item->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_item->bindParam(':unit_price', $unit_price);
                        $stmt_item->bindParam(':item_total', $item_total);
                        $stmt_item->bindParam(':received_quantity', $quantity, PDO::PARAM_INT); 
                        $stmt_item->execute();
                        
                        $sql_update_product = "UPDATE products SET stock_quantity = stock_quantity + :quantity, price = :sell_price_form, cost_price = :purchase_price_form WHERE product_id = :product_id";
                        $stmt_update_product = $pdo->prepare($sql_update_product);
                        $stmt_update_product->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_update_product->bindParam(':sell_price_form', $sell_price_item);
                        $stmt_update_product->bindParam(':purchase_price_form', $unit_price);
                        $stmt_update_product->bindParam(':product_id', $product_id_item, PDO::PARAM_INT);
                        $stmt_update_product->execute();

                        //if (!empty($batch_number_item)) { 
                        $batch_number_item = isset($batch_numbers_form[$key]) && !empty($batch_numbers_form[$key]) ? $batch_numbers_form[$key] : ''; // Jadi string kosong jika tidak diisi

                            // MODIFIKASI SQL DI SINI:
                            $sql_prod_batch = "INSERT INTO product_batches (product_id, supplier_id, batch_number, expiry_date, purchase_price, quantity, remaining_quantity, created_at, updated_at)
                                               VALUES (:product_id, :supplier_id, :batch_number, :expiry_date, :purchase_price, :quantity, :quantity, NOW(), NOW())
                                               ON DUPLICATE KEY UPDATE 
                                                   supplier_id = VALUES(supplier_id),
                                                   expiry_date = VALUES(expiry_date), 
                                                   purchase_price = VALUES(purchase_price), 
                                                   quantity = quantity + VALUES(quantity),
                                                   remaining_quantity = remaining_quantity + VALUES(remaining_quantity),
                                                   updated_at = NOW()";
                        
                            $stmt_prod_batch = $pdo->prepare($sql_prod_batch);
                            $stmt_prod_batch->bindParam(':product_id', $product_id_item, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT); 
                            $stmt_prod_batch->bindParam(':batch_number', $batch_number_item);
                            $stmt_prod_batch->bindParam(':expiry_date', $expiry_date_item);
                            $stmt_prod_batch->bindParam(':purchase_price', $unit_price); // $unit_price adalah harga beli dari item pembelian saat ini
                            $stmt_prod_batch->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                            $stmt_prod_batch->execute(); 
                        //}
                    }

                    $sql_update_total = "UPDATE purchases SET total_amount = :total_amount WHERE purchase_id = :purchase_id";
                    $stmt_update_total = $pdo->prepare($sql_update_total);
                    $stmt_update_total->bindParam(':total_amount', $total_purchase_amount);
                    $stmt_update_total->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                    $stmt_update_total->execute();

                    if ($payment_status === 'cicil' && isset($_POST['initial_amount_paid']) && !empty($_POST['initial_amount_paid'])) {
                        $initial_amount_paid_val = filter_var($_POST['initial_amount_paid'], FILTER_VALIDATE_FLOAT);

                        if ($initial_amount_paid_val !== false && $initial_amount_paid_val > 0) {
                            if ($initial_amount_paid_val > $total_purchase_amount) {
                                $error .= (empty($error) ? "" : "<br>") . "Jumlah cicilan awal melebihi total pembelian.";
                            } else {
                                $initial_payment_data = [
                                    'payment_date' => $_POST['initial_payment_date'] ?? date('Y-m-d'),
                                    'amount_paid' => $initial_amount_paid_val,
                                    'payment_method' => $_POST['initial_payment_method'] ?? 'N/A',
                                    'reference' => $_POST['initial_payment_reference'] ?? null,
                                    'proof_document_path' => null,
                                ];

                                if (isset($_FILES['initial_proof_document']) && $_FILES['initial_proof_document']['error'] == 0) {
                                    $target_dir = "../uploads/purchase_proofs/";
                                    if (!is_dir($target_dir)) {
                                        mkdir($target_dir, 0755, true);
                                    }
                                    $file_extension = strtolower(pathinfo($_FILES["initial_proof_document"]["name"], PATHINFO_EXTENSION));
                                    $safe_filename = "initial_proof_" . $purchase_id . "_" . time() . "." . $file_extension;
                                    $target_file = $target_dir . $safe_filename;
                                    
                                    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
                                    $max_size = 2 * 1024 * 1024; // 2MB

                                    if (in_array($file_extension, $allowed_types) && $_FILES['initial_proof_document']['size'] <= $max_size) {
                                        if (move_uploaded_file($_FILES["initial_proof_document"]["tmp_name"], $target_file)) {
                                            $initial_payment_data['proof_document_path'] = $target_file;
                                        } else {
                                            $error .= (empty($error) ? "" : "<br>") . "Gagal mengunggah bukti cicilan awal.";
                                        }
                                    } else {
                                        $error .= (empty($error) ? "" : "<br>") . "File bukti cicilan awal tidak valid (Hanya JPG, PNG, PDF, maks 2MB).";
                                    }
                                }

                                if (strpos($error, 'Gagal mengunggah bukti cicilan awal') === false && strpos($error, 'File bukti cicilan awal tidak valid') === false && strpos($error, 'Jumlah cicilan awal melebihi total pembelian.') === false) {
                                    $farma->addPurchasePayment($purchase_id, $initial_payment_data, $user_id);
                                    $message .= (empty($message) ? "" : "<br>") . "Cicilan awal berhasil dicatat dan status pembelian diperbarui.";
                                }
                            }
                        } elseif ($initial_amount_paid_val !== false && $initial_amount_paid_val <= 0 && !empty($_POST['initial_amount_paid'])) {
                             $error .= (empty($error) ? "" : "<br>") . "Jumlah cicilan awal harus lebih besar dari 0.";
                        }
                    }

                    if (empty($error) || (strpos($error, 'Gagal mencatat cicilan awal') === false && strpos($error, 'Gagal mengunggah bukti cicilan awal') === false && strpos($error, 'File bukti cicilan awal tidak valid') === false && strpos($error, 'Jumlah cicilan awal melebihi total pembelian.') === false && strpos($error, 'Jumlah cicilan awal harus lebih besar dari 0.') === false)) {
                        $pdo->commit();
                        $message = "Pembelian berhasil ditambahkan dengan ID: " . $purchase_id . ". Total: Rp " . number_format($total_purchase_amount, 0, ',', '.') . (empty($message) ? "" : "<br>Info Cicilan: " . $message);
                        $_POST = array(); 
                    } else {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                    }

                } catch (Exception $e) { 
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Gagal menambahkan pembelian: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .item-row:not(:first-child) { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; }
        .item-total-price { font-weight: bold; text-align: right; }
        #purchaseItemsSectionWrapper {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
            margin-bottom: 1rem;
            
        }

        #purchaseItemsSectionWrapper thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #a1a2a6;
        }
                /* Targetkan header kolom pertama (Produk) di tabel item pembelian */
        #purchaseItemsSectionWrapper table thead th:first-child {
            width: 250px; /* Naikkan nilai ini, misalnya dari 300px menjadi 350px atau lebih */
            /* Anda juga bisa menggunakan persentase, contoh: width: 35%; */
        }
        
        /* Targetkan sel data kolom pertama (Produk) di tabel item pembelian */
        #purchaseItemsSectionWrapper table tbody td:first-child {
            width: 250px; /* Pastikan lebar ini sama dengan yang di th agar kolomnya lurus */
        }
        
        /* Opsional: Memastikan input di dalamnya benar-benar menggunakan lebar tersebut */
        #purchaseItemsSectionWrapper table tbody td:first-child .product-search-input {
            min-width: 100%;
            box-sizing: border-box;
        }
        
        
        .grand-total-container { margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #333; text-align: right; }
        .grand-total-container span { font-size: 1.25rem; font-weight: bold; }
        .product-search-input-container { position: relative; }
        .product-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.575rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 1001; /* Z-index tinggi agar muncul di atas semua elemen */
        }
        .product-search-results div { padding: 8px 12px; cursor: pointer; }
        .product-search-results div:hover { background-color: #f0f0f0; }
        .product-search-results .result-name { font-weight: bold; }
        .product-search-results .result-details { font-size: 0.85em; color: #555; }
        .hidden { display: none; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include "includes/switch.php";?>
    <div id="loader" style="display:none;"><img src="../assets/images/media/loader.svg" alt=""></div>
    <div class="page">
        <?php include "includes/header.php";?>
        <?php include "includes/sidebar.php";?>
        
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header"><div class="box-title"><span><a href="hutang_supplier.php" class="ti-btn ti-btn-sm ti-btn-info"><i class="ri-arrow-left-s-line"></i>Kembali</a></span> Tambah Pembelian Baru</div></div>
                            <div class="box-body p-6">
                                <?php if ($message): ?>
                                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Berhasil!</strong> <span class="block sm:inline"><?php echo $message; ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Error!</strong> <span class="block sm:inline"><?php echo $error; ?></span>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="beli.php" id="purchaseForm" enctype="multipart/form-data">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6 items-end">
                                        <div class="relative">
                                            <label for="invoice_number_supplier" class="form-label">No. Invoice Supplier <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input pr-10" id="invoice_number_supplier" name="invoice_number_supplier" value="<?php echo isset($_POST['invoice_number_supplier']) ? htmlspecialchars($_POST['invoice_number_supplier']) : ''; ?>" required>
                                            <button type="button" onclick="generateInvoiceNumber()" class="absolute right-2 top-[35px] text-gray-500 hover:text-blue-500"><i class="ri-loop-right-line text-lg"></i></button>
                                        </div>
                                        <div>
                                            <label for="supplier_id" class="form-label">Supplier <span class="text-red-500">*</span></label>
                                            <select id="supplier_id" name="supplier_id" class="ti-form-select select2" required>
                                                <option value="">Pilih Supplier</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['supplier_id'] ? 'selected' : ''); ?>>
                                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="purchase_date" class="form-label">Tanggal Pembelian <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input flatpickr-date" id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars(isset($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d'));?>" required>
                                        </div>
                                    </div>

                                    <hr class="my-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-xl font-semibold">Item Pembelian</h3>
                                    </div> 
                                    <div id="purchaseItemsSectionWrapper" class="border border-gray-300 dark:border-gray-700 rounded-md mb-4">
                                        <table class="min-w-full divide-y divide-gray-200 border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                                            <thead class="bg-gray-200 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Produk <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Qty <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Harga Beli <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Harga Jual <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Total Item</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Expire</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-100 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsContainer" class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                                <?php 
                                                $item_count = isset($_POST['product_id']) ? count($_POST['product_id']) : 1;
                                                for ($i = 0; $i < $item_count; $i++):
                                                ?>
                                                <tr class="item-row">
                                                    <td class="px-3 py-2 whitespace-nowrap product-search-input-container">
                                                        <input type="text" class="ti-form-input product-search-input" name="product_search_display[]" placeholder="Cari Produk/Kode/Barcode..." style="width: 100%;" autocomplete="off" value="<?php echo isset($_POST['product_search_display'][$i]) ? htmlspecialchars($_POST['product_search_display'][$i]) : ''; ?>">
                                                        <input type="hidden" name="product_id[]" class="actual-product-id" value="<?php echo isset($_POST['product_id'][$i]) ? htmlspecialchars($_POST['product_id'][$i]) : ''; ?>">
                                                        <div class="product-search-results"></div>
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="tel" name="quantity[]" class="ti-form-input quantity-input text-center" placeholder="Qty" required value="<?php echo isset($_POST['quantity'][$i]) ? htmlspecialchars($_POST['quantity'][$i]) : '1'; ?>" style="width: 60px;"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="tel" step="any" name="purchase_price[]" class="ti-form-input purchase-price-input" style="width: 100px" min="0" value="<?php echo isset($_POST['purchase_price'][$i]) ? htmlspecialchars($_POST['purchase_price'][$i]) : ''; ?>" required placeholder="Harga Beli"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="tel" step="any" name="sell_price[]" class="ti-form-input sell-price-input" style="width: 100px" min="0" value="<?php echo isset($_POST['sell_price'][$i]) ? htmlspecialchars($_POST['sell_price'][$i]) : ''; ?>" required placeholder="Harga Jual"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="text" class="ti-form-input item-total-display" readonly placeholder="Rp 0" style="background-color: #e9ecef; width: 120px;"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="text" name="batch_number[]" class="ti-form-input batch-number-input" placeholder="Batch" style="width: 100px;" value="<?php echo isset($_POST['batch_number'][$i]) ? htmlspecialchars($_POST['batch_number'][$i]) : ''; ?>"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><input type="text" name="expiry_date[]" class="ti-form-input expiry-date-input" style="width: 120px;" value="<?php echo isset($_POST['expiry_date'][$i]) ? htmlspecialchars($_POST['expiry_date'][$i]) : ''; ?>"></td>
                                                    <td class="px-4 py-2 whitespace-nowrap"><button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i></button></td>
                                                </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" id="addItemBtn" class="ti-btn ti-btn-sm ti-btn-success"><i class="ri-add-line me-1"></i>Tambah Item</button>
                                    <a href="item_masuk.php" class="ti-btn ti-btn-sm ti-btn-primary">Input Barang Baru</a>
                                    <div class="grand-total-container">
                                        <label class="form-label text-lg font-bold">Total Pembelian:</label>
                                        <span id="grandTotalDisplay">Rp 0</span>
                                    </div>
                                    
                                    <hr class="my-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div> <!-- Kolom Kiri: Status, Jatuh Tempo, Catatan -->
                                            <h3 class="text-xl font-semibold mb-4">Status & Info Lain</h3>
                                            <div class="space-y-4">
                                                <div>
                                                    <label for="payment_status" class="form-label">Status Pembayaran</label>
                                                    <select id="payment_status" name="payment_status" class="ti-form-select">
                                                        <option value="hutang" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'hutang' ? 'selected' : (!isset($_POST['payment_status']) ? 'selected' : '')); ?>>Hutang</option>
                                                        <option value="cicil" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'cicil' ? 'selected' : ''); ?>>Cicil</option>
                                                        <option value="lunas" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'lunas' ? 'selected' : ''); ?>>Lunas</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="flex flex-col md:flex-row gap-4 items-end pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="w-full md:w-1/2">
                                                        <label for="due_days" class="form-label text-sm">Hari J.Tempo</label>
                                                        <input type="tel" class="ti-form-input text-center" id="due_days" name="due_days" value="<?php echo isset($_POST['due_days']) ? htmlspecialchars($_POST['due_days']) : '30'; ?>" min="0">
                                                    </div>
                                                    <div class="w-full md:w-1/2">
                                                        <label for="due_date" class="form-label text-sm">Tgl. Jatuh Tempo</label>
                                                        <input type="text" class="ti-form-input flatpickr-date" id="due_date" name="due_date" placeholder="Otomatis/Manual" value="<?php
                                                                $display_due_date_form = '';
                                                                if (isset($_POST['due_date']) && !empty($_POST['due_date'])) {
                                                                    $display_due_date_form = $_POST['due_date'];
                                                                } else if (isset($_POST['purchase_date'], $_POST['due_days'])) {
                                                                    try { /* ... PHP logic for due date ... */ } catch (Exception $e) { }
                                                                } else { try { /* ... PHP logic for default due date ... */ } catch (Exception $e) { } }
                                                                echo htmlspecialchars($display_due_date_form); ?>">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="notes" class="form-label">Catatan</label>
                                                    <textarea class="ti-form-input" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div> <!-- Kolom Kanan: Detail Pembayaran Awal (Jika Cicil) -->
                                            <h3 class="text-xl font-semibold mb-4">Pembayaran Awal</h3>
                                            <div id="initial-installment-section" class="space-y-4 hidden">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"> <!-- Ini baris utama form dua kolom -->
                                            
                                                    <div>
                                                        <label for="initial_payment_date" class="form-label">Tanggal Bayar Awal</label>
                                                        <input type="text" class="ti-form-input flatpickr-date" id="initial_payment_date" name="initial_payment_date" value="<?php echo isset($_POST['initial_payment_date']) ? htmlspecialchars($_POST['initial_payment_date']) : date('Y-m-d'); ?>">
                                                    </div>
                                            
                                                    <div>
                                                        <label for="initial_amount_paid" class="form-label">Jumlah Bayar Awal</label>
                                                        <input type="tel" step="any" class="ti-form-input" id="initial_amount_paid" name="initial_amount_paid" min="0" value="<?php echo isset($_POST['initial_amount_paid']) ? htmlspecialchars($_POST['initial_amount_paid']) : ''; ?>" placeholder="0">
                                                        <p class="text-xs text-gray-500 mt-1">Jumlah hrga yang akan dicicil</p>
                                                    </div>
                                            
                                                    <div>
                                                        <label for="initial_payment_method" class="form-label">Metode Bayar Awal</label>
                                                        <input type="text" class="ti-form-input" id="initial_payment_method" name="initial_payment_method" placeholder="e.g., Transfer Bank, Cash" value="<?php echo isset($_POST['initial_payment_method']) ? htmlspecialchars($_POST['initial_payment_method']) : ''; ?>">
                                                    </div>
                                            
                                                    <div>
                                                        <label for="initial_payment_reference" class="form-label">No. Referensi Bayar Awal</label>
                                                        <input type="text" class="ti-form-input" id="initial_payment_reference" name="initial_payment_reference" placeholder="e.g., TRF123" value="<?php echo isset($_POST['initial_payment_reference']) ? htmlspecialchars($_POST['initial_payment_reference']) : ''; ?>">
                                                    </div>
                                            
                                                    <div class="md:col-span-2"> <!-- Supaya full width -->
                                                        <label for="initial_proof_document" class="form-label">Unggah Bukti Bayar Awal (Opsional)</label>
                                                        <input type="file" class="ti-form-input" id="initial_proof_document" name="initial_proof_document">
                                                        <p class="text-xs text-gray-500 mt-1">File: JPG, PNG, PDF. Maks 2MB.</p>
                                                    </div>
                                            
                                                </div>
                                            </div>
                                            <p id="cicil-info-placeholder" class="text-sm text-gray-500">Form pembayaran awal akan muncul di sini jika status "Cicil" dipilih.</p>
                                        </div>
                                    </div>
                                    <div class="mt-8 flex justify-end">
                                        <button type="submit" class="ti-btn ti-btn-primary">Simpan Pembelian</button>
                                        <a href="beli.php" class="ti-btn ti-btn-light ms-2">Batal</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "includes/footer.php";?>
    </div>
    <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
    <script src="../assets/js/switch.js"></script>
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>
    <script src="../assets/libs/preline/preline.js"></script>
    <script src="../assets/js/defaultmenu.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/js/sticky.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/js/simplebar.js"></script>
    <script src="../assets/js/custom.js"></script>
    <script src="../assets/js/custom-switcher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script untuk memposisikan hasil pencarian di luar container scroll
            const searchInputs = document.querySelectorAll('.product-search-input');
            
            searchInputs.forEach(input => {
                const resultsDiv = input.parentElement.querySelector('.product-search-results');
                
                input.addEventListener('focus', function() {
                    if (resultsDiv) {
                        // Menghitung posisi hasil pencarian
                        const inputRect = input.getBoundingClientRect();
                        // const wrapperRect = document.getElementById('purchaseItemsSectionWrapper').getBoundingClientRect(); // Tidak terpakai
                        
                        // Memposisikan hasil pencarian di luar wrapper
                        resultsDiv.style.position = 'fixed';
                        resultsDiv.style.top = (inputRect.bottom) + 'px';
                        resultsDiv.style.left = inputRect.left + 'px';
                        resultsDiv.style.width = inputRect.width + 'px';
                        resultsDiv.style.display = 'block';
                    }
                });
                
                // Menyembunyikan hasil pencarian saat klik di luar
                document.addEventListener('click', function(e) {
                    if (e.target !== input && !resultsDiv.contains(e.target)) {
                        resultsDiv.style.display = 'none';
                    }
                });
            });
        });
    </script>
    
    <script>
        function generateInvoiceNumber() {
            const randomNumber = Math.floor(10000 + Math.random() * 90000);
            document.getElementById('invoice_number_supplier').value = randomNumber;
        }
    </script>
    
    <script>
    $(document).ready(function() { $('.select2').select2({ placeholder: "Pilih Supplier", allowClear: true }); });
    </script>
    
    <script>
    const productsList = <?php echo json_encode($products_list_for_js); ?>;
    document.addEventListener('DOMContentLoaded', function () {
        const itemsContainer = document.getElementById('itemsContainer');
        const addItemBtn = document.getElementById('addItemBtn');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        
        // BARU: Global click listener untuk menyembunyikan hasil pencarian produk
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.product-search-results').forEach(resultsDiv => {
                if (resultsDiv.style.display !== 'none') { // Hanya proses jika sedang ditampilkan
                    const searchInputContainer = resultsDiv.closest('.product-search-input-container');
                    if (searchInputContainer) {
                        const searchInput = searchInputContainer.querySelector('.product-search-input');
                        // Sembunyikan jika klik bukan pada input terkait dan bukan di dalam resultsDiv itu sendiri
                        if (searchInput && e.target !== searchInput && !resultsDiv.contains(e.target)) {
                            resultsDiv.style.display = 'none';
                        }
                    }
                }
            });
        });

        function getDefaultItemRowHTML() { /* ... JavaScript for item row template ... */ 
            return `
                <td class="px-4 py-2 whitespace-nowrap product-search-input-container">
                    <input type="text" class="ti-form-input product-search-input" name="product_search_display[]" placeholder="Cari Produk/Kode/Barcode..." style="width: 100%;" autocomplete="off">
                    <input type="hidden" name="product_id[]" class="actual-product-id">
                    <div class="product-search-results" style="display: none;"></div>
                </td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="tel" name="quantity[]" class="ti-form-input quantity-input text-center" placeholder="Qty" required value="1" style="width: 60px;"></td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="tel" step="any" name="purchase_price[]" class="ti-form-input purchase-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Beli"></td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="tel" step="any" name="sell_price[]" class="ti-form-input sell-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Jual"></td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="text" class="ti-form-input item-total-display" readonly placeholder="Rp 0" style="background-color: #e9ecef; width: 120px;"></td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="text" name="batch_number[]" class="ti-form-input batch-number-input" placeholder="Batch" style="width: 100px;"></td>
                <td class="px-4 py-2 whitespace-nowrap"><input type="text" name="expiry_date[]" class="ti-form-input expiry-date-input" style="width: 120px;"></td>
                <td class="px-4 py-2 whitespace-nowrap"><button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i></button></td>
            `;
        }
        const itemRowTemplateHTML = getDefaultItemRowHTML();

        document.querySelectorAll('.flatpickr-date:not(.expiry-date-input)').forEach(el => { flatpickr(el, { dateFormat: "Y-m-d", altInput: true, altFormat: "d-m-Y", allowInput: true }); });
        function initializeExpiryDateFlatpickr(element) { if (!element) return; if (element._flatpickr) { element._flatpickr.destroy(); } flatpickr(element, { dateFormat: "Y-m-d", altInput: true, altFormat: "d-m-Y", allowInput: true, placeholder: "YYYY-MM-DD" });}
        
        itemsContainer.querySelectorAll('.item-row').forEach(row => {
            const expiryInput = row.querySelector('.expiry-date-input');
            if (expiryInput) { initializeExpiryDateFlatpickr(expiryInput); }
            initializeProductSearch(row);
            attachCalculationListeners(row);
            const removeBtn = row.querySelector('.removeItemBtn');
            if (removeBtn) { attachRemoveButtonListener(removeBtn); }
            updateItemTotal(row); 
        });

        if (itemsContainer.querySelectorAll('.item-row').length === 0 && <?php echo isset($_POST['product_id']) ? 'false' : 'true'; ?>) { addItemRow(); } 
        else if (itemsContainer.querySelectorAll('.item-row').length > 0) { updateGrandTotal(); }

        function updateGrandTotal() { /* ... JavaScript ... */ 
            let grandTotal = 0;
            itemsContainer.querySelectorAll('.item-row').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const purchasePrice = parseFloat(row.querySelector('.purchase-price-input').value) || 0;
                grandTotal += quantity * purchasePrice;
            });
            grandTotalDisplay.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
        function updateItemTotal(rowElement) { /* ... JavaScript ... */ 
            const quantity = parseFloat(rowElement.querySelector('.quantity-input').value) || 0;
            const purchasePrice = parseFloat(rowElement.querySelector('.purchase-price-input').value) || 0;
            const itemTotal = quantity * purchasePrice;
            rowElement.querySelector('.item-total-display').value = 'Rp ' + itemTotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            updateGrandTotal();
        }
        function attachCalculationListeners(rowElement) { /* ... JavaScript ... */ 
            rowElement.querySelector('.quantity-input').addEventListener('input', () => updateItemTotal(rowElement));
            rowElement.querySelector('.purchase-price-input').addEventListener('input', () => updateItemTotal(rowElement));
        }
        function clearProductSearch(rowElement) { /* ... JavaScript ... */ 
            rowElement.querySelector('.product-search-input').value = '';
            rowElement.querySelector('.actual-product-id').value = '';
            const resultsContainer = rowElement.querySelector('.product-search-results');
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            rowElement.querySelector('input[name="purchase_price[]"]').value = '';
            rowElement.querySelector('input[name="sell_price[]"]').value = '';
            rowElement.querySelector('input[name="batch_number[]"]').value = '';
            const expiryInput = rowElement.querySelector('input[name="expiry_date[]"]');
            if (expiryInput && expiryInput._flatpickr) { expiryInput._flatpickr.clear(); } else if (expiryInput) { expiryInput.value = ''; }
            updateItemTotal(rowElement);
        }
        function initializeProductSearch(rowElement) {
            const searchInput = rowElement.querySelector('.product-search-input');
            const resultsContainer = rowElement.querySelector('.product-search-results');
            const hiddenIdInput = rowElement.querySelector('.actual-product-id');
            let currentFilteredProducts = [];

            searchInput.addEventListener('focus', function() {
                const inputRect = searchInput.getBoundingClientRect();
                resultsContainer.style.position = 'fixed';
                resultsContainer.style.top = (inputRect.bottom) + 'px';
                resultsContainer.style.left = inputRect.left + 'px';
                resultsContainer.style.width = inputRect.width + 'px';
                // z-index sudah diatur di CSS, pastikan cukup tinggi (misal: 1050 atau lebih)

                // Jika input sudah ada teksnya dan ada hasil filter, tampilkan
                // Namun, logika utama penampilan ada di event 'input'
                if (searchInput.value.trim().length > 0) {
                    if (currentFilteredProducts.length > 0 || resultsContainer.innerHTML.includes('Produk tidak ditemukan')) {
                        resultsContainer.style.display = 'block';
                    }
                }
            });

            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                resultsContainer.innerHTML = '';
                currentFilteredProducts = [];

                if (term.length < 1) {
                    resultsContainer.style.display = 'none';
                    return;
                }

                // Pastikan posisi fixed tetap terjaga saat input berubah (jika diperlukan)
                const inputRect = searchInput.getBoundingClientRect();
                resultsContainer.style.position = 'fixed';
                resultsContainer.style.top = (inputRect.bottom) + 'px';
                resultsContainer.style.left = inputRect.left + 'px';
                resultsContainer.style.width = inputRect.width + 'px';

                currentFilteredProducts = productsList.filter(p =>
                    p.product_name.toLowerCase().includes(term) ||
                    (p.barcode && String(p.barcode).toLowerCase().includes(term)) ||
                    (p.product_code && String(p.product_code).toLowerCase().includes(term))
                );

                if (currentFilteredProducts.length > 0) {
                    currentFilteredProducts.forEach(product => {
                        const div = document.createElement('div');
                        div.innerHTML = `<span class="result-name">${product.product_name}</span> <span class="result-details">(Kode: ${product.product_code || 'N/A'}, BC: ${product.barcode || 'N/A'}, Stok: ${product.stock_quantity || 0})</span>`;
                        
                        div.addEventListener('click', function() {
                            searchInput.value = product.product_name;
                            hiddenIdInput.value = product.product_id;
                            resultsContainer.innerHTML = '';
                            resultsContainer.style.display = 'none'; // Sembunyikan setelah dipilih

                            rowElement.querySelector('input[name="purchase_price[]"]').value = product.cost_price !== undefined ? product.cost_price : '';
                            rowElement.querySelector('input[name="sell_price[]"]').value = product.price !== undefined ? product.price : '';
                            rowElement.querySelector('input[name="batch_number[]"]').value = product.default_batch_number || '';
                            
                            const expiryDateInputEl = rowElement.querySelector('input[name="expiry_date[]"]');
                            if (expiryDateInputEl) {
                                if (product.default_expiry_date && expiryDateInputEl._flatpickr) {
                                    expiryDateInputEl._flatpickr.setDate(product.default_expiry_date, true);
                                } else if (expiryDateInputEl) {
                                    expiryDateInputEl.value = product.default_expiry_date || '';
                                }
                            }
                            updateItemTotal(rowElement);
                        });
                        resultsContainer.appendChild(div);
                    });
                    resultsContainer.style.display = 'block';
                } else {
                    resultsContainer.innerHTML = '<div>Produk tidak ditemukan</div>';
                    resultsContainer.style.display = 'block';
                }
            });

            searchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    if (resultsContainer.style.display === 'block' && currentFilteredProducts.length === 1) {
                        event.preventDefault(); 
                        const firstResultDiv = resultsContainer.querySelector('div');
                        if (firstResultDiv && typeof firstResultDiv.click === 'function') {
                            firstResultDiv.click();
                        }
                    } else if (resultsContainer.style.display === 'block' && currentFilteredProducts.length > 1) {
                        event.preventDefault();
                    }
                }
            });
        
            // Sembunyikan dropdown jika klik di luar area pencarian
            document.addEventListener("click", function (event) {
                if (searchInput && resultsContainer && !searchInput.contains(event.target) && !resultsContainer.contains(event.target)) {
                    resultsContainer.style.display = "none";
                }
            });
        }


        function attachRemoveButtonListener(button) { /* ... JavaScript ... */ 
            button.addEventListener('click', function () {
                const itemRows = itemsContainer.querySelectorAll('.item-row');
                if (itemRows.length > 1) {
                    const rowToRemove = this.closest('.item-row');
                    const expiryInput = rowToRemove.querySelector('.expiry-date-input');
                    if (expiryInput && expiryInput._flatpickr) { expiryInput._flatpickr.destroy(); }
                    rowToRemove.remove(); updateGrandTotal();
                } else { Swal.fire("Info", "Minimal harus ada 1 item pembelian.", "warning"); }
            });
        }
        function addItemRow() { /* ... JavaScript ... */ 
            const newItemRow = document.createElement('tr'); newItemRow.classList.add('item-row'); newItemRow.innerHTML = itemRowTemplateHTML; 
            itemsContainer.appendChild(newItemRow);
            initializeProductSearch(newItemRow);
            const newExpiryInput = newItemRow.querySelector('.expiry-date-input');
            if (newExpiryInput) { initializeExpiryDateFlatpickr(newExpiryInput); }
            const newRemoveBtn = newItemRow.querySelector('.removeItemBtn');
            if (newRemoveBtn) { attachRemoveButtonListener(newRemoveBtn); }
            attachCalculationListeners(newItemRow);
            newItemRow.querySelectorAll('input[type="tel"], input[type="text"], input[type="hidden"]').forEach(input => { if (input.classList.contains('quantity-input')) { input.value = '1'; } else if (!input.classList.contains('item-total-display')) { input.value = ''; }});
            newItemRow.querySelector('.item-total-display').value = 'Rp 0';
            if (newExpiryInput && newExpiryInput._flatpickr) { newExpiryInput._flatpickr.clear(); }
            updateItemTotal(newItemRow);
        }
        addItemBtn.addEventListener('click', addItemRow);

        const purchaseForm = document.getElementById('purchaseForm');
        if(purchaseForm){ purchaseForm.addEventListener('submit', function(event){ /* ... JavaScript form validation ... */ 
            const itemRowsNodeList = itemsContainer.querySelectorAll('.item-row'); let hasFilledItem = false;
            if (itemRowsNodeList.length === 0) { Swal.fire('Input Tidak Lengkap', 'Harap tambahkan minimal satu item pembelian.', 'warning'); event.preventDefault(); return; }
            for (let i = 0; i < itemRowsNodeList.length; i++) { if (itemRowsNodeList[i].querySelector('.actual-product-id').value) { hasFilledItem = true; break; }}
            if (!hasFilledItem) { Swal.fire('Input Tidak Lengkap', 'Harap pilih produk untuk setidaknya satu item pembelian.', 'warning'); event.preventDefault(); return; }
            let formIsValid = true;
            itemRowsNodeList.forEach((row, index) => {
                const actualProductIdInput = row.querySelector('.actual-product-id'); const productSearchDisplay = row.querySelector('.product-search-input');
                if (actualProductIdInput.value) { 
                    const quantityInput = row.querySelector('.quantity-input'); const purchasePriceInput = row.querySelector('.purchase-price-input'); const sellPriceInput = row.querySelector('.sell-price-input'); let rowErrorMessages = [];
                    if (!quantityInput.value || parseFloat(quantityInput.value) <= 0) { rowErrorMessages.push("Kuantitas (>0)"); }
                    if (purchasePriceInput.value === '' || parseFloat(purchasePriceInput.value) < 0) { rowErrorMessages.push("Harga Beli (>=0)"); }
                    if (sellPriceInput.value === '' || parseFloat(sellPriceInput.value) < 0) { rowErrorMessages.push("Harga Jual (>=0)"); }
                    if (rowErrorMessages.length > 0) { Swal.fire('Data Tidak Valid', `Item pada baris ke-${index + 1} (Produk: ${productSearchDisplay.value || 'Tidak Diketahui'}) tidak lengkap/valid: ${rowErrorMessages.join(', ')}`, 'warning'); formIsValid = false; }
                } else if (productSearchDisplay.value.trim() !== '') { Swal.fire('Produk Tidak Valid', `Produk pada baris ke-${index + 1} ("${productSearchDisplay.value}") belum dipilih dari daftar. Harap pilih produk yang valid.`, 'warning'); formIsValid = false; }
            });
            const paymentStatus = document.getElementById('payment_status').value; const initialAmountPaidInput = document.getElementById('initial_amount_paid'); const initialAmountPaidValue = parseFloat(initialAmountPaidInput.value);
            if (paymentStatus === 'cicil' && initialAmountPaidInput.value.trim() !== '' && (isNaN(initialAmountPaidValue) || initialAmountPaidValue <= 0)) { Swal.fire('Data Tidak Valid', 'Jika status "Cicil" dan Jumlah Bayar Awal diisi, nilainya harus lebih besar dari 0.', 'warning'); formIsValid = false; }
            if (paymentStatus === 'cicil' && !isNaN(initialAmountPaidValue) && initialAmountPaidValue > 0) {
                let grandTotal = 0; itemsContainer.querySelectorAll('.item-row').forEach(row => { const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0; const purchasePrice = parseFloat(row.querySelector('.purchase-price-input').value) || 0; grandTotal += quantity * purchasePrice; });
                if (initialAmountPaidValue > grandTotal) { Swal.fire('Data Tidak Valid', 'Jumlah Bayar Awal tidak boleh melebihi Total Pembelian.', 'warning'); formIsValid = false; }
            }
            if (!formIsValid) { event.preventDefault(); }
        });}

        const purchaseDateInput = document.getElementById('purchase_date'); const dueDaysInput = document.getElementById('due_days'); const dueDateInput = document.getElementById('due_date');
        function calculateDueDate() { /* ... JavaScript for due date calculation ... */ 
            if (purchaseDateInput && purchaseDateInput._flatpickr && purchaseDateInput._flatpickr.selectedDates.length > 0 && dueDaysInput.value !== '') {
                const pDate = new Date(purchaseDateInput._flatpickr.selectedDates[0]); const days = parseInt(dueDaysInput.value, 10);
                if (!isNaN(days) && days >= 0) { pDate.setDate(pDate.getDate() + days); if (dueDateInput && dueDateInput._flatpickr) { dueDateInput._flatpickr.setDate(pDate, true); } else if (dueDateInput) { const year = pDate.getFullYear(); const month = ('0' + (pDate.getMonth() + 1)).slice(-2); const day = ('0' + pDate.getDate()).slice(-2); dueDateInput.value = `${year}-${month}-${day}`;}}
            }
        }
        if (purchaseDateInput && purchaseDateInput._flatpickr) { purchaseDateInput._flatpickr.config.onClose.push(calculateDueDate); } else if (purchaseDateInput) { purchaseDateInput.addEventListener('change', calculateDueDate); }
        if (dueDaysInput) { dueDaysInput.addEventListener('input', calculateDueDate); }
        
        const paymentStatusSelect = document.getElementById('payment_status');
        const initialInstallmentSection = document.getElementById('initial-installment-section');
        const cicilInfoPlaceholder = document.getElementById('cicil-info-placeholder');

        function toggleInitialInstallmentSection() {
            if (paymentStatusSelect.value === 'cicil') {
                initialInstallmentSection.classList.remove('hidden');
                if(cicilInfoPlaceholder) cicilInfoPlaceholder.classList.add('hidden');
            } else {
                initialInstallmentSection.classList.add('hidden');
                 if(cicilInfoPlaceholder) cicilInfoPlaceholder.classList.remove('hidden');
            }
        }
        if (paymentStatusSelect) { paymentStatusSelect.addEventListener('change', toggleInitialInstallmentSection); toggleInitialInstallmentSection(); }
    });
    </script>
    
</body>
</html>
