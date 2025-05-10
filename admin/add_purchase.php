<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php");
    exit();
}

$suppliers = $farma->getSuppliers();
$products_list = $farma->getAllProducts();
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = $farma->getPDO();
    if (!$pdo) {
        $error = "Gagal terhubung ke database.";
    } else {
        $supplier_id = $_POST['supplier_id'] ?? null;
        $invoice_number_supplier = $_POST['invoice_number_supplier'] ?? null;
        $purchase_date = $_POST['purchase_date'] ?? null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $payment_status = $_POST['payment_status'] ?? 'pending';
        $received_status = $_POST['received_status'] ?? 'pending';
        $notes = $_POST['notes'] ?? null;
        $user_id = $_SESSION['user_id'] ?? null;

        $product_ids = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['purchase_price'] ?? []; // Menggunakan nama input 'purchase_price' dari form, tapi akan diinsert ke 'unit_price'
        $batch_numbers_form = $_POST['batch_number'] ?? []; // Batch dari form
        $expiry_dates_form = $_POST['expiry_date'] ?? [];   // Expiry date dari form

        if (empty($supplier_id) || empty($invoice_number_supplier) || empty($purchase_date) || empty($user_id) || empty($product_ids) || count($product_ids) === 0) {
            $error = "Data pembelian utama tidak lengkap atau tidak ada item yang ditambahkan.";
        } else {
            $valid_items = true;
            foreach ($product_ids as $key => $pid) {
                if (empty($pid) || !isset($quantities[$key]) || $quantities[$key] <= 0 || !isset($unit_prices[$key]) || $unit_prices[$key] <= 0) {
                    $error = "Data item pada baris ke-" . ($key + 1) . " tidak lengkap (Produk, Kuantitas, atau Harga Beli).";
                    $valid_items = false;
                    break;
                }
            }

            if ($valid_items) {
                try {
                    $pdo->beginTransaction();
                    $total_purchase_amount = 0;

                    $sql_purchase = "INSERT INTO purchases (supplier_id, invoice_number, purchase_date, due_date, total_amount, payment_status, received_status, user_id, notes, created_at, updated_at) 
                                     VALUES (:supplier_id, :invoice_number, :purchase_date, :due_date, 0, :payment_status, :received_status, :user_id, :notes, NOW(), NOW())";
                    $stmt_purchase = $pdo->prepare($sql_purchase);
                    $stmt_purchase->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                    $stmt_purchase->bindParam(':invoice_number', $invoice_number_supplier);
                    $stmt_purchase->bindParam(':purchase_date', $purchase_date);
                    $stmt_purchase->bindParam(':due_date', $due_date);
                    $stmt_purchase->bindParam(':payment_status', $payment_status);
                    $stmt_purchase->bindParam(':received_status', $received_status);
                    $stmt_purchase->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt_purchase->bindParam(':notes', $notes);
                    $stmt_purchase->execute();
                    $purchase_id = $pdo->lastInsertId();

                    foreach ($product_ids as $key => $product_id) {
                        $quantity = $quantities[$key];
                        $unit_price = $unit_prices[$key]; // Ini adalah harga beli per unit
                        $batch_number_item = $batch_numbers_form[$key] ?? null;
                        $expiry_date_item = !empty($expiry_dates_form[$key]) ? $expiry_dates_form[$key] : null;
                        
                        $item_total = $quantity * $unit_price;
                        $total_purchase_amount += $item_total;

                        // Insert ke tabel purchase_items (SESUAI STRUKTUR TABEL ANDA)
                        $sql_item = "INSERT INTO purchase_items (purchase_id, product_id, batch_number, expiry_date, quantity, unit_price, item_total, received_quantity, created_at, updated_at) 
                                     VALUES (:purchase_id, :product_id, :batch_number, :expiry_date, :quantity, :unit_price, :item_total, :received_quantity, NOW(), NOW())";
                        $stmt_item = $pdo->prepare($sql_item);
                        $stmt_item->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                        $stmt_item->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt_item->bindParam(':batch_number', $batch_number_item); // Dari form
                        $stmt_item->bindParam(':expiry_date', $expiry_date_item);   // Dari form
                        $stmt_item->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_item->bindParam(':unit_price', $unit_price); // Kolom di DB adalah unit_price
                        $stmt_item->bindParam(':item_total', $item_total);
                        // Asumsi received_quantity sama dengan quantity saat pertama kali input, bisa disesuaikan jika ada proses penerimaan terpisah
                        $stmt_item->bindParam(':received_quantity', $quantity, PDO::PARAM_INT); 
                        $stmt_item->execute();
                        
                        // Update stock_quantity di tabel products
                        $sql_update_stock = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE product_id = :product_id";
                        $stmt_update_stock = $pdo->prepare($sql_update_stock);
                        $stmt_update_stock->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_update_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt_update_stock->execute();

                        // OPTIONAL: Logika untuk tabel product_batches jika masih diperlukan
                        // Jika Anda tetap ingin mengisi product_batches sebagai tabel master batch:
                        if (!empty($batch_number_item) && !empty($expiry_date_item)) {
                             // Cek apakah batch sudah ada untuk produk dan supplier ini, jika iya update, jika tidak insert.
                             // Untuk sekarang, kita anggap insert baru atau Anda bisa kembangkan logika cek & update.
                            $sql_prod_batch = "INSERT INTO product_batches (product_id, supplier_id, purchase_id, batch_number, expiry_date, purchase_price, quantity_received, remaining_quantity, created_at, updated_at)
                                          VALUES (:product_id, :supplier_id, :purchase_id, :batch_number, :expiry_date, :purchase_price, :quantity, :quantity, NOW(), NOW())
                                          ON DUPLICATE KEY UPDATE 
                                          expiry_date = VALUES(expiry_date), 
                                          purchase_price = VALUES(purchase_price), 
                                          quantity_received = quantity_received + VALUES(quantity_received), -- atau logika update lain
                                          remaining_quantity = remaining_quantity + VALUES(remaining_quantity), -- atau logika update lain
                                          updated_at = NOW()"; 
                                          // Pastikan product_batches punya UNIQUE key pada (product_id, supplier_id, batch_number) untuk ON DUPLICATE KEY UPDATE
                            $stmt_prod_batch = $pdo->prepare($sql_prod_batch);
                            $stmt_prod_batch->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':batch_number', $batch_number_item);
                            $stmt_prod_batch->bindParam(':expiry_date', $expiry_date_item);
                            $stmt_prod_batch->bindParam(':purchase_price', $unit_price); // Harga beli
                            $stmt_prod_batch->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                            // $stmt_prod_batch->execute(); // Uncomment jika Anda ingin menggunakan ini
                        }
                    }

                    $sql_update_total = "UPDATE purchases SET total_amount = :total_amount WHERE purchase_id = :purchase_id";
                    $stmt_update_total = $pdo->prepare($sql_update_total);
                    $stmt_update_total->bindParam(':total_amount', $total_purchase_amount);
                    $stmt_update_total->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                    $stmt_update_total->execute();

                    $pdo->commit();
                    $message = "Pembelian berhasil ditambahkan dengan ID: " . $purchase_id . ". Total: " . number_format($total_purchase_amount, 2, ',', '.');
                    $_POST = array(); 

                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Gagal menambahkan pembelian: " . $e->getMessage();
                    error_log("PDO Error (add_purchase multi-item): " . $e->getMessage() . " | Query: " . ($stmt_item ? $stmt_item->queryString : "N/A"));
                }
            }
        }
    }
}

// Sisa kode HTML dan JavaScript tetap sama seperti sebelumnya,
// pastikan nama input di form untuk harga beli adalah name="purchase_price[]"
// dan JavaScript Anda juga menangani ini dengan benar.
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .item-row:not(:first-child) { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; }
    </style>
</head>
<body>
    <?php include "includes/switch.php";?>
    <div id="loader"><img src="../assets/images/media/loader.svg" alt=""></div>
    <div class="page">
        <?php include "includes/header.php";?>
        <?php include "includes/sidebar.php";?>
        
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header"><div class="box-title"><span><a href="pembelian_supplier.php" class="ti-btn ti-btn-sm ti-btn-info"><i class="ri-arrow-left-s-line"></i>Kembali</a></span> | Pembelian Baru</div></div>
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

                                <form method="POST" action="add_purchase.php" id="purchaseForm">
                                    <h3 class="text-xl font-semibold mb-4">Informasi Pembelian</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label for="supplier_id" class="form-label">Supplier <span class="text-red-500">*</span></label>
                                            <select id="supplier_id" name="supplier_id" class="ti-form-select" required>
                                                <option value="">Pilih Supplier</option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="invoice_number_supplier" class="form-label">No. Invoice Supplier <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input" id="invoice_number_supplier" name="invoice_number_supplier" value="<?php echo isset($_POST['invoice_number_supplier']) ? htmlspecialchars($_POST['invoice_number_supplier']) : ''; ?>" required>
                                        </div>
                                        <div>
                                            <label for="purchase_date" class="form-label">Tanggal Pembelian <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input flatpickr-date" id="purchase_date" name="purchase_date" value="<?php echo isset($_POST['purchase_date']) ? htmlspecialchars($_POST['purchase_date']) : date('Y-m-d'); ?>" required>
                                        </div>
                                        <div>
                                            <label for="due_date" class="form-label">Tanggal Jatuh Tempo</label>
                                            <input type="text" class="ti-form-input flatpickr-date" id="due_date" name="due_date" value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                                        </div>
                                    </div>

                                    <hr class="my-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-xl font-semibold">Item Pembelian</h3>
                                        <button type="button" id="addItemBtn" class="ti-btn ti-btn-sm ti-btn-success"><i class="ri-add-line me-1"></i>Tambah Item</button>
                                    </div>
                                    
                                    <div id="itemsContainer">
                                        <!-- Baris item pertama (template) -->
                                        <div class="item-row grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end p-4 border border-gray-200 dark:border-gray-700 rounded-md mb-4">
                                            <div class="lg:col-span-2">
                                                <label class="form-label">Produk <span class="text-red-500">*</span></label>
                                                <select name="product_id[]" class="ti-form-select product-select" required>
                                                    <option value="">Pilih Produk</option>
                                                    <?php foreach ($products_list as $product): ?>
                                                        <option value="<?php echo $product['product_id']; ?>">
                                                            <?php echo htmlspecialchars($product['product_name']) . ' (' . htmlspecialchars($product['kode_item']) . ')'; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Kuantitas <span class="text-red-500">*</span></label>
                                                <input type="tel" name="quantity[]" class="ti-form-input quantity-input" min="1" value="1" required>
                                            </div>
                                            <div>
                                                <!-- Nama input tetap 'purchase_price[]' agar sesuai dengan apa yang sudah ada, tapi PHP akan mapping ke 'unit_price' -->
                                                <label class="form-label">Harga Beli /unit <span class="text-red-500">*</span></label>
                                                <input type="tel" step="0.01" name="purchase_price[]" class="ti-form-input price-input" min="0" required>
                                            </div>
                                            <div>
                                                <label class="form-label">No. Batch</label>
                                                <input type="text" name="batch_number[]" class="ti-form-input">
                                            </div>
                                            <div class="flex items-end space-x-2">
                                                <div class="flex-grow">
                                                    <label class="form-label">Expire</label>
                                                    <input type="text" name="expiry_date[]" class="ti-form-input flatpickr-date expiry-date-input">
                                                </div>
                                                <button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-6">
                                    <h3 class="text-xl font-semibold mb-4">Status & Catatan</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="payment_status" class="form-label">Status Pembayaran</label>
                                            <select id="payment_status" name="payment_status" class="ti-form-select">
                                                <option value="pending" selected>Pending</option>
                                                <option value="partially_paid">Partially Paid</option>
                                                <option value="paid">Paid</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="received_status" class="form-label">Status Penerimaan Barang</label>
                                            <select id="received_status" name="received_status" class="ti-form-select">
                                                <option value="pending" selected>Pending</option>
                                                <option value="partially_received">Partially Received</option>
                                                <option value="received">Received</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-6">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea class="ti-form-input" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                    </div>

                                    <div class="mt-8 flex justify-end">
                                        <button type="submit" class="ti-btn ti-btn-primary">Simpan Pembelian</button>
                                        <a href="list_purchases.php" class="ti-btn ti-btn-light ms-2">Batal</a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemsContainer = document.getElementById('itemsContainer');
            const addItemBtn = document.getElementById('addItemBtn');
            
            const itemRowTemplate = itemsContainer.querySelector('.item-row').cloneNode(true);
            itemRowTemplate.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'number' && input.classList.contains('quantity-input')) {
                    input.value = '1';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else {
                    input.value = '';
                }
            });

            function initializeFlatpickr(element) {
                flatpickr(element, {
                    dateFormat: "Y-m-d",
                    allowInput: true
                });
            }

            document.querySelectorAll('.flatpickr-date').forEach(el => initializeFlatpickr(el));

            addItemBtn.addEventListener('click', function () {
                const newItemRow = itemRowTemplate.cloneNode(true);
                itemsContainer.appendChild(newItemRow);
                newItemRow.querySelectorAll('.flatpickr-date').forEach(el => initializeFlatpickr(el));
                attachRemoveButtonListener(newItemRow.querySelector('.removeItemBtn'));
            });

            function attachRemoveButtonListener(button) {
                 if(button){
                    button.addEventListener('click', function () {
                        if (itemsContainer.querySelectorAll('.item-row').length > 1) {
                            this.closest('.item-row').remove();
                        } else {
                            Swal.fire({
                              icon: "warning",
                              title: "Minimal harus ada 1 pembelian",
                              text: "Periksa kembali pembelian anda"
                              
                            });
                        }
                    });
                }
            }
            
            itemsContainer.querySelectorAll('.removeItemBtn').forEach(btn => attachRemoveButtonListener(btn));

            const purchaseForm = document.getElementById('purchaseForm');
            if(purchaseForm){
                purchaseForm.addEventListener('submit', function(event){
                    if(itemsContainer.querySelectorAll('.item-row').length === 0){
                        alert('Harap tambahkan minimal satu item pembelian.');
                        event.preventDefault(); 
                    }
                    // Validasi tambahan per item jika diperlukan sebelum submit
                    let validForm = true;
                    itemsContainer.querySelectorAll('.item-row').forEach((row, index) => {
                        const productSelect = row.querySelector('.product-select');
                        const quantityInput = row.querySelector('.quantity-input');
                        const priceInput = row.querySelector('.price-input');
                        if (!productSelect.value || !quantityInput.value || quantityInput.value <= 0 || !priceInput.value || priceInput.value < 0) {
                            alert(`Data item pada baris ke-${index + 1} tidak lengkap atau tidak valid.`);
                            validForm = false;
                        }
                    });
                    if(!validForm){
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
