<?php
require_once '../functions.php'; // Path to your functions.php

// $farma sudah diinstansiasi di akhir functions.php

// Cek sesi dan role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$pdo = $farma->getPDO();
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']); // Hapus pesan setelah ditampilkan

// Ambil data form sebelumnya jika ada error validasi dari server
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Inisialisasi array untuk dropdown
$categories = [];
$units = [];
$suppliers = [];
$recentMovements = []; // Untuk log item masuk

if ($pdo) {
    try {
        // Ambil Kategori Produk
        $stmt_cat = $pdo->query("SELECT category_id, category_name FROM product_categories ORDER BY category_name ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // Ambil Unit
        // Jika Anda punya tabel 'units' dengan 'unit_id' (PK) dan 'unit_name'
        // dan ingin menyimpan unit_id (INT) di tabel products, gunakan query ini:
        // $stmt_unit = $pdo->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name ASC");
        // $units = $stmt_unit->fetchAll(PDO::FETCH_ASSOC);
        // Jika tabel products.unit menyimpan NAMA unit (VARCHAR), maka ID dan Nama bisa sama untuk dropdown
        // Untuk contoh ini, kita asumsikan products.unit menyimpan NAMA unit (VARCHAR)
        // dan kita akan menggunakan nama unit sebagai value di dropdown.
        // Jika Anda punya tabel units (unit_id INT, unit_name VARCHAR), dan products.unit_id (INT FK)
        // maka value di option harus $unit_item['unit_id']
        $stmt_units_table = $pdo->query("SELECT unit_name FROM units ORDER BY unit_name ASC"); // Asumsi tabel units ada
        if ($stmt_units_table) {
            $unit_names_from_db = $stmt_units_table->fetchAll(PDO::FETCH_COLUMN);
            foreach ($unit_names_from_db as $u_name) {
                $units[] = ['unit_id' => $u_name, 'unit_name' => $u_name]; // unit_id di sini adalah nama unit itu sendiri untuk value
            }
        }
        // Fallback jika tabel units kosong atau tidak ada
        if (empty($units)) {
             $units = [
                ['unit_id' => 'PCS', 'unit_name' => 'PCS'],
                ['unit_id' => 'BOX', 'unit_name' => 'BOX'],
                ['unit_id' => 'Strip', 'unit_name' => 'Strip'],
                ['unit_id' => 'Tablet', 'unit_name' => 'Tablet'],
                ['unit_id' => 'Botol', 'unit_name' => 'Botol'],
                ['unit_id' => 'Tube', 'unit_name' => 'Tube'],
            ];
        }

        // Ambil Supplier
        $stmt_sup = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name ASC");
        $suppliers = $stmt_sup->fetchAll(PDO::FETCH_ASSOC);

        // Ambil Log Item Masuk Terbaru (khusus untuk 'barang_baru')
        $movementTypeFilter = 'barang_baru'; // Sesuai ENUM
        $stmt_logs = $pdo->prepare("
            SELECT 
                sm.movement_id,
                sm.movement_date,
                p.product_name,
                p.kode_item,
                sm.quantity_changed,
                sm.current_stock_after_movement,
                sm.reason,
                u.name as user_fullname, 
                sm.related_transaction_id 
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.product_id
            LEFT JOIN users u ON sm.user_id = u.user_id 
            WHERE sm.movement_type = :movement_type
            ORDER BY sm.movement_date DESC
            LIMIT 15
        ");
        $stmt_logs->bindParam(':movement_type', $movementTypeFilter);
        $stmt_logs->execute();
        $recentMovements = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMessage = "Kesalahan database: " . $e->getMessage();
        error_log("Kesalahan database di item_masuk.php: " . $e->getMessage());
    }
} else {
    $errorMessage = "Koneksi ke database gagal.";
}

?>
<!DOCTYPE html>
<html lang="id" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php"; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.938rem + 0.125rem) !important;
            border: 1px solid #e2e8f0 !important; 
            border-radius: 0.375rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + 0.938rem + 0.125rem - 2px) !important;
            padding-left: 0.75rem !important;
            padding-right: 2rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.938rem + 0.125rem - 2px) !important;
            right: 0.5rem !important;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            z-index: 1050;
        }
        .ti-form-input.is-invalid, 
        .ti-form-select.is-invalid + .select2-container--default .select2-selection--single { /* Target select2 after invalid select */
            border-color: #ef4444 !important; /* red-500 */
        }
        .error-message {
            color: #ef4444; /* red-500 */
            font-size: 0.775rem; /* text-sm */
            margin-top: 0.25rem; /* mt-1 */
        }
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
                <!-- Start::row-1 -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title flex items-center">
                                    <i class="ri-add-box-line text-xl ltr:mr-2 rtl:ml-2"></i> Tambah Barang Baru (Stok Masuk Awal)
                                </div>
                            </div>
                            <div class="box-body p-6">
                                <?php if ($successMessage): ?>
                                    <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded-md" role="alert">
                                        <i class="ri-check-double-line ltr:mr-2 rtl:ml-2"></i><?php echo htmlspecialchars($successMessage); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($errorMessage): ?>
                                    <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md" role="alert">
                                        <i class="ri-error-warning-line ltr:mr-2 rtl:ml-2"></i><?php echo $errorMessage; /* Error message bisa berisi HTML (<br>) */ ?>
                                    </div>
                                <?php endif; ?>

                                <form action="../prosesdata/process_tambah_barang.php" method="POST" id="tambahBarangForm">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Formulir ini digunakan untuk mendaftarkan produk baru beserta batch awal dan mencatatnya sebagai item masuk.</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-4">
                                        <div>
                                            <label for="nama_produk" class="ti-form-label">Nama Produk <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input" id="nama_produk" name="nama_produk" required placeholder="Nama lengkap produk" value="<?= htmlspecialchars($form_data['nama_produk'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="kode_item" class="ti-form-label">Kode Item</label>
                                            <div class="input-group">
                                                <input type="text" class="ti-form-input" id="kode_item" name="kode_item" placeholder="Auto / Manual" value="<?= htmlspecialchars($form_data['kode_item'] ?? '') ?>">
                                                <button type="button" id="btnGenerateKodeItem" class="ti-btn ti-btn-info btn-generate !mb-0 px-2 py-1 text-sm">Generate</button>
                                            </div>
                                            <div id="kode_item_feedback" class="error-message"></div>
                                        </div>
                                        <div>
                                            <label for="barcode" class="ti-form-label">Barcode</label>
                                            <input type="text" class="ti-form-input" id="barcode" name="barcode" placeholder="Scan atau ketik barcode" value="<?= htmlspecialchars($form_data['barcode'] ?? '') ?>">
                                            <div id="barcode_error" class="error-message"></div>
                                        </div>
                                        <div>
                                            <label for="category_id" class="ti-form-label">Kategori <span class="text-red-500">*</span></label>
                                            <select class="ti-form-select select2-basic" id="category_id" name="category_id" required data-placeholder="Pilih Kategori">
                                                <option value=""></option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category['category_id']) ?>" <?= (isset($form_data['category_id']) && $form_data['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['category_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="supplier_id" class="ti-form-label">Supplier</label>
                                            <select class="ti-form-select select2-basic" id="supplier_id" name="supplier_id" data-placeholder="Pilih Supplier (Jika ada)">
                                                <option value=""></option>
                                                <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= htmlspecialchars($supplier['supplier_id']) ?>" <?= (isset($form_data['supplier_id']) && $form_data['supplier_id'] == $supplier['supplier_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($supplier['supplier_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="batch_number" class="ti-form-label">No. Batch</label>
                                            <input type="text" class="ti-form-input" id="batch_number" name="batch_number" placeholder="Nomor batch produk" value="<?= htmlspecialchars($form_data['batch_number'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="expire_date" class="ti-form-label">Expire <span class="text-red-500">*</span></label>
                                            <input type="text" class="ti-form-input flatpickr-date" id="expire_date" name="expire_date" placeholder="YYYY-MM-DD" required value="<?= htmlspecialchars($form_data['expire_date'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="cost_price" class="ti-form-label">Harga Modal <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" class="ti-form-input" id="cost_price" name="cost_price" required min="0" placeholder="0.00" value="<?= htmlspecialchars($form_data['cost_price'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="price" class="ti-form-label">Harga Jual <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" class="ti-form-input" id="price" name="price" required min="0" placeholder="0.00" value="<?= htmlspecialchars($form_data['price'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="stock_quantity" class="ti-form-label">Stok Masuk <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" class="ti-form-input" id="stock_quantity" name="stock_quantity" required min="0.01" placeholder="Jumlah stok, misal: 10.50" value="<?= htmlspecialchars($form_data['stock_quantity'] ?? '') ?>">
                                        </div>
                                            <div>
                                            <label for="minimum_stock" class="ti-form-label">Stok Minimum <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" class="ti-form-input" id="minimum_stock" name="minimum_stock" required min="0.00" placeholder="Batas stok minimum, misal: 5.00" value="<?= htmlspecialchars($form_data['minimum_stock'] ?? '') ?>">
                                        </div>
                                        <div>
                                            <label for="unit" class="ti-form-label">Unit <span class="text-red-500">*</span></label>
                                            <select class="ti-form-select select2-basic" id="unit" name="unit" required data-placeholder="Pilih Unit Dasar">
                                                <option value=""></option>
                                                <?php foreach ($units as $unit_item): ?>
                                                <option value="<?= htmlspecialchars($unit_item['unit_id']) ?>" <?= (isset($form_data['unit']) && $form_data['unit'] == $unit_item['unit_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($unit_item['unit_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="lg:col-span-2">
                                            <label for="posisi" class="ti-form-label">Posisi</label>
                                            <input type="text" class="ti-form-input" id="posisi" name="posisi" placeholder="Contoh: Rak A1, Etalase Depan" value="<?= htmlspecialchars($form_data['posisi'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="lg:col-span-2">
                                            <label for="reason" class="ti-form-label">Catatan<span class="text-red-500">*</span></label>
                                            <textarea class="ti-form-input" id="reason" name="reason" rows="3" placeholder="Masukkan alasan atau catatan untuk item masuk..." required><?= htmlspecialchars($form_data['reason'] ?? 'Penambahan produk baru') ?></textarea>
                                        </div>

                                    </div>
                                    <div class="flex justify-end mt-6">
                                        <button type="submit" id="submitBtn" class="ti-btn ti-btn-primary اداکار-wave !font-medium"><i class="ri-add-circle-line ltr:mr-1 rtl:ml-1"></i>Tambah Produk & Catat Stok</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End::row-1 -->

                <!-- Start::Log Item Masuk -->
                <div class="grid grid-cols-12 gap-x-6 mt-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title flex items-center">
                                    <i class="ri-history-line text-xl ltr:mr-2 rtl:ml-2"></i> Log Penambahan Barang Baru Terbaru
                                </div>
                            </div>
                            <div class="box-body p-0">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                                        <thead class="bg-gray-50 dark:bg-black/20">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk (Kode)</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty Masuk</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Akhir</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alasan</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-bodybg dark:divide-white/10">
                                            <?php if (!empty($recentMovements)): ?>
                                                <?php foreach($recentMovements as $movement): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white/70"><?= htmlspecialchars(date('d/m/y H:i', strtotime($movement['movement_date']))) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        <?= htmlspecialchars($movement['product_name']) ?>
                                                        <?php if (!empty($movement['kode_item'])): ?>
                                                            <span class="block text-xs text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($movement['kode_item']) ?>)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white/70"><?= htmlspecialchars($movement['related_transaction_id']) /* Ini adalah batch number */ ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right"><?= htmlspecialchars(number_format((float)$movement['quantity_changed'], 2, ',', '.')) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right"><?= htmlspecialchars(number_format((float)$movement['current_stock_after_movement'], 2, ',', '.')) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white/70"><?= nl2br(htmlspecialchars($movement['reason'])) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white/70"><?= htmlspecialchars($movement['user_fullname'] ?? 'N/A') ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white/70 text-center">Belum ada log penambahan barang baru.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End::Log Item Masuk -->

            </div>
        </div> <!-- End Main Content -->
        <?php include "includes/footer.php";?>
    </div> <!-- End Page -->

    <div class="scrollToTop">
        <span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span>
    </div>
    <div id="responsive-overlay"></div>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script> 

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

    <script>
        $(document).ready(function() {
            $('.select2-basic').each(function () {
                var $this = $(this);
                $this.select2({
                    placeholder: $this.data('placeholder') || 'Pilih salah satu',
                    allowClear: true,
                    width: '100%'
                });
            });

            flatpickr(".flatpickr-date", {
                altInput: true, 
                altFormat: "d M Y", 
                dateFormat: "Y-m-d", 
                allowInput: true, 
                locale: "id" 
            });

            $('#barcode').on('blur', function() {
                var barcodeVal = $(this).val().trim();
                var barcodeErrorDiv = $('#barcode_error');
                var barcodeInput = $(this);
                var submitBtn = $('#submitBtn');

                barcodeInput.removeClass('is-invalid');
                barcodeErrorDiv.text('');
                // submitBtn.prop('disabled', false); 

                if (barcodeVal !== '') {
                    $.ajax({
                        url: 'ajax_check_barcode.php', // Pastikan path ini benar
                        type: 'POST',
                        data: { barcode: barcodeVal },
                        dataType: 'json',
                        success: function(response) {
                            if (response.exists) {
                                barcodeInput.addClass('is-invalid');
                                barcodeErrorDiv.text('Barcode ini sudah terdaftar.');
                                // submitBtn.prop('disabled', true); 
                            }
                        },
                        error: function() {
                            // Jangan tampilkan error teknis ke user, cukup log atau notif sederhana
                            // barcodeErrorDiv.text('Error validasi barcode.'); 
                            console.error("AJAX Barcode Check Error");
                        }
                    });
                }
            });

            $('#barcode').on('input', function() {
                if ($(this).hasClass('is-invalid')) {
                    $(this).removeClass('is-invalid');
                    $('#barcode_error').text('');
                    // $('#submitBtn').prop('disabled', false);
                }
            });
            
            // AJAX Generate Kode Item Unik
            $('#btnGenerateKodeItem').on('click', function() {
                var btn = $(this);
                var kodeItemInput = $('#kode_item');
                var kodeItemFeedback = $('#kode_item_feedback');

                btn.prop('disabled', true).text('Generating...');
                kodeItemFeedback.text('').removeClass('text-green-600 text-red-600'); // Reset feedback

                $.ajax({
                    url: 'ajax_generate_unique_kode_item.php', // Pastikan path ini benar
                    type: 'GET', // Atau POST jika prefer
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.kode_item) {
                            kodeItemInput.val(response.kode_item);
                           // kodeItemFeedback.text('Kode item unik berhasil di-generate: ' + response.kode_item).addClass('text-green-600');
                        } else {
                            kodeItemFeedback.text(response.message || 'Gagal men-generate kode item.').addClass('text-red-600');
                            kodeItemInput.val(''); // Kosongkan jika gagal
                        }
                    },
                    error: function() {
                        kodeItemFeedback.text('Terjadi kesalahan saat menghubungi server.').addClass('text-red-600');
                        kodeItemInput.val('');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Generate');
                    }
                });
            });
        });
    </script>
</body>
</html>
