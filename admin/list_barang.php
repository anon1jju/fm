<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php");
    exit();
}

// Inisialisasi variables
$dataProduk = $suppliers = $categories = $units = [];
$errorMessage = null;
$jumlahProduk = $produkTersedia = $totalStokUnit = 0;

try {
    // Ambil koneksi PDO
    $pdo = $farma->getPDO();
    if (!$pdo) {
        throw new Exception("Koneksi database tidak tersedia.");
    }

    // Ambil data produk
    $dataProduk = $farma->getAllProducts();
    
    // Hitung jumlah produk
    $jumlahProduk = count($dataProduk);
    
    // Hitung jumlah produk dengan stok tersedia (> 0)
    $produkTersedia = $totalStokUnit = 0;
    foreach ($dataProduk as $produk) {
        if ($produk['stock_quantity'] > 0) {
            $produkTersedia++;
            $totalStokUnit += $produk['stock_quantity'];
        }
    }
    
    // Gunakan metode dari class Farmamedika untuk mendapatkan data
    $suppliers = $farma->getSuppliers();
    $categories = $farma->getAllCategories();
    
    // Ambil data unit - masih menggunakan query langsung karena belum ada metode di class
    $query = "SELECT unit_name FROM units ORDER BY unit_name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Tangani error dengan lebih informatif
    error_log("Error: " . $e->getMessage());
    $errorMessage = "Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.";
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
    <head>
        <?php include "includes/meta.php";?>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body>
        <?php include "includes/switch.php";?>
        <!-- ========== END Switcher  ========== -->
        <!-- Loader -->
        <div id="loader">
            <img src="../assets/images/media/loader.svg" alt="">
        </div>
        <!-- Loader -->
        <div class="page">
        <?php include "includes/header.php";?>
        <!-- /app-header -->
        <!-- Start::app-sidebar -->
        <?php include "includes/sidebar.php";?>
        <!-- End::app-sidebar -->
        <!-- Start::app-content -->
        <div class="main-content app-content">
        <div class="container-fluid">
        <!-- Start::row-2 -->
        <div class="grid grid-cols-12 gap-x-6">
            <div class="xl:col-span-12 col-span-12">
                <div class="box">
                    <div class="box-header justify-between">
                        <div class="box-title">
                            <i class="ri-box-3-fill text-2xl"></i> Daftar Produk 
                            <span class="badge bg-success ms-2">Jumlah : <?php echo $produkTersedia; ?> produk</span>
                        </div>
                        <div class="flex gap-2">
                            <input class="ti-form-control" type="text" placeholder="Text atau Scan" id="search-barang" onkeyup="searchBarang()">
                            <button type="button" class="ti-btn ti-btn-sm !m-0 ti-btn-primary text-nowrap" data-hs-overlay="#modal-tambah-barang">
                            <i class="ri-add-line me-1 align-middle text-sm font-semibold"></i>Tambah Produk </button>
                            <div class="hs-overlay hidden ti-modal" id="modal-tambah-barang">
                                <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:mx-auto flex items-center justify-center min-h-[calc(100%-3.5rem)]">
                                    <div class="ti-modal-content shadow-lg">
                                        <div class="ti-modal-header">
                                            <h6 class="ti-modal-title" id="staticBackdropLabel">Tambah Produk </h6>
                                            <button type="button" class="hs-dropdown-toggle !text-[1rem] !font-semibold !text-defaulttextcolor" data-hs-overlay="#modal-tambah-barang">
                                            <span class="sr-only">Close</span>
                                            <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                        <div class="ti-modal-body">
                                            <form action="../prosesdata/process_tambah_barang.php" method="POST">
                                                <div class="grid grid-cols-12 sm:gap-x-6 gap-y-3">
                                                    <!-- Nama Produk -->
                                                    <div class="xl:col-span-6 col-span-12">
                                                        <label class="ti-form-label">Nama Produk</label>
                                                        <input type="text" class="ti-form-control" name="nama_produk" required>
                                                    </div>
                                                    <!-- Barcode -->
                                                    <div class="xl:col-span-6 col-span-12">
                                                        <label class="ti-form-label">Barcode</label>
                                                        <input type="tel" class="ti-form-control" name="barcode">
                                                    </div>
                                                    <!-- Kategori -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Kategori</label>
                                                        <select class="ti-form-control" name="category_id">
                                                            <option value="" disabled selected>Pilih Kategori</option>
                                                            <?php foreach ($categories as $category): ?>
                                                            <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <!-- Harga Modal -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Harga Modal</label>
                                                        <input type="tel" class="ti-form-control" name="harga_modal" required>
                                                    </div>
                                                    <!-- Harga Jual -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Harga Jual</label>
                                                        <input type="tel" class="ti-form-control" name="harga_jual" required>
                                                    </div>
                                                    <!-- Expire -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Expire</label>
                                                        <input type="tel" class="ti-form-control" name="expire" required>
                                                    </div>
                                                    <!-- Batch Number -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Batch Number</label>
                                                        <input type="text" class="ti-form-control" name="batch_number">
                                                    </div>
                                                    <!-- Kode Item -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Kode Item</label>
                                                        <input type="tel" class="ti-form-control" name="kode_item">
                                                    </div>
                                                    <!-- Posisi -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Posisi</label>
                                                        <input type="text" class="ti-form-control" name="posisi">
                                                    </div>
                                                    <!-- Unit -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Unit</label>
                                                        <select class="ti-form-control" name="unit">
                                                            <option value="" disabled selected>Pilih Unit</option>
                                                            <?php foreach ($units as $unit): ?>
                                                            <option value="<?= htmlspecialchars($unit['unit_name'], ENT_QUOTES, 'UTF-8') ?>" <?= $produk['unit'] == $unit['unit_name'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($unit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <!-- Stok -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Stok</label>
                                                        <input type="tel" class="ti-form-control" name="stok_barang" required>
                                                    </div>
                                                    <!-- Stok Minimum -->
                                                    <div class="xl:col-span-3 col-span-12">
                                                        <label class="ti-form-label">Stok Min</label>
                                                        <input type="tel" class="ti-form-control" name="stok_minimum" required>
                                                    </div>
                                                    <!-- Supplier -->
                                                    <div class="xl:col-span-6 col-span-12">
                                                        <label class="ti-form-label">Supplier</label>
                                                        <select class="ti-form-control" name="supplier_id">
                                                            <option value="" disabled selected>Pilih Supplier</option>
                                                            <?php 
                                                                foreach ($suppliers as $supplier): ?>
                                                            <option value="<?= htmlspecialchars($supplier['supplier_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8') ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="ti-modal-footer">
                                                    <button type="button" class="hs-dropdown-toggle ti-btn btn-wave ti-btn-light align-middle" data-hs-overlay="#modal-tambah-barang">Tutup</button>
                                                    <button type="submit" class="ti-btn btn-wave bg-success text-white !font-medium">Tambah</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="max-h-[70vh] overflow-y-auto table-responsive">
                            <table class="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 table border border-defaultborder dark:border-defaultborder/10 text-nowrap">
                                <thead class="bg-sky-400 sticky top-0">
                                    <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                        <th scope="col">Produk</th>
                                        <th scope="col">Barcode</th>
                                        <th scope="col">Kategori</th>
                                        <th scope="col">Modal</th>
                                        <th scope="col">Jual</th>
                                        <th scope="col">Exp</th>
                                        <th scope="col">Unit</th>
                                        <th scope="col">Stok</th>
                                        <th scope="col">Supplier</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($dataProduk)): ?> 
                                    <?php foreach ($dataProduk as $produk): ?> 
                                    <?php 
                                        $modal = "Rp ".number_format($produk['cost_price'], 2, ',', '.');
                                        $jual = "Rp ".number_format($produk['price'], 2, ',', '.');
                                        $expire_produk = json_decode($farma->daysUntilExpire($produk['expiry_date']), true);
                                        // Ambil jumlah hari yang tersisa
                                        $days_left = $expire_produk['days_left'];
                                        
                                        // Tentukan kelas badge berdasarkan jumlah hari yang tersisa
                                        if ($days_left <= 60) { // 60 hari kira-kira 2 bulan
                                            $badge_class = 'bg-danger/10 text-danger'; // Merah jika sisa 2 bulan atau kurang
                                        } elseif ($days_left <= 90) { // 90 hari kira-kira 3 bulan
                                            $badge_class = 'bg-warning/10 text-warning'; 
                                        } else {
                                            $badge_class = 'bg-success/10 text-success'; // Hijau jika lebih dari 3 bulan
                                        }
                                        
                                        $stoksisa = '';
                                        
                                        if ($produk['stock_quantity'] <= 0) {
                                            // Stok habis
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-danger">Habis</span>';
                                        } elseif ($produk['stock_quantity'] <= $produk['minimum_stock'] * 2) {
                                            // Stok mendekati minimum
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-warning">' . htmlspecialchars($produk['stock_quantity']) . '</span>';
                                        } else {
                                            // Stok aman
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-success">' . htmlspecialchars($produk['stock_quantity']) . '</span>';
                                        }
                                        // Supplier
                                            $supplier_name = !empty($produk['supplier_name']) ? htmlspecialchars($produk['supplier_name']) : 'Tidak Diketahui';?> 
                                    <tr class="product-list border-b border-defaultborder dark:border-defaultborder/10">
                                        <td>
                                            <div class="flex">
                                                <div class="ms-2">
                                                    <p class="font-semibold mb-0 flex items-center"><a href="javascript:void(0);"><?php echo htmlspecialchars($produk['product_name']); ?></a></p>
                                                    <p class="text-sm text-textmuted dark:text-textmuted/50 mb-0"><?php echo htmlspecialchars($produk['posisi']). ' - '.htmlspecialchars($produk['kode_item']). ' - '.htmlspecialchars($produk['batch_number']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($produk['barcode']); ?></td>
                                        <td><?php echo htmlspecialchars($produk['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($modal); ?></td>
                                        <td><?php echo htmlspecialchars($jual); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>"> <?php echo htmlspecialchars($days_left . ' hari'); ?> </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($produk['unit']); ?></td>
                                        <td><?php echo $stoksisa; ?></td>
                                        <td><?php echo $supplier_name; ?></td>
                                        <td>
                                            <div class="hstack gap-2 text-[15px]">
                                                <!-- Tombol Edit -->
                                                <a href="javascript:void(0);" class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-primary" data-hs-overlay="#modal-edit-barang-<?php echo $produk['product_id']; ?>">
                                                <i class="ri-edit-line"></i>
                                                </a>
                                                <!-- Tombol Hapus -->
                                                <a href="javascript:void(0);" class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-danger product-btn" onclick="confirmDelete(<?php echo $produk['product_id']; ?>, '<?php echo htmlspecialchars($produk['product_name'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="ri-delete-bin-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <!-- Modal Edit -->
                                        <div class="hs-overlay hidden ti-modal" id="modal-edit-barang-<?php echo $produk['product_id']; ?>">
                                            <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:mx-auto flex items-center min-h-[calc(100%-3.5rem)] justify-center">
                                                <div class="ti-modal-content shadow-lg">
                                                    <div class="ti-modal-header">
                                                        <h6 class="ti-modal-title" id="staticBackdropLabel">Edit Produk</h6>
                                                        <button type="button" class="hs-dropdown-toggle !text-[1rem] !font-semibold !text-defaulttextcolor" data-hs-overlay="#modal-edit-barang-<?php echo $produk['product_id']; ?>">
                                                        <span class="sr-only">Close</span>
                                                        <i class="ri-close-line"></i>
                                                        </button>
                                                    </div>
                                                    <div class="ti-modal-body">
                                                        <form action="../prosesdata/process_edit_barang.php" method="POST">
                                                            <!-- ID Produk (Hidden) -->
                                                            <input type="hidden" name="product_id" value="<?php echo $produk['product_id']; ?>" />
                                                            <div class="grid grid-cols-12 sm:gap-x-6 gap-y-3">
                                                                <!-- Nama Produk -->
                                                                <div class="xl:col-span-6 col-span-12">
                                                                    <label class="ti-form-label">Nama Produk</label>
                                                                    <input type="text" class="ti-form-control" name="nama_produk" value="<?php echo htmlspecialchars($produk['product_name']); ?>" required />
                                                                </div>
                                                                <!-- Barcode -->
                                                                <div class="xl:col-span-6 col-span-12">
                                                                    <label class="ti-form-label">Barcode</label>
                                                                    <input type="tel" class="ti-form-control" name="barcode" value="<?php echo htmlspecialchars($produk['barcode']); ?>" />
                                                                </div>
                                                                <!-- Kategori -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Kategori</label>
                                                                    <select class="ti-form-control" name="category_id">
                                                                        <option value="" disabled selected>Pilih Kategori</option>
                                                                        <?php foreach ($categories as $category): ?>
                                                                        <option value="<?= $category['category_id'] ?>" <?= $produk['category_id'] == $category['category_id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <!-- Harga Modal -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Harga Modal</label>
                                                                    <input type="tel" class="ti-form-control" name="harga_modal" value="<?php echo htmlspecialchars($produk['cost_price']); ?>" required />
                                                                </div>
                                                                <!-- Harga Jual -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Harga Jual</label>
                                                                    <input type="tel" class="ti-form-control" name="harga_jual" value="<?php echo htmlspecialchars($produk['price']); ?>" required />
                                                                </div>
                                                                <!-- Expire -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Expire</label>
                                                                    <input type="tel" class="ti-form-control" name="expire" value="<?php echo htmlspecialchars($produk['expiry_date']); ?>" required />
                                                                </div>
                                                                <!-- Batch Number -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Batch Number</label>
                                                                    <input type="text" class="ti-form-control" name="batch_number" value="<?php echo htmlspecialchars($produk['batch_number']); ?>"  />
                                                                </div>
                                                                <!-- Kode Item -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Kode Item</label>
                                                                    <input type="text" class="ti-form-control" name="kode_item" value="<?php echo htmlspecialchars($produk['kode_item']); ?>" />
                                                                </div>
                                                                <!-- Posisi -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Posisi</label>
                                                                    <input type="text" class="ti-form-control" name="posisi" value="<?php echo htmlspecialchars($produk['posisi']); ?>" />
                                                                </div>
                                                                <!-- Unit -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Unit</label>
                                                                    <select class="ti-form-control" name="unit">
                                                                        <option value="" disabled selected>Pilih Unit</option>
                                                                        <?php foreach ($units as $unit): ?>
                                                                        <option value="<?= htmlspecialchars($unit['unit_name'], ENT_QUOTES, 'UTF-8') ?>" <?= $produk['unit'] == $unit['unit_name'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($unit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <!-- Stok -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Stok</label>
                                                                    <input type="tel" class="ti-form-control" name="stok_barang" value="<?php echo htmlspecialchars($produk['stock_quantity']); ?>" required />
                                                                </div>
                                                                
                                                                <!-- Stok Minimum -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Stok Min</label>
                                                                    <input type="tel" class="ti-form-control" name="stok_minimum" value="<?php echo htmlspecialchars($produk['minimum_stock']); ?>" required />
                                                                </div>
                                                                <!-- Supplier -->
                                                                <div class="xl:col-span-6 col-span-12">
                                                                    <label class="ti-form-label">Supplier</label>
                                                                    <select class="ti-form-control" name="supplier_id">
                                                                        <option value="" disabled><?php echo $supplier_name;?></option>
                                                                        <?php foreach ($suppliers as $supplier): ?>
                                                                        <option value="<?= htmlspecialchars($supplier['supplier_id'], ENT_QUOTES, 'UTF-8') ?>" 
                                                                            <?= $produk['supplier_id'] == $supplier['supplier_id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($produk['supplier_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                        </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="ti-modal-footer">
                                                                <button type="button" class="hs-dropdown-toggle ti-btn btn-wave ti-btn-light align-middle" data-hs-overlay="#modal-edit-barang-<?php echo $produk['product_id']; ?>">Tutup</button>
                                                                <button type="submit" class="ti-btn btn-wave bg-success text-white !font-medium">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </tr>
                                    <?php endforeach; ?> 
                                    <?php else: ?> 
                                    <tr>
                                        <td colspan="11" class="text-center">Data produk tidak ditemukan.</td>
                                    </tr>
                                    <?php endif; ?> 
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-x-6">
            <div class="xl:col-span-12 col-span-12">
                <div class="box">
                    <div class="box-header justify-between">
                        <div class="box-title">
                            <i class="ri-box-3-line text-2xl"></i> Item Keluar 
                            
                        </div>
                        <!--End::row-2 -->
                    </div>
                </div>
            </div>
        </div>
            <!-- End::app-content -->
            <?php include "includes/footer.php";?>
        </div>
        <!-- Scroll To Top -->
        <div class="scrollToTop">
            <span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span>
        </div>
        <div id="responsive-overlay"></div>
        <!-- Scroll To Top -->
        <!-- Switch JS -->
        <script src="../assets/js/switch.js"></script>
        <!-- Popper JS -->
        <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>
        <!-- Preline JS -->
        <script src="../assets/libs/preline/preline.js"></script>
        <!-- Defaultmenu JS -->
        <script src="../assets/js/defaultmenu.min.js"></script>
        <!-- Node Waves JS-->
        <script src="../assets/libs/node-waves/waves.min.js"></script>
        <!-- Sticky JS -->
        <script src="../assets/js/sticky.js"></script>
        <!-- Simplebar JS -->
        <script src="../assets/libs/simplebar/simplebar.min.js"></script>
        <script src="../assets/js/simplebar.js"></script>
        <!-- Auto Complete JS -->
        <script src="../assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js"></script>
        <!-- Color Picker JS -->
        <script src="../assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>
        <!-- Date & Time Picker JS -->
        <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
        <!-- Apex Charts JS -->
        <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
        <!-- Sales Dashboard -->
        <script src="../assets/js/sales-dashboard.js"></script>
        <!-- Custom JS -->
        <script src="../assets/js/custom.js"></script>
        <!-- Custom-Switcher JS -->
        <script src="../assets/js/custom-switcher.min.js"></script>
        <!--<script src="../assets/js/autoformatexpire.js" defer></script>-->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function confirmDelete(productId, productName) {
                Swal.fire({
                    title: 'Apakah Anda yakin menghapus produk "' + productName + '"?',
                    text: "Produk tersebut akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect ke URL penghapusan produk
                        window.location.href = "../prosesdata/process_delete_barang.php?id=" + productId;
                    }
                });
            }
        </script>
        <script>
            function searchBarang() {
                const input = document.getElementById('search-barang').value.toLowerCase();
                const rows = document.querySelectorAll('.product-list');
            
                rows.forEach(row => {
                    const productName = row.querySelector('td:first-child a').textContent.toLowerCase();
                    const barcode = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const category = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
                    if (productName.includes(input) || barcode.includes(input) || category.includes(input)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        </script>
    </body>
</html>

