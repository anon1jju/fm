<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

// Inisialisasi variables
$suppliers = $categories = $units = [];
$errorMessage = null;
$jumlahProduk = $produkTersedia = $totalStokUnit = 0;

// --- Logic untuk Pencarian ---
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// --- Logic untuk Pagination ---
$items_per_page = 10; // Jumlah item per halaman
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$dataProduk = []; // Untuk halaman saat ini
$total_pages = 0;

try {
    // Ambil koneksi PDO
    $pdo = $farma->getPDO();
    if (!$pdo) {
        throw new Exception("Koneksi database tidak tersedia.");
    }

    // Ambil SEMUA data produk dari database
    $allProductsFromDb = $farma->getAllProducts();
    if ($allProductsFromDb === false) { // Asumsikan false jika ada error pengambilan
        throw new Exception("Gagal mengambil data produk dari database.");
    }

    // Filter produk jika ada query pencarian
    $productsToConsider = $allProductsFromDb;
    if (!empty($search_query)) {
        $filteredProducts = [];
        foreach ($allProductsFromDb as $product) {
            // Cek apakah category_name ada sebelum diakses
            $category_name_check = isset($product['category_name']) ? $product['category_name'] : '';
            
            if (stripos($product['product_name'], $search_query) !== false ||
                stripos($product['barcode'], $search_query) !== false ||
                stripos($product['kode_item'], $search_query) !== false ||
                stripos($category_name_check, $search_query) !== false) {
                $filteredProducts[] = $product;
            }
        }
        $productsToConsider = $filteredProducts;
    }

    // Hitung statistik berdasarkan $productsToConsider (sudah difilter jika ada pencarian)
    $jumlahProdukTotalMatchingFilter = count($productsToConsider);

    foreach ($productsToConsider as $p) {
        if ($p['stock_quantity'] > 0) {
            $produkTersedia++;
            $totalStokUnit += $p['stock_quantity'];
        }
    }
    
    // Hitung total halaman untuk pagination berdasarkan produk yang sudah difilter
    $total_items = $jumlahProdukTotalMatchingFilter;
    $total_pages = ceil($total_items / $items_per_page);

    if ($total_pages > 0 && $current_page > $total_pages) {
        $current_page = $total_pages; // Cap current page di total halaman jika melebihi
    }
    if ($total_pages == 0 && $current_page != 1) { // Jika tidak ada produk, halaman harus 1
         $current_page = 1;
    }


    // Hitung offset dan ambil data untuk halaman saat ini
    $offset = ($current_page - 1) * $items_per_page;
    $dataProduk = array_slice($productsToConsider, $offset, $items_per_page);

    // Gunakan metode dari class Farmamedika untuk mendapatkan data dropdown (suppliers, categories)
    $suppliers = $farma->getSuppliers();
    $categories = $farma->getAllCategories();
    
    // Ambil data unit - masih menggunakan query langsung karena belum ada metode di class
    $queryUnits = "SELECT unit_name FROM units ORDER BY unit_name ASC";
    $stmtUnits = $pdo->prepare($queryUnits);
    $stmtUnits->execute();
    $units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Tangani error dengan lebih informatif
    error_log("Error: " . $e->getMessage());
    $errorMessage = "Terjadi kesalahan saat memuat data: " . $e->getMessage() . " Silakan coba lagi nanti.";
}

// Nomor urut untuk tabel, disesuaikan dengan halaman saat ini
$no = ($current_page - 1) * $items_per_page;

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
    <head>
        <?php include "includes/meta.php";?>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .flatpickr-calendar {
                z-index: 99999 !important; /* Angka yang sangat tinggi, pastikan lebih tinggi dari z-index modal */
            }
            .ti-pagination .page-item.disabled .page-link {
                color: #6c757d;
                pointer-events: none;
                background-color: #fff;
                border-color: #dee2e6;
            }
            .ti-pagination .page-item.active .page-link {
                z-index: 3;
                color: #fff;
                background-color: #007bff; /* Ganti dengan warna primer tema Anda jika perlu */
                border-color: #007bff; /* Ganti dengan warna primer tema Anda jika perlu */
            }
        </style>
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
         <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-12 gap-x-6">
            <div class="xl:col-span-12 col-span-12">
                <div class="box">
                    <div class="box-header justify-between">
                        <div class="box-title">
                            <i class="ri-box-3-fill text-2xl"></i> Daftar Produk 
                            <span class="badge bg-success ms-2">Produk Tersedia: <?php echo $produkTersedia; ?> item</span>
                            <?php if(!empty($search_query)): ?>
                                <span class="badge bg-info ms-2">Hasil Pencarian "<?= htmlspecialchars($search_query) ?>": <?= $jumlahProdukTotalMatchingFilter ?> item ditemukan</span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-2">Total Produk: <?php echo $jumlahProdukTotalMatchingFilter; ?> item</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <input class="ti-form-control sm:w-auto w-full" type="text" placeholder="Cari Nama/Barcode/Kategori..." id="search-barang-input" value="<?= htmlspecialchars($search_query) ?>">
                            <button type="button" class="ti-btn ti-btn-sm ti-btn-primary" onclick="performSearch()">Cari</button>
                            <?php if (!empty($search_query)): ?>
                                <button type="button" class="ti-btn ti-btn-sm ti-btn-light" onclick="clearSearch()">Reset</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="max-h-[70vh] overflow-y-auto table-responsive">
                            <table class="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 table border border-defaultborder dark:border-defaultborder/10 text-nowrap">
                                <thead class="bg-sky-400 sticky top-0">
                                    <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                        <th scope="col">No</th>
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
                                        $no++; // Increment nomor urut
                                        $modal = "Rp ".number_format($produk['cost_price'], 2, ',', '.');
                                        $jual = "Rp ".number_format($produk['price'], 2, ',', '.');
                                        
                                        $expire_produk_info = ['days_left' => 'N/A', 'badge_class' => 'bg-secondary/10 text-secondary']; // Default
                                        if (!empty($produk['expiry_date'])) {
                                            $expire_produk = json_decode($farma->daysUntilExpire($produk['expiry_date']), true);
                                            $days_left = isset($expire_produk['days_left']) ? $expire_produk['days_left'] : null;

                                            if ($days_left !== null) {
                                                 $expire_produk_info['days_left'] = $days_left . ' hari';
                                                if ($days_left <= 0) {
                                                    $expire_produk_info['badge_class'] = 'bg-danger/10 text-danger'; // Expired
                                                    $expire_produk_info['days_left'] = 'Expired';
                                                } elseif ($days_left <= 60) { // 60 hari kira-kira 2 bulan
                                                    $expire_produk_info['badge_class'] = 'bg-danger/10 text-danger'; 
                                                } elseif ($days_left <= 90) { // 90 hari kira-kira 3 bulan
                                                    $expire_produk_info['badge_class'] = 'bg-warning/10 text-warning'; 
                                                } else {
                                                    $expire_produk_info['badge_class'] = 'bg-success/10 text-success'; // Hijau jika lebih dari 3 bulan
                                                }
                                            }
                                        }
                                        
                                        $stoksisa = '';
                                        if ($produk['stock_quantity'] <= 0) {
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-danger">Habis</span>';
                                        } elseif ($produk['stock_quantity'] <= $produk['minimum_stock'] * 2) {
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-warning">' . htmlspecialchars($produk['stock_quantity']) . '</span>';
                                        } else {
                                            $stoksisa = '<span class="badge !rounded-full bg-outline-success">' . htmlspecialchars($produk['stock_quantity']) . '</span>';
                                        }
                                        $supplier_name = !empty($produk['supplier_name']) ? htmlspecialchars($produk['supplier_name']) : 'Tidak Diketahui';
                                    ?>
                                    <tr class="product-list border-b border-defaultborder dark:border-defaultborder/10">
                                        <td><?php echo htmlspecialchars($no); ?></td>
                                        <td>
                                            <div class="flex">
                                                <div class="ms-2">
                                                    <p class="font-semibold mb-0 flex items-center"><a href="javascript:void(0);"><?php echo htmlspecialchars($produk['product_name']); ?></a></p>
                                                    <p class="text-sm text-textmuted dark:text-textmuted/50 mb-0">
                                                        <?php echo htmlspecialchars($produk['posisi'] ?? 'N/A') . ' - ' . htmlspecialchars($produk['kode_item'] ?? 'N/A') . ' - ' . htmlspecialchars($produk['batch_number'] ?? 'N/A'); ?>
                                                    </p>

                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($produk['barcode'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($produk['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($modal); ?></td>
                                        <td><?php echo htmlspecialchars($jual); ?></td>
                                        <td>
                                            <span class="badge <?php echo $expire_produk_info['badge_class']; ?>"> <?php echo htmlspecialchars($expire_produk_info['days_left']); ?> </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($produk['unit'] ?? 'N/A'); ?></td>
                                        <td><?php echo $stoksisa; ?></td>
                                        <td><?php echo $supplier_name; ?></td>
                                        <td>
                                            <div class="hstack gap-2 text-[15px]">
                                                <a href="javascript:void(0);" class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-primary" data-hs-overlay="#modal-edit-barang-<?php echo $produk['product_id']; ?>">
                                                <i class="ri-edit-line"></i>
                                                </a>
                                                <a href="javascript:void(0);" class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-danger product-btn" onclick="confirmDelete(<?php echo $produk['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($produk['product_name'])); ?>')">
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
                                                                    <input type="tel" class="ti-form-control" id="expire" name="expire" value="<?php echo htmlspecialchars($produk['expiry_date']); ?>" placeholder="DD-MM-YYYY" required />
                                                                </div>
                                                                <!-- Batch Number -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Batch Number</label>
                                                                    <input type="text" class="ti-form-control" name="batch_number" value="<?php echo htmlspecialchars($produk['batch_number']); ?>" />
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
                                                                
                                                                <!-- Stok Saat Ini (Read-only atau sebagai referensi) -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Stok Saat Ini</label>
                                                                    <input type="text" class="ti-form-control bg-gray-200" name="stok_saat_ini_display" step="0.01" value="<?php echo htmlspecialchars($produk['stock_quantity']); ?>" disabled />
                                                                </div>

                                                                <!-- Stok Minimum (tetap ada jika masih diperlukan) -->
                                                                <div class="xl:col-span-3 col-span-12">
                                                                    <label class="ti-form-label">Stok Min</label>
                                                                    <input type="tel" class="ti-form-control" name="stok_minimum" step="0.01" value="<?php echo htmlspecialchars($produk['minimum_stock']); ?>" required />
                                                                </div>
                                                                <!-- Supplier -->
                                                                <div class="xl:col-span-6 col-span-12">
                                                                    <label class="ti-form-label">Supplier</label>
                                                                    <select class="ti-form-control" name="supplier_id">
                                                                        <option class="font-semibold text-sm" value=""><?php echo $supplier_name;?></option>
                                                                        <?php foreach ($suppliers as $supplier): ?>
                                                                        <option value="<?= htmlspecialchars($supplier['supplier_id'], ENT_QUOTES, 'UTF-8') ?>" 
                                                                            <?= $produk['supplier_id'] == $supplier['supplier_id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8') ?> <!-- Corrected to show supplier_name -->
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
                        <!-- Pagination Controls -->
                        <?php if ($total_pages > 1 && empty($errorMessage)): ?>
                        <nav aria-label="Page navigation" class="mt-4 flex justify-center">
                            <ul class="ti-pagination">
                                <!-- Tombol Previous -->
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>">Previous</a></li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                                <?php endif; ?>

                                <!-- Nomor Halaman -->
                                <?php 
                                    $num_links_to_show = 5; // Jumlah link halaman yang ditampilkan (misal: 1 ... 4 5 6 ... 10)
                                    $start = max(1, $current_page - floor($num_links_to_show / 2));
                                    $end = min($total_pages, $current_page + floor($num_links_to_show / 2));

                                    if ($end - $start + 1 < $num_links_to_show) {
                                        if ($start == 1) {
                                            $end = min($total_pages, $start + $num_links_to_show - 1);
                                        } elseif ($end == $total_pages) {
                                            $start = max(1, $end - $num_links_to_show + 1);
                                        }
                                    }
                                ?>
                                <?php if ($start > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=1<?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>">1</a></li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <li class="page-item active" aria-current="page"><span class="page-link"><?= $i ?></span></li>
                                    <?php else: ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?= $i ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>"><?= $i ?></a></li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>"><?= $total_pages ?></a></li>
                                <?php endif; ?>

                                <!-- Tombol Next -->
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?><?= !empty($search_query) ? '&search_query='.urlencode($search_query) : '' ?>">Next</a></li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <!-- End Pagination Controls -->
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
        <script src="../assets/js/beli_sup.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <script>
        
        // Inisialisasi Flatpickr untuk input tanggal
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr(".flatpickr-date", {
                dateFormat: "d-m-Y",
                allowInput: true // Memungkinkan input manual juga
            });

            // Event listener untuk input pencarian jika ingin search on enter
            const searchInput = document.getElementById('search-barang-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault(); // Mencegah submit form jika ada
                        performSearch();
                    }
                });
            }
        });

        function performSearch() {
            const query = document.getElementById('search-barang-input').value;
            const params = new URLSearchParams(window.location.search);
            params.set('search_query', query);
            params.set('page', '1'); // Selalu reset ke halaman 1 saat pencarian baru
            window.location.href = window.location.pathname + '?' + params.toString();
        }

        function clearSearch() {
            const params = new URLSearchParams(window.location.search);
            params.delete('search_query');
            params.set('page', '1'); // Reset ke halaman 1
            window.location.href = window.location.pathname + '?' + params.toString();
        }
        
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
    </body>
</html>
