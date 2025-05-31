<?php
require_once '../functions.php'; // Path to your functions.php

// $farma sudah diinstansiasi di akhir functions.php

// Cek sesi dan role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$pdo = $farma->getPDO();

$search_posisi = ''; // Akan diisi dari POST atau dibiarkan kosong
$products = [];
$opname_results = null;
$user_id = $_SESSION['user_id'] ?? null;
$clear_storage_for_posisi = null; 

// Handle pencarian produk berdasarkan posisi (baik dari submit manual maupun auto-submit JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_by_posisi'])) {
    $search_posisi = trim($_POST['posisi_rak']);
    if (!empty($search_posisi)) {
        $products = $farma->getProductsByPosisiLike($search_posisi);
    }
}

// Handle penyimpanan stok opname batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_opname'])) {
    $product_ids = $_POST['product_id'] ?? [];
    $actual_stocks = $_POST['actual_stock_fisik'] ?? [];
    // Ambil $search_posisi dari hidden input, karena $search_posisi di atas mungkin kosong jika ini bukan POST search
    $searched_posisi_for_opname = trim($_POST['searched_posisi_for_opname'] ?? ''); 

    $stockOpnameData = [];
    foreach ($product_ids as $index => $product_id) {
        if (isset($actual_stocks[$index]) && $actual_stocks[$index] !== '' && is_numeric($actual_stocks[$index])) {
            $stockOpnameData[] = [
                'product_id' => (int)$product_id,
                'actual_stock' => (float)$actual_stocks[$index]
            ];
        }
    }

    if (!empty($stockOpnameData) && $user_id) {
        $reason = "Stok Opname Batch per Posisi: " . $searched_posisi_for_opname;
        $opname_results = $farma->performBatchStockOpname($stockOpnameData, $user_id, $reason);
        
        if ($opname_results['success'] === true) {
            $clear_storage_for_posisi = $searched_posisi_for_opname; 
        }
        
        // Setelah opname, muat ulang produk untuk posisi yang sama untuk melihat stok terbaru
        // $search_posisi di sini akan diisi dengan $searched_posisi_for_opname agar tabel yang benar ditampilkan
        if (!empty($searched_posisi_for_opname)) {
             $search_posisi = $searched_posisi_for_opname; 
             $products = $farma->getProductsByPosisiLike($searched_posisi_for_opname);
        }

    } elseif (empty($stockOpnameData) && isset($_POST['product_id'])) { 
        $opname_results = ['success' => false, 'message' => 'Tidak ada data stok fisik yang valid untuk disimpan. Pastikan kolom stok fisik diisi dengan angka.'];
         // Jika gagal simpan, tetap tampilkan tabel untuk posisi yang sedang dikerjakan
        if (!empty($searched_posisi_for_opname)) {
            $search_posisi = $searched_posisi_for_opname;
            $products = $farma->getProductsByPosisiLike($searched_posisi_for_opname);
        }
    } elseif (!$user_id){
        $opname_results = ['success' => false, 'message' => 'User ID tidak ditemukan.'];
    }
}

?>
<!DOCTYPE html>
<html lang="id" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php"; ?>
    <title>Stok Opname per Posisi/Rak</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .table th, .table td { vertical-align: middle; }
        .form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .875rem; line-height: 1.5; border-radius: .2rem; }
        .input-stok-fisik { min-width: 80px; max-width: 120px; }
        .box .card { margin-bottom: 1.5rem; }
        .box .card:last-child { margin-bottom: 0; }
        .table-responsive { max-height: 60vh; overflow-y: auto; position: relative; }
        .table-responsive thead th { position: -webkit-sticky; position: sticky; top: 0; z-index: 2; background-color: var(--table-bg, #ffffff); }
        .table-light thead th { background-color: #f8f9fa; } /* Specific for .table-light */
        .table-dark thead th { background-color: #343a40; color: #fff; } /* Specific for .table-dark */
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
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                    <h1 class="page-title fw-semibold fs-18 mb-0">Stok Opname per Posisi/Rak</h1>
                    
                </div>

                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box p-5 md:p-10">
                            <div class="card custom-card">
                                <div class="card-header"><h5 class="card-title mb-0">Cari Produk berdasarkan Posisi/Rak</h5></div>
                                <div class="card-body">
                                    <form method="POST" action="" id="searchForm">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4 mb-4">
                                                <input type="text" class="form-control" id="posisi_rak" name="posisi_rak" value="<?= htmlspecialchars($search_posisi) ?>" placeholder="Contoh: Rak A1">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" name="search_by_posisi" class="ti-btn ti-btn-primary">Cari Produk</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <?php if ($opname_results): ?>
                            <div class="card custom-card">
                                <div class="card-body">
                                    <div class="alert alert-<?= $opname_results['success'] ? 'success' : 'danger' ?> alert-dismissible fade show mb-0" role="alert">
                                        <?= htmlspecialchars($opname_results['message']) ?>
                                        <?php if (isset($opname_results['details']) && is_array($opname_results['details']) && ($opname_results['failed_items'] > 0 || $opname_results['adjusted_items'] > 0 || $opname_results['accurate_items'] > 0 ) ): ?>
                                            <hr><strong>Ringkasan Proses:</strong><ul>
                                            <?php if(isset($opname_results['adjusted_items']) && $opname_results['adjusted_items'] > 0) echo "<li>Item Disesuaikan: " . htmlspecialchars($opname_results['adjusted_items']) . "</li>"; ?>
                                            <?php if(isset($opname_results['accurate_items']) && $opname_results['accurate_items'] > 0) echo "<li>Item Akurat: " . htmlspecialchars($opname_results['accurate_items']) . "</li>"; ?>
                                            <?php if(isset($opname_results['failed_items']) && $opname_results['failed_items'] > 0) echo "<li>Item Gagal: " . htmlspecialchars($opname_results['failed_items']) . "</li>"; ?>
                                            </ul>
                                            <?php if ($opname_results['failed_items'] > 0): ?><strong>Detail Kegagalan:</strong><ul>
                                            <?php foreach ($opname_results['details'] as $detail): if (!$detail['success']): ?>
                                            <li>Prod ID <?= htmlspecialchars($detail['product_id'] ?? 'N/A') ?> (<?= htmlspecialchars($detail['product_name'] ?? 'N/A') ?>): <?= htmlspecialchars($detail['message']) ?></li>
                                            <?php endif; endforeach; ?></ul>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($products)): ?>
                            <div class="card custom-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-3">Daftar Produk untuk Posisi/Rak: "<span id="currentSearchPosisiDisplay"><?= htmlspecialchars($search_posisi) ?></span>"</h5>
                                    
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="opnameForm">
                                        <input type="hidden" name="searched_posisi_for_opname" value="<?= htmlspecialchars($search_posisi) ?>">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped text-nowrap table-light">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">No</th>
                                                        <th scope="col">Kode</th>
                                                        <th scope="col">Nama Produk</th>
                                                        <th scope="col">Posisi</th>
                                                        <th scope="col">Satuan</th>
                                                        <th scope="col" class="text-end">Stok Sistem</th>
                                                        <th scope="col" class="text-center">Stok Fisik</th>
                                                        <th scope="col" class="text-end">Selisih</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($products as $index => $product): ?>
                                                <tr data-product-id="<?= $product['product_id'] ?>">
                                                    <td><?= $index + 1 ?></td><td><?= htmlspecialchars($product['kode_item']) ?></td>
                                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                    <td><?= htmlspecialchars($product['posisi']) ?></td>
                                                    <td><?= htmlspecialchars($product['unit']) ?></td>
                                                    <td class="text-center stock-sistem"><?= htmlspecialchars(number_format($product['stock_quantity'], 0, ',', '.')) ?></td>
                                                    <td class="text-center">
                                                        <input type="hidden" name="product_id[]" value="<?= $product['product_id'] ?>">
                                                        <input type="number" step="any" class="form-control form-control-sm input-stok-fisik text-center" name="actual_stock_fisik[]" value="" placeholder="0">
                                                    </td>
                                                    <td class="text-center selisih">0</td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-end mt-4"><button type="submit" name="save_opname" class="ti-btn ti-btn-success" form="opnameForm">Simpan Semua Perubahan</button></div>
                            </div>
                            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_by_posisi']) && empty($products) && !empty($search_posisi)): ?>
                            <div class="card custom-card"><div class="card-body"><p class="text-center mb-0">Tidak ada produk untuk posisi/rak "<?= htmlspecialchars($search_posisi) ?>".</p></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
        <?php include "includes/footer.php";?>
    </div>
    <div class="scrollToTop"><span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span></div>
    <div id="responsive-overlay"></div>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script> 
    <script src="../assets/js/switch.js"></script>
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
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
        const lastSearchedPosisiKey = 'opname_last_searched_posisi';
        const currentRequestMethod = '<?= $_SERVER['REQUEST_METHOD'] ?>';
        // $search_posisi dari PHP adalah posisi yang *sebenarnya* digunakan untuk query data saat ini
        const phpRenderedSearchPosisi = <?= json_encode($search_posisi) ?>; 
        const phpClearStorageForOpnameData = <?= json_encode($clear_storage_for_posisi) ?>;

        function getOpnameDataLocalStorageKey(posisiRak) {
            if (!posisiRak || typeof posisiRak !== 'string' || posisiRak.trim() === '') return null;
            return 'opnameData_' + posisiRak.trim().toLowerCase().replace(/\s+/g, '_').replace(/[^\w-]+/g, '');
        }

        function calculateDifference(row) {
            const stockSistemText = $(row).find('.stock-sistem').text().replace(/\./g, '').replace(',', '.');
            const stockSistem = parseFloat(stockSistemText) || 0;
            const stockFisik = parseFloat($(row).find('.input-stok-fisik').val()) || 0;
            const selisih = stockFisik - stockSistem;
            $(row).find('.selisih').text(selisih.toLocaleString('id-ID'));
        }

        function loadOpnameItemValues() {
            // Hanya load item values jika phpRenderedSearchPosisi ada (artinya tabel ditampilkan untuk posisi tsb)
            if (phpRenderedSearchPosisi && $('tbody tr').length > 0) {
                const storageKey = getOpnameDataLocalStorageKey(phpRenderedSearchPosisi);
                if (storageKey) {
                    const savedData = JSON.parse(localStorage.getItem(storageKey) || '{}');
                    if (Object.keys(savedData).length > 0) {
                        $('tbody tr').each(function() {
                            const row = $(this);
                            const productId = row.data('product-id');
                            if (savedData.hasOwnProperty(productId)) {
                                row.find('.input-stok-fisik').val(savedData[productId]);
                                calculateDifference(row);
                            }
                        });
                    }
                }
            }
        }

        function saveOpnameItemValue(productId, stockFisik) {
            // Simpan item values berdasarkan phpRenderedSearchPosisi (posisi tabel yang sedang aktif)
            const contextPosisiRak = phpRenderedSearchPosisi; 
            if (contextPosisiRak && productId) {
                const storageKey = getOpnameDataLocalStorageKey(contextPosisiRak);
                if (storageKey) {
                    let data = JSON.parse(localStorage.getItem(storageKey) || '{}');
                    if (stockFisik === '' || stockFisik === null) { delete data[productId]; } 
                    else { data[productId] = stockFisik; }

                    if (Object.keys(data).length === 0) { localStorage.removeItem(storageKey); } 
                    else { localStorage.setItem(storageKey, JSON.stringify(data)); }
                }
            }
        }

        // --- Logika Inisialisasi Halaman ---

        // 1. Auto-fill dan auto-submit search form jika ini adalah GET request (load/reload awal)
        if (currentRequestMethod === 'GET') {
            const storedLastPosisi = localStorage.getItem(lastSearchedPosisiKey);
            if (storedLastPosisi && storedLastPosisi.trim() !== '') {
                $('#posisi_rak').val(storedLastPosisi);
                if (!$('body').data('autoSubmitted')) { // Mencegah submit berulang jika ready() terpanggil lagi
                    $('body').data('autoSubmitted', true);
                    // console.log('Auto-submitting search for last position:', storedLastPosisi);
                    $('button[name="search_by_posisi"]').click();
                }
            }
        }

        // 2. Update localStorage untuk 'lastSearchedPosisiKey' setelah PHP memproses search (jika POST)
        // atau jika halaman dirender dengan $search_posisi (misalnya setelah save opname berhasil)
        if (phpRenderedSearchPosisi && phpRenderedSearchPosisi.trim() !== '') {
            localStorage.setItem(lastSearchedPosisiKey, phpRenderedSearchPosisi.trim());
        } else if (currentRequestMethod === 'POST' && (!phpRenderedSearchPosisi || phpRenderedSearchPosisi.trim() === '')) {
            // Jika search POST menghasilkan posisi kosong (misal user hapus input & search)
            localStorage.removeItem(lastSearchedPosisiKey);
        }
        
        // 3. Update UI (input field & display span) untuk konsistensi dengan apa yang dirender PHP
        if(phpRenderedSearchPosisi){
            $('#posisi_rak').val(phpRenderedSearchPosisi); // Pastikan input search konsisten
            $('#currentSearchPosisiDisplay').text(phpRenderedSearchPosisi);
        } else {
             $('#currentSearchPosisiDisplay').text('Belum ada'); // Default jika tidak ada pencarian
        }

        // 4. Clear opname *item values* dari LocalStorage jika opname berhasil disimpan
        if (phpClearStorageForOpnameData) {
            const opnameDataKeyToClear = getOpnameDataLocalStorageKey(phpClearStorageForOpnameData);
            if (opnameDataKeyToClear) {
                localStorage.removeItem(opnameDataKeyToClear);
                // console.log('Opname data LocalStorage cleared for:', phpClearStorageForOpnameData);
            }
        }

        // 5. Load opname *item values* untuk tabel yang ditampilkan
        loadOpnameItemValues();

        // --- Event Handlers ---
        $('.input-stok-fisik').on('input', function() {
            const currentRow = $(this).closest('tr');
            const productId = currentRow.data('product-id');
            const stockFisikValue = $(this).val();
            saveOpnameItemValue(productId, stockFisikValue);
            calculateDifference(currentRow);
        });

        $('tbody tr').each(function() { // Hitung selisih untuk nilai yang mungkin sudah ada
            if ($(this).find('.input-stok-fisik').val() !== "") { calculateDifference(this); }
        });
    });
    </script>
</body>
</html>
