<?php
require_once '../functions.php'; // Pastikan path ini benar

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

// --- Ambil data pembelian ---
$purchases = []; 
if (isset($farma) && method_exists($farma, 'getPurchases')) {
    // Anda bisa meneruskan filter tanggal dari form ke getPurchases jika fungsi tersebut mendukungnya
    // Untuk saat ini, kita biarkan filter tanggal dilakukan di PHP sesuai skrip awal Anda
    $filter_start_date_for_query = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $filter_end_date_for_query = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    // Jika Anda ingin getPurchases menangani filter tanggal, gunakan:
    // $purchases = $farma->getPurchases($filter_start_date_for_query, $filter_end_date_for_query);
    // Jika tidak, dan filter tanggal murni di PHP setelah fetch semua data:
    $purchases = $farma->getPurchases(); // Mengambil semua, lalu filter di PHP
} else {
    // Fallback jika $farma atau getPurchases tidak tersedia
}


// --- Buat daftar supplier unik untuk dropdown ---
$all_suppliers = [];
if (!empty($purchases)) {
    foreach ($purchases as $purchase) {
        if (isset($purchase['supplier_name']) && !empty($purchase['supplier_name']) && !in_array($purchase['supplier_name'], $all_suppliers)) {
            $all_suppliers[] = $purchase['supplier_name'];
        }
    }
    sort($all_suppliers); 
}

// --- Filter Logic ---
$filter_payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filter_supplier_name = isset($_GET['supplier_name']) ? $_GET['supplier_name'] : ''; 

// --- Inisialisasi variabel ---
$filtered_purchases = $purchases; // Mulai dengan semua pembelian, lalu filter

// --- Apply Filters Sequentially ---

// 1. Filter berdasarkan Nama Supplier (dari dropdown, pencocokan persis)
if (!empty($filter_supplier_name)) {
    $filtered_purchases = array_filter($filtered_purchases, function ($purchase) use ($filter_supplier_name) {
        return isset($purchase['supplier_name']) && $purchase['supplier_name'] === $filter_supplier_name;
    });
}

// 2. Filter berdasarkan Status Pembayaran (diterapkan pada hasil filter supplier)
if (!empty($filter_payment_status)) {
    $filtered_purchases = array_filter($filtered_purchases, function ($purchase) use ($filter_payment_status) {
        return isset($purchase['payment_status']) && $purchase['payment_status'] === $filter_payment_status;
    });
}

// 3. Filter berdasarkan Rentang Tanggal (diterapkan pada hasil filter sebelumnya)
if (!empty($filter_start_date) && !empty($filter_end_date)) {
    $date_filtered_purchases_temp = [];
    foreach ($filtered_purchases as $purchase) {
        if (isset($purchase['purchase_date'])) {
            $purchase_date_timestamp = strtotime($purchase['purchase_date']);
            $start_timestamp = strtotime($filter_start_date);
            $end_timestamp = strtotime($filter_end_date . ' 23:59:59'); 

            if ($start_timestamp !== false && $end_timestamp !== false && $purchase_date_timestamp >= $start_timestamp && $purchase_date_timestamp <= $end_timestamp) {
                $date_filtered_purchases_temp[] = $purchase; 
            }
        }
    }
    $filtered_purchases = $date_filtered_purchases_temp; 
}
// JIKA HANYA SALAH SATU TANGGAL YANG DIISI (SESUAIKAN JIKA PERLU LOGIKA INI)
// Bagian ini bisa Anda kembangkan jika ingin filter dengan hanya start_date atau end_date
// else if (!empty($filter_start_date)) { ... }
// else if (!empty($filter_end_date)) { ... }


// --- PERHITUNGAN TOTAL HUTANG KESELURUHAN YANG SUDAH DIKOREKSI ---\n
$total_hutang_overall = 0;      
foreach ($filtered_purchases as $purchase_item) { // Gunakan variabel berbeda untuk item loop
    // Hanya hitung jika statusnya hutang atau cicil
    if (isset($purchase_item['payment_status']) && ($purchase_item['payment_status'] === 'hutang' || $purchase_item['payment_status'] === 'cicil')) {
        
        $total_amount_item = isset($purchase_item['total_amount']) ? floatval($purchase_item['total_amount']) : 0;
        // PASTIKAN $purchase_item['amount_already_paid'] TERSEDIA DARI getPurchases() (via p.*)
        $amount_paid_item = isset($purchase_item['amount_already_paid']) ? floatval($purchase_item['amount_already_paid']) : 0;
        
        $sisa_hutang_item = $total_amount_item - $amount_paid_item;

        if ($sisa_hutang_item > 0) { // Hanya tambahkan jika memang masih ada sisa hutang
            $total_hutang_overall += $sisa_hutang_item;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">

<head>
    <?php include "includes/meta.php"; ?>
    <title>Daftar Hutang Supplier</title> <!-- Judul disesuaikan -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php include "includes/switch.php"; ?>
    <div id="loader" style="display:none;"><img src="../assets/images/media/loader.svg" alt="Loading..."></div>
    <div class="page">
        <?php include "includes/header.php"; ?>
        <?php include "includes/sidebar.php"; ?>
        
        <div class="main-content app-content">
            <div class="container-fluid">
                
                <div class="box mb-6">
                    <div class="box-header"><h5 class="box-title">Filter Hutang Pembelian</h5></div>
                    <div class="box-body">
                        <form method="GET" action=""> 
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                                <div>
                                    <label for="supplier_name_filter" class="ti-form-label">Nama Supplier:</label>
                                    <select id="supplier_name_filter" name="supplier_name" class="ti-form-select">
                                        <option value="">Semua Supplier</option>
                                        <?php if (!empty($all_suppliers)): ?>
                                            <?php foreach ($all_suppliers as $supplier): ?>
                                                <option value="<?php echo htmlspecialchars($supplier); ?>" <?php echo ($filter_supplier_name === $supplier) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($supplier); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="payment_status_filter" class="ti-form-label">Status Bayar:</label>
                                    <select id="payment_status_filter" name="payment_status" class="ti-form-select">
                                        <option value="">Semua Status (Hutang/Cicil)</option> <!-- Diperjelas -->
                                        <option value="hutang" <?php echo ($filter_payment_status === 'hutang') ? 'selected' : ''; ?>>Hutang</option>
                                        <option value="cicil" <?php echo ($filter_payment_status === 'cicil') ? 'selected' : ''; ?>>Cicil</option>
                                        <!-- Opsi 'lunas' mungkin kurang relevan di halaman hutang, tapi bisa ditambahkan jika perlu -->
                                        <!-- <option value="lunas" <?php echo ($filter_payment_status === 'lunas') ? 'selected' : ''; ?>>Lunas</option> -->
                                    </select>
                                </div>
                                <div>
                                    <label for="start_date_filter" class="ti-form-label">Dari Tanggal Beli:</label>
                                    <input type="text" id="start_date_filter" name="start_date" class="ti-form-input flatpickr-input" value="<?php echo htmlspecialchars($filter_start_date); ?>" placeholder="YYYY-MM-DD">
                                </div>
                                <div>
                                    <label for="end_date_filter" class="ti-form-label">Sampai Tanggal Beli:</label>
                                    <input type="text" id="end_date_filter" name="end_date" class="ti-form-input flatpickr-input" value="<?php echo htmlspecialchars($filter_end_date); ?>" placeholder="YYYY-MM-DD">
                                </div>
                                <div>
                                    <button type="submit" class="ti-btn ti-btn-primary w-full">
                                        <i class="fas fa-filter me-1"></i> Terapkan Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php 
                $has_active_filters_for_total = !empty($filter_supplier_name) || 
                                                !empty($filter_payment_status) || 
                                                (!empty($filter_start_date) && !empty($filter_end_date));
                
                // Tampilkan total hanya jika ada filter aktif ATAU jika tidak ada filter sama sekali (total semua hutang)
                // Dan hanya jika ada hutang
                if ($total_hutang_overall > 0): 
                ?>
                <div class="box mb-6">
                    <div class="box-header">
                        <h5 class="box-title">
                            Total Sisa Hutang 
                            <?php 
                                $title_parts = [];
                                if (!empty($filter_supplier_name)) $title_parts[] = "Supp: " . htmlspecialchars($filter_supplier_name);
                                if (!empty($filter_payment_status)) $title_parts[] = "Status: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $filter_payment_status)));
                                if (!empty($filter_start_date) && !empty($filter_end_date)) {
                                    $start_f = date("d M Y", strtotime($filter_start_date));
                                    $end_f = date("d M Y", strtotime($filter_end_date));
                                    $title_parts[] = "Periode: " . $start_f . " - " . $end_f;
                                } elseif (!empty($filter_start_date)) $title_parts[] = "Dari: " . date("d M Y", strtotime($filter_start_date));
                                elseif (!empty($filter_end_date)) $title_parts[] = "Sampai: " . date("d M Y", strtotime($filter_end_date));
                                
                                if (!empty($title_parts)) echo "(" . implode(', ', $title_parts) . ")";
                                else echo "(Keseluruhan)";
                            ?>
                        </h5>
                    </div>
                    <div class="box-body">
                        <p class="text-4xl text-rose-500 font-bold">Rp <?php echo htmlspecialchars(number_format($total_hutang_overall, 2, ',', '.')); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    <i class="ri-money-dollar-box-line text-2xl me-2"></i> Daftar Hutang ke Supplier 
                                </div>
                                <div class="flex gap-2 items-center">
                                    <input class="ti-form-control" type="text" placeholder="Cari No. Inv/Supplier..." id="search-pembelian-table">
                                    <a href="beli.php" class="ti-btn ti-btn-sm !m-0 ti-btn-primary text-nowrap">
                                        <i class="ri-add-line me-1"></i>Tambah Pembelian
                                    </a>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="max-h-[70vh] overflow-y-auto table-responsive">
                                    <table id="purchasesTable" class="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 table border border-defaultborder dark:border-defaultborder/10 w-full">
                                        <thead class="bg-sky-400 sticky top-0 z-10">
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th class="px-4 py-2 text-left">No. Invoice</th>
                                                <th class="px-4 py-2 text-left">Supplier</th>
                                                <th class="px-4 py-2 text-left">Tgl. Beli</th>
                                                <th class="px-4 py-2 text-left">Jatuh Tempo</th>
                                                <th class="px-4 py-2 text-right">Total Tagihan</th>
                                                <th class="px-4 py-2 text-right">Sudah Dibayar</th> <!-- TAMBAHAN -->
                                                <th class="px-4 py-2 text-right text-red-600 dark:text-red-400">Sisa Hutang</th> <!-- TAMBAHAN & PENYESUAIAN -->
                                                <th class="px-4 py-2 text-center">Status Bayar</th>
                                                <th class="px-4 py-2 text-center">Status Terima</th>
                                                <th class="px-4 py-2 text-left">User Input</th>
                                                <th class="px-4 py-2 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($filtered_purchases)): ?>
                                                <?php foreach ($filtered_purchases as $purchase_row): // Gunakan nama variabel berbeda ?>
                                                    <?php
                                                        // Hitung sisa hutang per item untuk ditampilkan
                                                        $item_total = isset($purchase_row['total_amount']) ? floatval($purchase_row['total_amount']) : 0;
                                                        $item_paid = isset($purchase_row['amount_already_paid']) ? floatval($purchase_row['amount_already_paid']) : 0;
                                                        $item_sisa_hutang = $item_total - $item_paid;

                                                        // Tampilkan baris hanya jika ada sisa hutang (relevan untuk halaman hutang)
                                                        // atau jika tidak ada filter status bayar aktif (menampilkan semua hutang/cicil)
                                                        $show_row = false;
                                                        if ($item_sisa_hutang > 0) {
                                                            if (empty($filter_payment_status)) { // Jika tidak ada filter status, tampilkan semua yg punya sisa hutang
                                                                $show_row = true;
                                                            } elseif (isset($purchase_row['payment_status']) && $purchase_row['payment_status'] === $filter_payment_status) {
                                                                $show_row = true; // Jika ada filter status, cocokkan
                                                            }
                                                        }
                                                        // Jika Anda ingin tetap menampilkan item yang statusnya "hutang" atau "cicil" meskipun sisa hutangnya 0 (misal karena baru dilunasi),
                                                        // Anda bisa menyesuaikan logika $show_row ini.
                                                        // Untuk halaman "Daftar Hutang", biasanya hanya yang benar-benar masih ada sisa hutang yang ditampilkan.
                                                        if (!$show_row && !($item_sisa_hutang > 0 && ($purchase_row['payment_status'] === 'hutang' || $purchase_row['payment_status'] === 'cicil'))) {
                                                            // Jika filter status aktif dan item tidak cocok ATAU sisa hutang <=0, skip baris ini
                                                            // kecuali jika tidak ada filter status, maka hanya sisa hutang > 0 yang relevan
                                                            if(!empty($filter_payment_status) || $item_sisa_hutang <=0 ) continue;
                                                        }

                                                    ?>
                                                    <tr class="border-b border-defaultborder dark:border-defaultborder/10 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars($purchase_row['invoice_number'] ?? '-'); ?></td>
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars($purchase_row['supplier_name'] ?? '-'); ?></td>
                                                        <td class="px-4 py-2"><?php echo isset($purchase_row['purchase_date']) ? htmlspecialchars(date('d M Y', strtotime($purchase_row['purchase_date']))) : '-'; ?></td>
                                                        <td class="px-4 py-2"><?php echo (isset($purchase_row['due_date']) && !empty($purchase_row['due_date'])) ? htmlspecialchars(date('d M Y', strtotime($purchase_row['due_date']))) : '-'; ?></td>
                                                        <td class="px-4 py-2 text-right"><?php echo htmlspecialchars(number_format($item_total, 2, ',', '.')); ?></td>
                                                        <td class="px-4 py-2 text-right"><?php echo htmlspecialchars(number_format($item_paid, 2, ',', '.')); ?></td>
                                                        <td class="px-4 py-2 text-right font-semibold <?php echo $item_sisa_hutang > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'; ?>">
                                                            <?php echo htmlspecialchars(number_format($item_sisa_hutang, 2, ',', '.')); ?>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <?php 
                                                                $payment_status = $purchase_row['payment_status'] ?? 'tidak_diketahui';
                                                                $badge_class = 'bg-gray-500'; 
                                                                if ($item_sisa_hutang <= 0 && $payment_status !== 'lunas') $payment_status = 'lunas'; // Koreksi status jika sisa 0
                                                                switch ($payment_status) {
                                                                    case 'lunas': $badge_class = 'bg-green-500 text-white'; break;
                                                                    case 'cicil': $badge_class = 'bg-yellow-500 text-black'; break;
                                                                    case 'hutang': $badge_class = 'bg-red-500 text-white'; break;
                                                                }
                                                            ?>
                                                            <span class="capitalize badge <?php echo $badge_class; ?>">
                                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment_status))); ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <?php 
                                                                $received_status = $purchase_row['received_status'] ?? 'tidak_diketahui';
                                                                $received_badge_class = 'bg-gray-500 text-white';
                                                                switch ($received_status) {
                                                                    case 'diterima': $received_badge_class = 'bg-green-500 text-white'; break;
                                                                    case 'diterima_sebagian': $received_badge_class = 'bg-yellow-500 text-black'; break;
                                                                    case 'belum_diterima': $received_badge_class = 'bg-blue-500 text-white'; break; 
                                                                }
                                                            ?>
                                                            <span class="capitalize badge <?php echo $received_badge_class; ?>">
                                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $received_status))); ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars($purchase_row['user_name'] ?? '-'); ?></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <div class="flex items-center justify-center space-x-1">
                                                                <a href="view_purchase.php?purchase_id=<?php echo $purchase_row['purchase_id'] ?? ''; ?>" class="ti-btn ti-btn-xs ti-btn-info me-1" title="Lihat Detail Pembelian"><i class="ri-eye-line"></i></a>
                                                                <a href="edit_purchase_items.php?purchase_id=<?php echo $purchase_row['purchase_id'] ?? ''; ?>" class="ti-btn ti-btn-xs ti-btn-warning me-1" title="Edit Item Produk"><i class="ri-pencil-line"></i></a>
                                                                <?php if ($item_sisa_hutang > 0): // Tombol bayar hanya jika masih ada sisa hutang ?>
                                                                <a href="edit_purchase.php?purchase_id=<?php echo $purchase_row['purchase_id'] ?? ''; ?>" class="ti-btn ti-btn-xs ti-btn-success" title="Edit Status Bayar & Penerimaan"><i class="ri-money-dollar-circle-line"></i></a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="11" class="text-center px-4 py-10">Tidak ada data hutang pembelian yang sesuai dengan filter atau belum ada hutang.</td>
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
        <?php include "includes/footer.php"; ?>
    </div>
    <div class="scrollToTop"><span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span></div>
    <div id="responsive-overlay"></div>

    <script src="../assets/libs/flatpickr/flatpickr.min.js"></script> <!-- Pastikan flatpickr.min.js ada atau gunakan CDN -->
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
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr(".flatpickr-input", { // Target semua input dengan class flatpickr-input
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
        });

        const searchInput = document.getElementById('search-pembelian-table');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let table = document.getElementById('purchasesTable');
                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) { 
                    let displayRow = false;
                    for (let j = 0; j < tr[i].cells.length; j++) { // Loop through all cells in a row
                        let td = tr[i].cells[j];
                        if (td) {
                            if ((td.textContent || td.innerText).toLowerCase().indexOf(filter) > -1) {
                                displayRow = true;
                                break; // Found a match in this row, no need to check other cells
                            }
                        }
                    }
                    tr[i].style.display = displayRow ? "" : "none";
                }
            });
        }
        
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'none';
    });
    </script>
</body>
</html>
