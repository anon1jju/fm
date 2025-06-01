<?php
require_once '../functions.php'; // Path to your functions.php

// $farma sudah diinstansiasi di akhir functions.php

// Cek sesi dan role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$pdo = $farma->getPDO();
//$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']); // Hapus pesan setelah ditampilkan

// Ambil data form sebelumnya jika ada error validasi dari server
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Inisialisasi array untuk dropdown
$categories = [];
$units = [];
$suppliers = [];
$recentMovements = []; // Untuk log item masuk (barang baru)
$existingProducts = []; // Untuk tab "Tambah Stok Barang Tersedia"

if ($pdo) {
    try {
        // Ambil Kategori Produk
        $stmt_cat = $pdo->query("SELECT category_id, category_name FROM product_categories ORDER BY category_name ASC");
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // Ambil Unit
        $stmt_units_table = $pdo->query("SELECT unit_name FROM units ORDER BY unit_name ASC");
        if ($stmt_units_table) {
            $unit_names_from_db = $stmt_units_table->fetchAll(PDO::FETCH_COLUMN);
            foreach ($unit_names_from_db as $u_name) {
                $units[] = ['unit_id' => $u_name, 'unit_name' => $u_name];
            }
        }
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

        // Ambil Produk yang Sudah Ada (untuk tab Tambah Stok)
        $stmt_ex_products = $pdo->query("SELECT product_id, product_name, kode_item, unit FROM products WHERE is_active = 1 ORDER BY product_name ASC");
        $existingProducts = $stmt_ex_products->fetchAll(PDO::FETCH_ASSOC);

        // Ambil Log Item Masuk Terbaru (khusus untuk 'barang_baru') - untuk Tab 1
        $movementTypeFilter = 'barang_baru'; 
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
            border: 2px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            z-index: 1050; /* Ensure dropdown is above other elements */
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
        .input-with-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.input-with-icon-wrapper .ti-form-input {
    padding-right: 2.8rem; /* Space for the icon */
}
.input-with-icon-wrapper .generate-kode-item-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 1.1rem;
    color: #6b7280;
    z-index: 2;
}
.input-with-icon-wrapper .generate-kode-item-icon:hover {
    color: #111827;
}
.input-with-icon-wrapper .generate-kode-item-icon.is-loading {
    cursor: default;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(-360deg); }
}
/* Pastikan .error-message dan .ti-form-input.is-invalid sudah ada dari CSS Anda sebelumnya */
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

                <?php if ($successMessage): ?>
                    <div class="my-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded-md" role="alert">
                        <i class="ri-check-double-line ltr:mr-2 rtl:ml-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage && strpos($errorMessage, "Kesalahan database") === false): ?>
                    <div class="my-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md" role="alert">
                        <i class="ri-error-warning-line ltr:mr-2 rtl:ml-2"></i><?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- Tab Content -->
                <div class="mt-3">
                    <!-- Tab 1: Tambah Barang Baru -->
                    <div id="content-tambah-baru" role="tabpanel" aria-labelledby="tab-tambah-baru">
                        <div class="grid grid-cols-12 gap-x-6">
                            <div class="xl:col-span-12 col-span-12">
                                <div class="box p-15">
                                    <div class="box-header p-10">
                                        <div class="box-title flex items-center">
                                            <i class="ri-add-box-line text-xl ltr:mr-2 rtl:ml-2"></i> Input Barang Baru
                                        </div>
                                    </div>
                                    <div class="box-body p-10">
                                        <?php if ($errorMessage && strpos($errorMessage, "Kesalahan database")): ?>
                                            <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md" role="alert">
                                                <i class="ri-error-warning-line ltr:mr-2 rtl:ml-2"></i><?php echo $errorMessage; ?>
                                            </div>
                                        <?php endif; ?>
                                        <form action="../prosesdata/process_tambah_barang.php" method="POST" id="tambahBarangForm">
                                            <div class="flex items-center justify-between mb-4">
                                                  <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    Input beberapa produk sekaligus, satu baris untuk satu produk.
                                                  </p>
                                                  <a href="beli.php" class="ti-btn ti-btn-info">
                                                    Pembelian Supplier
                                                  </a>
                                                </div>

                                        
                                            <table class="min-w-full border text-sm text-left" id="produkTable">
                                                <thead>
                                                    <tr class="bg-gray-100">
                                                        <th class="border px-1 py-1">Kode Item</th>
                                                        <th class="border px-2 py-1">Barcode</th>
                                                        <th class="border px-2 py-1">Nama Produk</th>
                                                        <th class="border px-2 py-1">Jenis</th>
                                                        <th class="border px-2 py-1">Satuan</th>
                                                        <th class="border px-2 py-1">Stok Min</th>
                                                        <th class="border px-2 py-1">Harga Modal</th>
                                                        <th class="border px-2 py-1">Harga Jual</th>
                                                        <th class="border px-2 py-1">Batch</th>
                                                        <th class="border px-2 py-1">Rak</th>
                                                        <th class="border px-2 py-1">Expire</th>
                                                        <!--<th class="border px-2 py-1">Catatan</th>-->
                                                        <th class="border px-2 py-1">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tableBody">
                                                    <tr>
                                                        <td>
                                                            <div class="input-with-icon-wrapper">
                                                                <input type="text" name="kode_item[]" class="ti-form-input kode-item-input" placeholder="Otomatis/Input manual" style="width: 90px; font-size: 12px;"/>
                                                                <i class="ri-loop-left-line generate-kode-item-icon" title="Generate Kode Item"></i>
                                                                <div class="error-message kode-item-error-message" style="display: none;"></div>
                                                            </div>
                                                        </td>
                                                        <td><input type="text" name="barcode[]" class="ti-form-input w-full" /><div id="barcode_error" class="error-message"></div></td>
                                                        
                                                        <td><input type="text" name="nama_produk[]" class="ti-form-input" style="width: 150px; font-size: 12px;" required /></td>
                                                        <td>
                                                            <select name="category_id[]" class="ti-form-select w-full">
                                                                <option value="">Pilih</option>
                                                                <?php foreach ($categories as $category): ?>
                                                                    <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="unit[]" class="ti-form-select w-full" required>
                                                                <option value="">Pilih</option>
                                                                <?php foreach ($units as $unit_item): ?>
                                                                    <option value="<?= htmlspecialchars($unit_item['unit_id']) ?>"><?= htmlspecialchars($unit_item['unit_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td><input type="number" step="any" min="0" name="minimum_stock[]" class="ti-form-input" style="width: 64px; font-size: 12px;" required /></td>
                                                        <td><input type="text" inputmode="numeric" step="any" min="0" name="cost_price[]" class="ti-form-input w-full" required /></td>
                                                        <td><input type="text" inputmode="numeric" step="any" min="0" name="price[]" class="ti-form-input w-full" required /></td>
                                                        <td><input type="text" name="batch_number[]" class="ti-form-input" style="width: 75px; font-size: 12px;" /></td>
                                                        <td><input type="text" name="posisi[]" class="ti-form-input" style="width: 50px; font-size: 12px;" /></td>
                                                        <td><input type="tel" name="expire_date[]" class="ti-form-input w-full" placeholder="DD-MM-YYYY" required /></td>
                                                        <!--<td><textarea name="reason[]" rows="1" class="ti-form-input w-full">Penambahan produk baru</textarea></td>-->
                                                        <td><button type="button" class="ti-btn ti-btn-danger" onclick="hapusBaris(this)"><i class="ri-delete-bin-line"></i></button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        
                                            <div class="mt-4">
                                                <button type="button" class="ti-btn ti-btn-secondary" onclick="tambahBaris()">+ Tambah Baris</button>
                                            </div>
                                        
                                            <div class="flex justify-end mt-6">
                                                <button type="submit" class="ti-btn ti-btn-primary">Tambah Semua Produk</button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Log Item Masuk Terbaru (untuk barang baru) -->
                        <div class="grid grid-cols-12 gap-x-6 mt-6">
                            <div class="xl:col-span-12 col-span-12">
                                <div class="box">
                                    <div class="box-header">
                                        <div class="box-title flex items-center justify-end p-4 gap-2">
                                            <i class="ri-history-line text-xl ltr:mr-2 rtl:ml-2"></i> Log Penambahan Barang Baru Terbaru
                                        </div>
                                        <div class="flex items-center justify-end p-4 gap-2">
                                            <label for="filter-date" class="text-sm text-gray-700 dark:text-gray-300">Filter Tanggal:</label>
                                            <input type="text" id="filter-date" class="border border-gray-300 dark:border-white/20 rounded px-3 py-2 text-sm dark:bg-black/20 dark:text-white" placeholder="Pilih tanggal" />
                                        </div>
                                    </div>
                                    <div class="box-body p-0">
                                        <div class="overflow-x-auto max-h-[400px]">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                                                <thead class="bg-gray-50 dark:bg-black/20">
                                                    <tr>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk (Kode)</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty Masuk</th>
                                                        
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alasan</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Oleh</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-bodybg dark:divide-white/10">
                                                    <?php if (!empty($recentMovements)): ?>
                                                        <?php foreach($recentMovements as $movement): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white/70"><?= htmlspecialchars(date('d M Y H:i', strtotime($movement['movement_date']))) ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                                <?= htmlspecialchars($movement['product_name']) ?>
                                                                <?php if (!empty($movement['kode_item'])): ?>
                                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($movement['kode_item']) ?>)</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white/70"><?= htmlspecialchars($movement['related_transaction_id']) /* Ini adalah batch number */ ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right"><?= htmlspecialchars(number_format((float)$movement['quantity_changed'], 2, ',', '.')) ?></td>
                                                            
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
                    </div>
                </div>
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
    <script src="../assets/js/auto_format_date_input_baru.js"></script>
    <script src="../assets/js/custom-switcher.min.js"></script>
    <script src="../assets/js/setting_input.js"> </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#filter-date", {
            dateFormat: "d M Y",
            onChange: function(selectedDates, dateStr) {
                filterTableByDate(dateStr);
            }
        });

        function filterTableByDate(selectedDate) {
            const rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const dateCell = row.querySelector("td:first-child");
                if (!dateCell) return;

                const rowDate = dateCell.textContent.trim().substring(0, 11); // contoh: "22 Mei 2025"
                if (!selectedDate || rowDate === selectedDate) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    });
</script>
</body>
</html>
