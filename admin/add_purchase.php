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
        $unit_prices = $_POST['purchase_price'] ?? [];
        $batch_numbers_form = $_POST['batch_number'] ?? [];
        $expiry_dates_form = $_POST['expiry_date'] ?? [];

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
                        $unit_price = $unit_prices[$key];
                        $batch_number_item = $batch_numbers_form[$key] ?? null;
                        $expiry_date_item = !empty($expiry_dates_form[$key]) ? $expiry_dates_form[$key] : null;
                        
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
                        $stmt_item->bindParam(':item_total', $item_total);
                        $stmt_item->bindParam(':received_quantity', $quantity, PDO::PARAM_INT);
                        $stmt_item->execute();
                        
                        $sql_update_stock = "UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE product_id = :product_id";
                        $stmt_update_stock = $pdo->prepare($sql_update_stock);
                        $stmt_update_stock->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_update_stock->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                        $stmt_update_stock->execute();

                        if (!empty($batch_number_item) && !empty($expiry_date_item)) {
                            $sql_prod_batch = "INSERT INTO product_batches (product_id, supplier_id, purchase_id, batch_number, expiry_date, purchase_price, quantity_received, remaining_quantity, created_at, updated_at)
                                              VALUES (:product_id, :supplier_id, :purchase_id, :batch_number, :expiry_date, :purchase_price, :quantity, :quantity, NOW(), NOW())
                                              ON DUPLICATE KEY UPDATE 
                                              expiry_date = VALUES(expiry_date), 
                                              purchase_price = VALUES(purchase_price), 
                                              quantity_received = quantity_received + VALUES(quantity_received),
                                              remaining_quantity = remaining_quantity + VALUES(remaining_quantity),
                                              updated_at = NOW()";
                            $stmt_prod_batch = $pdo->prepare($sql_prod_batch);
                            $stmt_prod_batch->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
                            $stmt_prod_batch->bindParam(':batch_number', $batch_number_item);
                            $stmt_prod_batch->bindParam(':expiry_date', $expiry_date_item);
                            $stmt_prod_batch->bindParam(':purchase_price', $unit_price);
                            $stmt_prod_batch->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                            // $stmt_prod_batch->execute(); // Uncomment jika ingin aktif
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
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                                            <input type="text" class="ti-form-input flatpickr-date" id="purchase_date" name="purchase_date" value="<?php
                                                // Tentukan dan cetak nilai tanggal pembelian
                                                // Ambil dari POST jika ada, jika tidak gunakan tanggal hari ini
                                                $purchase_date_val = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
                                                echo htmlspecialchars($purchase_date_val);
                                            ?>" required>
                                        </div>
                                        <div>
                                            <label for="due_date" class="form-label">Tanggal Jatuh Tempo</label>
                                            <input type="tel" class="ti-form-input" id="due_date" name="due_date" placeholder="YYYY-MM-DD" value="<?php
                                                // Tentukan tanggal dasar untuk perhitungan tanggal jatuh tempo
                                                // Logikanya sama dengan penentuan purchase_date_val di atas
                                                $base_purchase_date_for_due_date_calc = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
                                                
                                                $calculated_due_date_val = ''; // Default ke string kosong
                                        
                                                // Validasi format tanggal sebelum mencoba membuat objek DateTime
                                                // Ini untuk memastikan tanggal dasar adalah string 'Y-m-d' yang valid
                                                $date_obj_check = DateTime::createFromFormat('Y-m-d', $base_purchase_date_for_due_date_calc);
                                                
                                                if ($date_obj_check && $date_obj_check->format('Y-m-d') === $base_purchase_date_for_due_date_calc) {
                                                    // String tanggal valid dan dalam format Y-m-d
                                                    try {
                                                        // Buat objek DateTime dari tanggal pembelian yang valid
                                                        $date_obj = new DateTime($base_purchase_date_for_due_date_calc);
                                                        // Tambahkan 30 hari
                                                        $date_obj->add(new DateInterval('P30D'));
                                                        // Format tanggal jatuh tempo sebagai YYYY-MM-DD
                                                        $calculated_due_date_val = $date_obj->format('Y-m-d');
                                                    } catch (Exception $e) {
                                                        // Seharusnya tidak terjadi jika pemeriksaan createFromFormat berhasil,
                                                        // tetapi sebagai pengaman jika ada masalah tak terduga dengan DateTime.
                                                        // error_log('Error calculating due date: ' . $e->getMessage()); // Opsional: log error
                                                        $calculated_due_date_val = ''; // Kembali ke string kosong jika ada error
                                                    }
                                                } else {
                                                    // String tanggal pembelian dasar tidak dalam format 'Y-m-d' yang valid
                                                    // (misalnya, dari data POST yang dimanipulasi atau tidak valid).
                                                    // error_log('Invalid purchase_date format for due_date calculation: ' . htmlspecialchars($base_purchase_date_for_due_date_calc)); // Opsional: log error
                                                    $calculated_due_date_val = ''; // Kembali ke string kosong
                                                }
                                                
                                                // Cetak nilai tanggal jatuh tempo yang sudah dihitung dan diamankan
                                                echo htmlspecialchars($calculated_due_date_val);
                                            ?>">
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
                                                <select name="product_id[]" class="ti-form-select product-select select2-product" required>
                                                    <option value="">Pilih Produk</option>
                                                    <?php foreach ($products_list as $product): ?>
                                                        <option value="<?php echo $product['product_id']; ?>"
                                                                data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>"
                                                                data-stock="<?php echo htmlspecialchars($product['stock_quantity'] ?? '0'); ?>">
                                                            <?php 
                                                            // Teks utama untuk option, Select2 akan menggunakan templateResult untuk tampilan di dropdown
                                                            echo htmlspecialchars($product['product_name']); 
                                                            // Tambahkan kode item jika perlu, tapi templateResult lebih baik untuk info lengkap
                                                            // echo ' (' . htmlspecialchars($product['kode_item']) . ')'; 
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Kuantitas <span class="text-red-500">*</span></label>
                                                <input type="tel" name="quantity[]" class="ti-form-input quantity-input" placeholder="Jumlah" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Harga Beli /unit <span class="text-red-500">*</span></label>
                                                <input type="tel" step="0.01" name="purchase_price[]" class="ti-form-input price-input" min="0" value="//ambil data cost_price nya" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Harga Jual <span class="text-red-500">*</span></label>
                                                <input type="tel" step="0.01" name="sell_price[]" class="ti-form-input price-input" min="0" value="// ambildata pricenya" required>
                                            </div>
                                            <div>
                                                <label class="form-label">No. Batch</label>
                                                <input type="text" name="batch_number[]" class="ti-form-input">
                                            </div>
                                            <div class="flex items-end space-x-2">
                                                <div class="flex-grow">
                                                    <label class="form-label">Expire</label>
                                                    <input type="text" name="expiry_date[]" id="gas" class="ti-form-input" placeholder="YYYY-MM-DD">
                                                </div>
                                                <button type="button" class="ti-btn ti-btn-danger ti-btn-icon removeItemBtn"><i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-6">
                                    <h3 class="text-xl font-semibold mb-4">Status & Catatan</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="payment_status" class="form-label">Status Pembayaran</label>
                                            <select id="payment_status" name="payment_status" class="ti-form-select">
                                                <option value="hutang" selected>Hutang</option>
                                                <option value="cicil">Cicil</option>
                                                <option value="lunas">Lunas</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="received_status" class="form-label">Status Penerimaan Barang</label>
                                            <select id="received_status" name="received_status" class="ti-form-select">
                                                <option value="belum_diterima" selected>Belum Diterima</option>
                                                <option value="diterima_sebagian">Sebagian Diterima</option>
                                                <option value="diterima">Diterima</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-6">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea class="ti-form-input" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                    </div>

                                    <div class="mt-8 flex justify-end">
                                        <button type="submit" class="ti-btn ti-btn-primary">Simpan Pembelian</button>
                                        <a href="pembelian_supplier.php" class="ti-btn ti-btn-light ms-2">Batal</a>
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
    <script src="../assets/js/beli_sup.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');

    flatpickr(".flatpickr-date", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    const itemRowTemplate = itemsContainer.querySelector('.item-row').cloneNode(true);
    itemRowTemplate.querySelectorAll('input, select').forEach(input => {
        if (input.type === 'number' && input.classList.contains('quantity-input')) {
            input.value = '1';
        } else if (input.tagName === 'SELECT' && !input.classList.contains('select2-product')) { // Jangan reset select2 product
            input.selectedIndex = 0;
        } else if (!input.classList.contains('select2-product')) { // Jangan reset input yang akan jadi select2
             input.value = '';
        }
        // Untuk select2 product, biarkan option pertama yang terpilih (placeholder)
        if (input.classList.contains('select2-product')) {
            input.selectedIndex = 0;
        }
    });
    
    // Fungsi untuk format tampilan item di dropdown Select2
    function formatProductResult(product) {
        if (!product.id) { return product.text; } // Untuk placeholder
        var $element = $(product.element);
        var barcode = $element.data('barcode');
        var stock = $element.data('stock');
        var productName = product.text;

        var markup = "<div class='select2-result-repository clearfix'>" +
                     "<div class='select2-result-repository__title'>" + productName + "</div>";
        if (barcode) {
            markup += "<div class='select2-result-repository__barcode' style='font-size:0.85em; color:#555;'>BC: " + barcode + "</div>";
        }
        markup += "<div class='select2-result-repository__stock' style='font-size:0.85em; color:#777;'>Stok: " + stock + "</div>";
        markup += "</div>";
        return $(markup);
    }
    
    document.getElementById('gas').addEventListener('input', function (e) {
        let input = e.target.value.replace(/\D/g, ''); // Hapus semua karakter non-digit
        if (input.length > 4) input = input.slice(0, 4) + '-' + input.slice(4);
        if (input.length > 7) input = input.slice(0, 7) + '-' + input.slice(7, 9);
        e.target.value = input;
    });


    // Fungsi untuk format tampilan item yang TERPILIH di input Select2
    function formatProductSelection(product) {
        if (!product.id) { return product.text; }
        var $element = $(product.element);
        var productName = product.text;
        // Anda bisa menambahkan info lain jika perlu, misal kode item
        // var kodeItem = ... (ambil dari data-attribute jika ada)
        return productName; // Atau $(product.element).text() jika lebih sederhana
    }

    function initializeSelect2Product(element) {
        $(element).select2({
            width: '100%',
            dropdownParent: $(element).closest('.item-row'), // Penting untuk z-index
            placeholder: 'Cari Produk (Nama/Barcode)',
            allowClear: true,
            templateResult: formatProductResult,
            templateSelection: formatProductSelection,
            matcher: function(params, data) {
                // Jika tidak ada term pencarian, tampilkan semua
                if ($.trim(params.term) === '') {
                    return data;
                }
                // Jangan tampilkan jika tidak ada properti 'text' (untuk placeholder)
                if (typeof data.text === 'undefined' || data.id === "") { // Juga cek data.id untuk placeholder
                    return null;
                }

                var term = params.term.toLowerCase();
                var originalText = data.text.toLowerCase(); // Nama produk
                var barcode = ($(data.element).data('barcode') || '').toString().toLowerCase();

                if (originalText.indexOf(term) > -1 || (barcode && barcode.indexOf(term) > -1)) {
                    return data;
                }
                return null;
            }
        }).on('select2:select', function (e) {
            // Mungkin ada aksi lain yang ingin dilakukan saat produk dipilih
            // Misalnya, mengisi harga beli terakhir produk tersebut (jika ada datanya)
            var data = e.params.data;
            // console.log('Selected product:', data);
        });
    }

    // Inisialisasi select2 pada baris pertama yang sudah ada
    itemsContainer.querySelectorAll('.select2-product').forEach(function(el) {
        initializeSelect2Product(el);
    });

    function attachRemoveButtonListener(button) {
        if(button){
            button.addEventListener('click', function () {
                if (itemsContainer.querySelectorAll('.item-row').length > 1) {
                    // Hancurkan instance select2 sebelum menghapus baris
                    $(this.closest('.item-row')).find('.select2-product').each(function() {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                    });
                    this.closest('.item-row').remove();
                } else {
                    Swal.fire("Peringatan", "Minimal harus ada 1 item pembelian.", "warning");
                }
            });
        }
    }

    itemsContainer.querySelectorAll('.removeItemBtn').forEach(btn => attachRemoveButtonListener(btn));

    addItemBtn.addEventListener('click', function () {
        const newItemRow = itemRowTemplate.cloneNode(true);
        // Bersihkan value input di baris baru yang di-clone, kecuali quantity
        newItemRow.querySelectorAll('input, select').forEach(input => {
            if (input.type === 'number' && input.classList.contains('quantity-input')) {
                input.value = '1';
            } else if (input.tagName === 'SELECT' && !input.classList.contains('select2-product')) {
                 input.selectedIndex = 0;
            } else if (!input.classList.contains('select2-product')) {
                input.value = '';
            }
            if (input.classList.contains('select2-product')) {
                 input.selectedIndex = 0; // Reset ke placeholder
            }
        });

        itemsContainer.appendChild(newItemRow);
        
        const newSelectProduct = newItemRow.querySelector('.select2-product');
        if ($(newSelectProduct).hasClass('select2-hidden-accessible')) {
            $(newSelectProduct).select2('destroy');
        }
        initializeSelect2Product(newSelectProduct);

        newItemRow.querySelectorAll('.flatpickr-date').forEach(el => {
            flatpickr(el, { dateFormat: "Y-m-d", allowInput: true });
        });

        attachRemoveButtonListener(newItemRow.querySelector('.removeItemBtn'));
    });

    const purchaseForm = document.getElementById('purchaseForm');
    if(purchaseForm){
        purchaseForm.addEventListener('submit', function(event){
            let hasItems = itemsContainer.querySelectorAll('.item-row').length > 0;
            let firstProductSelect = itemsContainer.querySelector('.item-row .product-select');
            
            // Cek jika hanya ada satu baris dan produk belum dipilih
            if (itemsContainer.querySelectorAll('.item-row').length === 1 && firstProductSelect && !firstProductSelect.value) {
                hasItems = false; // Anggap tidak ada item jika baris pertama produknya kosong
            }

            if(!hasItems){
                Swal.fire('Input Tidak Lengkap', 'Harap tambahkan minimal satu item pembelian dengan produk yang valid.', 'warning');
                event.preventDefault(); 
                return;
            }

            let validForm = true;
            itemsContainer.querySelectorAll('.item-row').forEach((row, index) => {
                const productSelect = row.querySelector('.product-select');
                const quantityInput = row.querySelector('.quantity-input');
                const priceInput = row.querySelector('.price-input');
                if (!productSelect.value || !quantityInput.value || parseFloat(quantityInput.value) <= 0 || !priceInput.value || parseFloat(priceInput.value) < 0) {
                    // Cek apakah ini baris template yang tidak diisi dan bukan satu-satunya baris
                    if (itemsContainer.querySelectorAll('.item-row').length > 1 && !productSelect.value && !quantityInput.value && !priceInput.value) {
                        // Ini mungkin baris kosong yang tidak sengaja ada, bisa diabaikan jika bukan satu-satunya
                    } else {
                        Swal.fire('Data Tidak Valid', `Data item pada baris ke-${index + 1} tidak lengkap atau tidak valid (Produk, Kuantitas > 0, Harga Beli >= 0).`, 'error');
                        validForm = false;
                    }
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
