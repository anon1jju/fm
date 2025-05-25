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
        $received_status = $_POST['received_status'] ?? 'belum_diterima'; 
        $notes = $_POST['notes'] ?? null;
        $user_id = $_SESSION['user_id'] ?? null;

        // Critical: product_id now comes from the hidden input
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
                // Ensure all corresponding arrays have an entry for this key
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
                        if ($final_due_date === null && is_numeric($due_days_input)) { // check if due_days_input is numeric
                            $days_to_add = (int)$due_days_input;
                            if ($days_to_add >= 0) { // ensure days are not negative
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

                    foreach ($product_ids as $key => $product_id) {
                        if (empty($product_id)) continue; // Skip if product_id is empty for some reason

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
                        $stmt_item->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt_item->bindParam(':batch_number', $batch_number_item);
                        $stmt_item->bindParam(':expiry_date', $expiry_date_item);
                        $stmt_item->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_item->bindParam(':unit_price', $unit_price);
                        //$stmt_item->bindParam(':sell_price', $sell_price_item); 
                        $stmt_item->bindParam(':item_total', $item_total);
                        // Assuming received_quantity is same as quantity on initial purchase
                        $stmt_item->bindParam(':received_quantity', $quantity, PDO::PARAM_INT); 
                        $stmt_item->execute();
                        
                        // Update product stock, cost price, and selling price
                        $sql_update_product = "UPDATE products SET stock_quantity = stock_quantity + :quantity, price = :sell_price_form, cost_price = :purchase_price_form WHERE product_id = :product_id";
                        $stmt_update_product = $pdo->prepare($sql_update_product);
                        $stmt_update_product->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_update_product->bindParam(':sell_price_form', $sell_price_item);
                        $stmt_update_product->bindParam(':purchase_price_form', $unit_price);
                        $stmt_update_product->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt_update_product->execute();

                        if (!empty($batch_number_item)) { 
                             $sql_prod_batch = "INSERT INTO product_batches (product_id, supplier_id, batch_number, expiry_date, purchase_price,  quantity, remaining_quantity, created_at, updated_at)
                                               VALUES (:product_id, :supplier_id, :batch_number, :expiry_date, :purchase_price, :quantity, :quantity, NOW(), NOW())
                                               ON DUPLICATE KEY UPDATE 
                                               expiry_date = VALUES(expiry_date), 
                                               purchase_price = VALUES(purchase_price), 
                                               quantity = quantity + VALUES(quantity),
                                               remaining_quantity = remaining_quantity + VALUES(remaining_quantity),
                                               updated_at = NOW()";
                            $stmt_prod_batch = $pdo->prepare($sql_prod_batch);
                            $stmt_prod_batch->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT); // supplier_id from main form
                            //$stmt_prod_batch->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':batch_number', $batch_number_item);
                            $stmt_prod_batch->bindParam(':expiry_date', $expiry_date_item);
                            $stmt_prod_batch->bindParam(':purchase_price', $unit_price);
                            //$stmt_prod_batch->bindParam(':sell_price', $sell_price_item); 
                            $stmt_prod_batch->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                            $stmt_prod_batch->execute(); // Uncommented this line
                        }
                    }

                    $sql_update_total = "UPDATE purchases SET total_amount = :total_amount WHERE purchase_id = :purchase_id";
                    $stmt_update_total = $pdo->prepare($sql_update_total);
                    $stmt_update_total->bindParam(':total_amount', $total_purchase_amount);
                    $stmt_update_total->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                    $stmt_update_total->execute();

                    $pdo->commit();
                    $message = "Pembelian berhasil ditambahkan dengan ID: " . $purchase_id . ". Total: Rp " . number_format($total_purchase_amount, 0, ',', '.');
                    $_POST = array(); // Clear POST data to reset form

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
    <!-- Select2 CSS is removed as it's not used for product search anymore -->
    <style>
        .item-row:not(:first-child) { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; }
        .item-total-price { font-weight: bold; text-align: right; }
        
        #purchaseItemsSectionWrapper { /* Renamed for clarity */
            max-height: 400px; 
            overflow-y: auto;
            padding-right: 10px; 
            margin-bottom: 1rem; 
        }

        .grand-total-container { 
            margin-top: 1rem; 
            padding-top: 1rem; 
            border-top: 2px solid #333; 
            text-align: right; 
        }
        .grand-total-container span { font-size: 1.25rem; font-weight: bold; }

        /* Styles for product search results */
        .product-search-input-container {
            position: relative; /* For positioning the results dropdown */
        }
        .product-search-results {
             position: absolute;
             background-color: white;
             border: 1px solid #ddd;
             border-top: none;
             z-index: 1001; /* Ensure it's above other elements */
             width: 200%; /* Match input width */
             max-height: 300px;
             overflow-y: auto;
             box-shadow: 0 4px 8px rgba(0,0,0,0.1);
         }
        .product-search-results div {
            padding: 8px 12px;
            cursor: pointer;
        }
        .product-search-results div:hover {
            background-color: #f0f0f0;
        }
        .product-search-results .result-name {
            font-weight: bold;
        }
        .product-search-results .result-details {
            font-size: 0.85em;
            color: #555;
        }

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
                            <div class="box-header"><div class="box-title"><span><a href="beli.php" class="ti-btn ti-btn-sm ti-btn-info"><i class="ri-arrow-left-s-line"></i>Kembali</a></span> Informasi Pembelian Baru</div></div>
                            <div class="box-body p-6">
                                <?php if ($message): ?>
                                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Berhasil!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Error!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="beli.php" id="purchaseForm">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6 items-end">
                                        <div class="relative">
                                            <label for="invoice_number_supplier" class="form-label">No. Invoice Supplier <span class="text-red-500">*</span></label>
                                            
                                            <input type="text" 
                                                   class="ti-form-input pr-10" 
                                                   id="invoice_number_supplier" 
                                                   name="invoice_number_supplier" 
                                                   value="<?php echo isset($_POST['invoice_number_supplier']) ? htmlspecialchars($_POST['invoice_number_supplier']) : ''; ?>" 
                                                   required>
                                            
                                            <!-- Icon di dalam input -->
                                            <button type="button" 
                                                    onclick="generateInvoiceNumber()" 
                                                    class="absolute right-2 top-[35px] text-gray-500 hover:text-blue-500">
                                                <i class="ri-loop-right-line text-lg"></i>
                                            </button>
                                        </div>

                                        <div>
                                            <label for="supplier_id" class="form-label">Supplier <span class="text-red-500">*</span></label>
                                            <select id="supplier_id" name="supplier_id" class="ti-form-select select2" required>
                                                <option value="">Pilih Supplier</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['supplier_id']; ?>" 
                                                        <?php echo (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['supplier_id'] ? 'selected' : ''); ?>>
                                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    
                                        <div>
                                            <label for="purchase_date" class="form-label">Tanggal Pembelian <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input flatpickr-date" id="purchase_date" name="purchase_date" value="<?php
                                                echo htmlspecialchars(isset($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d'));
                                            ?>" required>
                                        </div>
                                    </div>

                                    <hr class="my-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-xl font-semibold">Item Pembelian</h3>
                                    </div> 
                                    
                                    <div id="purchaseItemsSectionWrapper" class="max-h-[500px] overflow-auto border border-gray-300 dark:border-gray-700 rounded-md mb-4">
                                        <table class="min-w-full divide-y divide-gray-200 border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                                            <thead class="bg-gray-200 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Beli <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Jual <span class="text-red-500">*</span></th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Item</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expire</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsContainer" class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                                <tr class="item-row">
                                                    <td class="px-4 py-2 whitespace-nowrap product-search-input-container">
                                                        <input type="text"
                                                               class="ti-form-input product-search-input"
                                                               name="product_search_display[]" 
                                                               placeholder="Cari Produk/Kode/Barcode..."
                                                               style="width: 100%;"
                                                               autocomplete="off">
                                                        <input type="hidden" name="product_id[]" class="actual-product-id"> <!-- This holds the actual product_id -->
                                                        <div class="product-search-results"></div> <!-- Container for search results -->
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="tel" name="quantity[]" class="ti-form-input quantity-input text-center" placeholder="Qty" required value="1" style="width: 60px;">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="tel" step="any" name="purchase_price[]" class="ti-form-input purchase-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Beli">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="tel" step="any" name="sell_price[]" class="ti-form-input sell-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Jual">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="text" class="ti-form-input item-total-display" readonly placeholder="Rp 0" style="background-color: #e9ecef; width: 120px;">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="text" name="batch_number[]" class="ti-form-input batch-number-input" placeholder="Batch" style="width: 100px;">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input type="tel" name="expiry_date[]" class="ti-form-input flatpickr-date expiry-date-input" style="width: 120px;">
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" id="addItemBtn" class="ti-btn ti-btn-sm ti-btn-success"><i class="ri-add-line me-1"></i>Tambah Item</button>

                                    <div class="grand-total-container">
                                        <label class="form-label text-lg font-bold">Total Pembelian:</label>
                                        <span id="grandTotalDisplay">Rp 0</span>
                                    </div>
                                    
                                    <hr class="my-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    <div>
                                        <h3 class="text-xl font-semibold mb-4">Status & Catatan</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label for="payment_status" class="form-label">Status Pembayaran</label>
                                                <select id="payment_status" name="payment_status" class="ti-form-select">
                                                    <option value="hutang" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'hutang' ? 'selected' : (!isset($_POST['payment_status']) ? 'selected' : '')); ?>>Hutang</option>
                                                    <option value="cicil" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'cicil' ? 'selected' : ''); ?>>Cicil</option>
                                                    <option value="lunas" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] == 'lunas' ? 'selected' : ''); ?>>Lunas</option>
                                                </select>
                                            </div>
                                
                                            <div class="flex flex-col md:flex-row gap-4 items-end">
                                                <div class="w-full md:w-1/2">
                                                    <label for="due_days" class="form-label text-sm">Hari J.Tempo</label>
                                                    <input type="tel" class="ti-form-input text-center" id="due_days" name="due_days"
                                                        value="<?php echo isset($_POST['due_days']) ? htmlspecialchars($_POST['due_days']) : '30'; ?>" min="0">
                                                </div>
                                            
                                                <div class="w-full md:w-1/2">
                                                    <label for="due_date" class="form-label text-sm">Tgl. Jatuh Tempo</label>
                                                    <input type="text" class="ti-form-input flatpickr-date" id="due_date" name="due_date"
                                                        placeholder="Otomatis/Manual" value="<?php
                                                            $display_due_date_form = '';
                                                            if (isset($_POST['due_date']) && !empty($_POST['due_date'])) {
                                                                $display_due_date_form = $_POST['due_date'];
                                                            } else if (isset($_POST['purchase_date'], $_POST['due_days'])) {
                                                                try {
                                                                    $pd_form = $_POST['purchase_date'];
                                                                    $dd_form = (int)$_POST['due_days'];
                                                                    if ($dd_form >= 0) {
                                                                        $date_obj_form = new DateTime($pd_form);
                                                                        $date_obj_form->add(new DateInterval("P{$dd_form}D"));
                                                                        $display_due_date_form = $date_obj_form->format('Y-m-d');
                                                                    }
                                                                } catch (Exception $e) { }
                                                            } else {
                                                                try {
                                                                    $pd_form = date('Y-m-d');
                                                                    $dd_form = 30;
                                                                    $date_obj_form = new DateTime($pd_form);
                                                                    $date_obj_form->add(new DateInterval("P{$dd_form}D"));
                                                                    $display_due_date_form = $date_obj_form->format('Y-m-d');
                                                                } catch (Exception $e) { }
                                                            }
                                                            echo htmlspecialchars($display_due_date_form);
                                                        ?>">
                                                </div>
                                            </div>
                                            <div>
                                                <label for="notes" class="form-label">Catatan</label>
                                                <textarea class="ti-form-input" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan: Form Cicil (kosong dulu atau bisa kamu isi form-nya) -->
                                    <div class="space-y-4">
                                        <div>
                                            <label for="installment_total" class="form-label">Cicil</label>
                                            <input type="number" class="ti-form-input" id="installment_total" name="installment_total" min="1" value="<?php echo isset($_POST['installment_total']) ? htmlspecialchars($_POST['installment_total']) : ''; ?>" placeholder="Nominal harga">
                                        </div>
                                    
                                        <div>
                                            <label for="installment_start_date" class="form-label">Sisa Hutang</label>
                                            <input type="text" class="ti-form-input" id="installment_start_date" name="installment_start_date" value="<?php echo isset($_POST['installment_start_date']) ? htmlspecialchars($_POST['installment_start_date']) : ''; ?>" readonly>
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
    <!-- autoformatexpire.js might need review if it interacts with product expiry date population -->
    <!-- <script src="../assets/js/autoformatexpire.js"></script> --> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        function generateInvoiceNumber() {
            // Menghasilkan nomor acak 5 digit
            const randomNumber = Math.floor(10000 + Math.random() * 90000);
            document.getElementById('invoice_number_supplier').value = randomNumber;
        }
    </script>
    
    <script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Pilih Supplier",
            allowClear: true
        });
    });
    </script>
    
    <script>
        document.getElementById('payment_status').addEventListener('change', function () {
            const cicilSection = document.getElementById('cicil-section');
            if (this.value === 'cicil') {
                cicilSection.classList.remove('hidden');
            } else {
                cicilSection.classList.add('hidden');
            }
        });
    
        // Jalankan saat load untuk menyembunyikan jika bukan cicil
        window.addEventListener('DOMContentLoaded', () => {
            const status = document.getElementById('payment_status').value;
            const cicilSection = document.getElementById('cicil-section');
            if (status !== 'cicil') {
                cicilSection.classList.add('hidden');
            }
        });
    </script>


    <!-- Select2 JS is removed as it's not used for product search -->

    <script>
    // Make products_list available to the script
    const productsList = <?php echo json_encode($products_list_for_js); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const itemsContainer = document.getElementById('itemsContainer');
        const addItemBtn = document.getElementById('addItemBtn');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');

        // --- TEMPLATE DEFINITION (MUST BE PRISTINE HTML) ---
        // This function defines the raw HTML for a new row.
        function getDefaultItemRowHTML() {
            return `
                <td class="px-4 py-2 whitespace-nowrap product-search-input-container">
                    <input type="text" class="ti-form-input product-search-input" name="product_search_display[]" placeholder="Cari Produk/Kode/Barcode..." style="width: 100%;" autocomplete="off">
                    <input type="hidden" name="product_id[]" class="actual-product-id">
                    <div class="product-search-results" style="display: none;"></div>
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="tel" name="quantity[]" class="ti-form-input quantity-input text-center" placeholder="Qty" required value="1" style="width: 60px;">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="tel" step="any" name="purchase_price[]" class="ti-form-input purchase-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Beli">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="tel" step="any" name="sell_price[]" class="ti-form-input sell-price-input" style="width: 100px" min="0" value="" required placeholder="Harga Jual">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="text" class="ti-form-input item-total-display" readonly placeholder="Rp 0" style="background-color: #e9ecef; width: 120px;">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="text" name="batch_number[]" class="ti-form-input batch-number-input" placeholder="Batch" style="width: 100px;">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <input type="tel" name="expiry_date[]" class="ti-form-input expiry-date-input" style="width: 120px;">
                </td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i></button>
                </td>
            `;
        }
        // Menggunakan fungsi untuk mendapatkan template HTML yang bersih
        const itemRowTemplateHTML = getDefaultItemRowHTML();

        // --- GLOBAL FLATPICKR INITIALIZATION (untuk input tanggal di luar item) ---
        // Pastikan selector ini TIDAK mengenai input .expiry-date-input di dalam baris item.
        // Anda bisa menggunakan kelas yang lebih spesifik untuk tanggal pembelian/jatuh tempo jika perlu.
        document.querySelectorAll('.flatpickr-date:not(.expiry-date-input)').forEach(el => {
            flatpickr(el, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: true
            });
        });
        
        // --- FUNGSI UNTUK INISIALISASI FLATPICKR PADA INPUT TANGGAL KEDALUWARSA ITEM ---
        function initializeExpiryDateFlatpickr(element) {
            if (element._flatpickr) { // Hancurkan instance yang ada jika ada
                element._flatpickr.destroy();
            }
            flatpickr(element, { 
                dateFormat: "Y-m-d", 
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: true, 
                placeholder: "YYYY-MM-DD" // Placeholder untuk altInput
            });
        }
        
        // --- INISIALISASI BARIS ITEM YANG SUDAH ADA (DARI PHP) ---
        itemsContainer.querySelectorAll('.item-row').forEach(row => {
            const expiryInput = row.querySelector('.expiry-date-input');
            if (expiryInput) {
                initializeExpiryDateFlatpickr(expiryInput);
            }
            initializeProductSearch(row);
            attachCalculationListeners(row);
            const removeBtn = row.querySelector('.removeItemBtn');
            if (removeBtn) {
                 attachRemoveButtonListener(removeBtn);
            }
            updateItemTotal(row); 
        });

        if (itemsContainer.querySelectorAll('.item-row').length > 0) {
            updateGrandTotal();
        }

        // --- FUNGSI UTILITAS LAINNYA (updateGrandTotal, updateItemTotal, dll.) ---
        // (Fungsi-fungsi ini sebagian besar tetap sama dari kode Anda sebelumnya)
        function updateGrandTotal() {
            let grandTotal = 0;
            itemsContainer.querySelectorAll('.item-row').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                const purchasePrice = parseFloat(row.querySelector('.purchase-price-input').value) || 0;
                grandTotal += quantity * purchasePrice;
            });
            grandTotalDisplay.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        function updateItemTotal(rowElement) {
            const quantity = parseFloat(rowElement.querySelector('.quantity-input').value) || 0;
            const purchasePrice = parseFloat(rowElement.querySelector('.purchase-price-input').value) || 0;
            const itemTotal = quantity * purchasePrice;
            rowElement.querySelector('.item-total-display').value = 'Rp ' + itemTotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            updateGrandTotal();
        }

        function attachCalculationListeners(rowElement) {
            rowElement.querySelector('.quantity-input').addEventListener('input', () => updateItemTotal(rowElement));
            rowElement.querySelector('.purchase-price-input').addEventListener('input', () => updateItemTotal(rowElement));
        }

        function clearProductSearch(rowElement) {
            rowElement.querySelector('.product-search-input').value = '';
            rowElement.querySelector('.actual-product-id').value = '';
            const resultsContainer = rowElement.querySelector('.product-search-results');
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            
            rowElement.querySelector('input[name="purchase_price[]"]').value = '';
            rowElement.querySelector('input[name="sell_price[]"]').value = '';
            rowElement.querySelector('input[name="batch_number[]"]').value = '';
            const expiryInput = rowElement.querySelector('input[name="expiry_date[]"]');
            if (expiryInput && expiryInput._flatpickr) {
                expiryInput._flatpickr.clear();
            } else if (expiryInput) {
                expiryInput.value = '';
            }
            updateItemTotal(rowElement);
        }
        
        function initializeProductSearch(rowElement) {
            const searchInput = rowElement.querySelector('.product-search-input');
            const resultsContainer = rowElement.querySelector('.product-search-results');
            const hiddenIdInput = rowElement.querySelector('.actual-product-id');

            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                resultsContainer.innerHTML = '';
                if (term.length < 1) {
                    resultsContainer.style.display = 'none';
                    if (hiddenIdInput.value !== '') {
                        clearProductSearch(rowElement);
                    }
                    return;
                }

                const filteredProducts = productsList.filter(p => 
                    p.product_name.toLowerCase().includes(term) ||
                    (p.barcode && p.barcode.toLowerCase().includes(term)) ||
                    (p.product_code && p.product_code.toLowerCase().includes(term))
                );

                if (filteredProducts.length > 0) {
                    filteredProducts.forEach(product => {
                        const div = document.createElement('div');
                        div.innerHTML = `<span class="result-name">${product.product_name}</span> <span class="result-details">(BC: ${product.barcode || 'N/A'}, Stok: ${product.stock_quantity || '0'})</span>`;
                        div.addEventListener('click', function() {
                            searchInput.value = product.product_name;
                            hiddenIdInput.value = product.product_id;
                            resultsContainer.innerHTML = '';
                            resultsContainer.style.display = 'none';

                            rowElement.querySelector('input[name="purchase_price[]"]').value = product.cost_price !== undefined ? product.cost_price : '';
                            rowElement.querySelector('input[name="sell_price[]"]').value = product.price !== undefined ? product.price : '';
                            rowElement.querySelector('input[name="batch_number[]"]').value = product.default_batch_number || '';
                            
                            const expiryDateInputEl = rowElement.querySelector('input[name="expiry_date[]"]');
                            if (expiryDateInputEl) {
                                if (product.default_expiry_date && expiryDateInputEl._flatpickr) {
                                    expiryDateInputEl._flatpickr.setDate(product.default_expiry_date, true);
                                } else if (product.default_expiry_date) {
                                     expiryDateInputEl.value = product.default_expiry_date; // Fallback jika flatpickr belum ada
                                } else if (expiryDateInputEl._flatpickr) {
                                    expiryDateInputEl._flatpickr.clear();
                                } else {
                                     expiryDateInputEl.value = '';
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

            document.addEventListener('click', function(event) {
                // Pastikan searchInput dan resultsContainer ada sebelum mengakses contains
                if (searchInput && resultsContainer && !searchInput.contains(event.target) && !resultsContainer.contains(event.target)) {
                    resultsContainer.style.display = 'none';
                }
            });
        }

        function attachRemoveButtonListener(button) {
            button.addEventListener('click', function () {
                const itemRows = itemsContainer.querySelectorAll('.item-row');
                if (itemRows.length > 1) {
                    const rowToRemove = this.closest('.item-row');
                    const expiryInput = rowToRemove.querySelector('.expiry-date-input');
                    if (expiryInput && expiryInput._flatpickr) {
                        expiryInput._flatpickr.destroy();
                    }
                    rowToRemove.remove();
                    updateGrandTotal();
                } else {
                    Swal.fire("Info", "Minimal harus ada 1 item pembelian.", "warning");
                }
            });
        }
        
        // --- LOGIKA TOMBOL TAMBAH ITEM ---
        addItemBtn.addEventListener('click', function () {
            const newItemRow = document.createElement('tr');
            newItemRow.classList.add('item-row');
            newItemRow.innerHTML = itemRowTemplateHTML; // Gunakan template HTML yang bersih
            
            itemsContainer.appendChild(newItemRow);
            
            // Inisialisasi komponen untuk baris BARU
            initializeProductSearch(newItemRow);
            const newExpiryInput = newItemRow.querySelector('.expiry-date-input');
            if (newExpiryInput) {
                initializeExpiryDateFlatpickr(newExpiryInput); // Inisialisasi pada input yang baru dan bersih
            }
            const newRemoveBtn = newItemRow.querySelector('.removeItemBtn');
            if (newRemoveBtn) {
                attachRemoveButtonListener(newRemoveBtn);
            }
            attachCalculationListeners(newItemRow);
            
            // Bersihkan nilai untuk baris baru
            newItemRow.querySelectorAll('input[type="tel"], input[type="text"], input[type="hidden"]').forEach(input => {
                 if (input.classList.contains('quantity-input')) {
                    input.value = '1';
                } else if (!input.classList.contains('item-total-display')) {
                    input.value = '';
                }
            });
            newItemRow.querySelector('.item-total-display').value = 'Rp 0';
            
            // Bersihkan nilai Flatpickr untuk input tanggal kedaluwarsa yang baru
            if (newExpiryInput && newExpiryInput._flatpickr) {
                newExpiryInput._flatpickr.clear();
            }

            updateItemTotal(newItemRow);
        });

        // --- VALIDASI FORM SUBMISSION ---
        const purchaseForm = document.getElementById('purchaseForm');
        if(purchaseForm){
            purchaseForm.addEventListener('submit', function(event){
                const itemRowsNodeList = itemsContainer.querySelectorAll('.item-row');
                let hasFilledItem = false;

                if (itemRowsNodeList.length === 0) {
                     Swal.fire('Input Tidak Lengkap', 'Harap tambahkan minimal satu item pembelian.', 'warning');
                     event.preventDefault(); 
                     return;
                }
                
                for (let i = 0; i < itemRowsNodeList.length; i++) {
                    if (itemRowsNodeList[i].querySelector('.actual-product-id').value) {
                        hasFilledItem = true;
                        break;
                    }
                }

                if (!hasFilledItem) {
                    Swal.fire('Input Tidak Lengkap', 'Harap pilih produk untuk setidaknya satu item pembelian.', 'warning');
                    event.preventDefault();
                    return;
                }

                let formIsValid = true;
                itemRowsNodeList.forEach((row, index) => {
                    const actualProductIdInput = row.querySelector('.actual-product-id');
                    const productSearchDisplay = row.querySelector('.product-search-input');
                    
                    if (actualProductIdInput.value) { 
                        const quantityInput = row.querySelector('.quantity-input');
                        const purchasePriceInput = row.querySelector('.purchase-price-input');
                        const sellPriceInput = row.querySelector('.sell-price-input');
                        let rowErrorMessages = [];

                        if (!quantityInput.value || parseFloat(quantityInput.value) <= 0) {
                            rowErrorMessages.push("Kuantitas (>0)");
                        }
                        if (purchasePriceInput.value === '' || parseFloat(purchasePriceInput.value) < 0) {
                            rowErrorMessages.push("Harga Beli (>=0)");
                        }
                        if (sellPriceInput.value === '' || parseFloat(sellPriceInput.value) < 0) {
                            rowErrorMessages.push("Harga Jual (>=0)");
                        }

                        if (rowErrorMessages.length > 0) {
                            Swal.fire('Data Tidak Valid', `Item pada baris ke-${index + 1} (Produk: ${productSearchDisplay.value || 'Tidak Diketahui'}) tidak lengkap/valid: ${rowErrorMessages.join(', ')}.`, 'error');
                            formIsValid = false; 
                        }
                    } else if (productSearchDisplay.value.trim() !== '') {
                         Swal.fire('Produk Tidak Valid', `Produk pada baris ke-${index + 1} ("${productSearchDisplay.value}") belum dipilih dari daftar. Harap pilih produk yang valid.`, 'warning');
                        formIsValid = false;
                    }
                });

                if (!formIsValid) {
                    event.preventDefault();
                }
            });
        }

        // --- AUTO CALCULATE DUE DATE ---
        const purchaseDateInput = document.getElementById('purchase_date');
        const dueDaysInput = document.getElementById('due_days');
        const dueDateInput = document.getElementById('due_date');

        function calculateDueDate() {
            if (purchaseDateInput && purchaseDateInput._flatpickr && purchaseDateInput._flatpickr.selectedDates.length > 0 && dueDaysInput.value !== '') {
                const pDate = new Date(purchaseDateInput._flatpickr.selectedDates[0]);
                const days = parseInt(dueDaysInput.value, 10);
                if (!isNaN(days) && days >= 0) {
                    pDate.setDate(pDate.getDate() + days);
                    if (dueDateInput && dueDateInput._flatpickr) {
                        dueDateInput._flatpickr.setDate(pDate, true);
                    } else if (dueDateInput) {
                        const year = pDate.getFullYear();
                        const month = ('0' + (pDate.getMonth() + 1)).slice(-2);
                        const day = ('0' + pDate.getDate()).slice(-2);
                        dueDateInput.value = `${year}-${month}-${day}`;
                    }
                }
            }
        }

        if (purchaseDateInput) {
             // Pastikan _flatpickr ada sebelum mengakses config
            if (purchaseDateInput._flatpickr) {
                 purchaseDateInput._flatpickr.config.onClose.push(calculateDueDate);
            } else { // Fallback jika belum diinisialisasi oleh selector global
                 purchaseDateInput.addEventListener('change', calculateDueDate);
            }
        }
       
        if (dueDaysInput) {
            dueDaysInput.addEventListener('input', calculateDueDate);
        }
    });
    </script>
</body>
</html>
