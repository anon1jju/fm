<?php
require_once '../functions.php'; // Pastikan path ini benar

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}
// Asumsikan $farma adalah objek yang sudah diinisialisasi dan memiliki metode getPurchases()
// Misalnya: $farma = new Farma(); atau sudah di-include dari functions.php
// Jika $farma belum ada, Anda perlu menginisialisasinya di sini atau di functions.php
// Contoh inisialisasi jika belum ada (sesuaikan dengan implementasi Anda):
// if (!isset($farma) && class_exists('Farma')) {
//     $farma = new Farma(); 
// } elseif (!isset($farma)) {
//     die("Objek Farma tidak tersedia."); // Atau handle error lainnya
// }


// --- Ambil data pembelian ---
$purchases = []; // Default ke array kosong
if (isset($farma) && method_exists($farma, 'getPurchases')) {
    $purchases = $farma->getPurchases(); 
} else {
    // Handle jika $farma atau getPurchases tidak tersedia, mungkin dengan pesan error atau data dummy
    // Untuk pengembangan, Anda bisa menggunakan data dummy jika $farma belum siap:
    /*
    $purchases = [
        ['invoice_number' => 'INV001', 'supplier_name' => 'Supplier A', 'purchase_date' => '2024-01-15', 'due_date' => '2024-02-15', 'total_amount' => 1000000, 'payment_status' => 'hutang', 'received_status' => 'diterima', 'user_name' => 'Admin', 'purchase_id' => 1],
        ['invoice_number' => 'INV002', 'supplier_name' => 'Supplier B', 'purchase_date' => '2024-01-20', 'due_date' => '2024-02-20', 'total_amount' => 500000, 'payment_status' => 'cicil', 'received_status' => 'diterima_sebagian', 'user_name' => 'Admin', 'purchase_id' => 2],
        ['invoice_number' => 'INV003', 'supplier_name' => 'Supplier A', 'purchase_date' => '2024-02-10', 'due_date' => '2024-03-10', 'total_amount' => 750000, 'payment_status' => 'lunas', 'received_status' => 'diterima', 'user_name' => 'Admin', 'purchase_id' => 3],
    ];
    */
}


// --- Buat daftar supplier unik untuk dropdown ---
$all_suppliers = [];
if (!empty($purchases)) {
    foreach ($purchases as $purchase) {
        if (isset($purchase['supplier_name']) && !empty($purchase['supplier_name']) && !in_array($purchase['supplier_name'], $all_suppliers)) {
            $all_suppliers[] = $purchase['supplier_name'];
        }
    }
    sort($all_suppliers); // Urutkan nama supplier
}

// --- Filter Logic ---
$filter_payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filter_supplier_name = isset($_GET['supplier_name']) ? $_GET['supplier_name'] : ''; 

// --- Inisialisasi variabel ---
$filtered_purchases = $purchases; // Mulai dengan semua pembelian, lalu filter
$total_hutang_overall = 0;      

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
//    dan hitung Total Hutang Keseluruhan untuk item yang difilter dalam rentang tanggal tersebut.
if (!empty($filter_start_date) && !empty($filter_end_date)) {
    $date_filtered_purchases_temp = []; // Temporary array for date-filtered items
    $current_total_hutang_for_date_range = 0;

    foreach ($filtered_purchases as $purchase) {
        if (isset($purchase['purchase_date'])) {
            $purchase_date_timestamp = strtotime($purchase['purchase_date']);
            // Validasi format tanggal filter sebelum digunakan
            $start_timestamp = strtotime($filter_start_date);
            $end_timestamp = strtotime($filter_end_date . ' 23:59:59'); // Include the whole end day

            if ($start_timestamp !== false && $end_timestamp !== false && $purchase_date_timestamp >= $start_timestamp && $purchase_date_timestamp <= $end_timestamp) {
                $date_filtered_purchases_temp[] = $purchase; // Add to the list of items within date range
                // Hitung total hutang HANYA untuk item dalam rentang tanggal ini yang juga hutang/cicil
                if (isset($purchase['payment_status']) && ($purchase['payment_status'] === 'hutang' || $purchase['payment_status'] === 'cicil')) {
                    $current_total_hutang_for_date_range += (isset($purchase['total_amount']) ? floatval($purchase['total_amount']) : 0);
                }
            }
        }
    }
    $filtered_purchases = $date_filtered_purchases_temp; // Update $filtered_purchases to only those in date range
    $total_hutang_overall = $current_total_hutang_for_date_range; // Set total hutang based on date-filtered items
} else {
    // Jika TIDAK ada filter tanggal, hitung total hutang dari $filtered_purchases saat ini (yang sudah terfilter supplier & status)
    foreach ($filtered_purchases as $purchase) {
        if (isset($purchase['payment_status']) && ($purchase['payment_status'] === 'hutang' || $purchase['payment_status'] === 'cicil')) {
            $total_hutang_overall += (isset($purchase['total_amount']) ? floatval($purchase['total_amount']) : 0);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">

<head>
    <?php include "includes/meta.php"; // Pastikan path ini benar ?>
    <title>Daftar Pembelian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tambahkan link CSS lain jika ada, misal untuk flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Contoh untuk ikon filter -->
</head>

<body>
    <?php include "includes/switch.php"; // Pastikan path ini benar ?>
    <!-- ========== END Switcher  ========== -->
    <!-- Loader -->
    <div id="loader">
        <img src="../assets/images/media/loader.svg" alt="Loading..."> <!-- Pastikan path ini benar -->
    </div>
    <!-- Loader -->
    <div class="page">
        <?php include "includes/header.php"; // Pastikan path ini benar ?>
        <!-- /app-header -->
        
        <!-- Start::app-sidebar -->
        <?php include "includes/sidebar.php"; // Pastikan path ini benar ?>
        <!-- End::app-sidebar -->
        
        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                
                <!-- Start::Filter Section -->
                <div class="box mb-6">
                    <div class="box-header">
                        <h5 class="box-title">Filter Pembelian</h5>
                    </div>
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
                                        <option value="">Semua Status</option>
                                        <option value="hutang" <?php echo ($filter_payment_status === 'hutang') ? 'selected' : ''; ?>>Hutang</option>
                                        <option value="cicil" <?php echo ($filter_payment_status === 'cicil') ? 'selected' : ''; ?>>Cicil</option>
                                        <option value="lunas" <?php echo ($filter_payment_status === 'lunas') ? 'selected' : ''; ?>>Lunas</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="start_date_filter" class="ti-form-label">Dari Tanggal:</label>
                                    <input type="text" id="start_date_filter" name="start_date" class="ti-form-input flatpickr-input" value="<?php echo htmlspecialchars($filter_start_date); ?>" placeholder="YYYY-MM-DD">
                                </div>
                                <div>
                                    <label for="end_date_filter" class="ti-form-label">Sampai Tanggal:</label>
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
                <!-- End::Filter Section -->

                <!-- Start::Totals Section -->
                <?php 
                $has_active_filters_for_total = !empty($filter_supplier_name) || 
                                                !empty($filter_payment_status) || 
                                                (!empty($filter_start_date) && !empty($filter_end_date));
                
                if ($has_active_filters_for_total && $total_hutang_overall > 0): 
                ?>
                <div class="box mb-6">
                    <div class="box-header">
                        <h5 class="box-title">
                            Total -
                            <?php 
                                $title_parts = [];
                                if (!empty($filter_supplier_name)) {
                                    $title_parts[] = "Supplier: " . htmlspecialchars($filter_supplier_name);
                                }
                                if (!empty($filter_payment_status)) {
                                    $title_parts[] = "Status: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $filter_payment_status)));
                                }
                                if (!empty($filter_start_date) && !empty($filter_end_date)) {
                                    $start_date_formatted = date("d M Y", strtotime($filter_start_date));
                                    $end_date_formatted = date("d M Y", strtotime($filter_end_date));
                                    $title_parts[] = "periode " . $start_date_formatted . " - " . $end_date_formatted;
                                } elseif (!empty($filter_start_date)) {
                                    $start_date_formatted = date("d M Y", strtotime($filter_start_date));
                                    $title_parts[] = "dari " . $start_date_formatted;
                                } elseif (!empty($filter_end_date)) {
                                    $end_date_formatted = date("d M Y", strtotime($filter_end_date));
                                    $title_parts[] = "sampai " . $end_date_formatted;
                                }
                                if (!empty($title_parts)) {
                                    echo "(" . implode(', ', $title_parts) . ")";
                                }
                            ?>
                        </h5>
                    </div>
                    <div class="box-body">
                        <p class="text-4xl text-rose-500 font-bold">Rp <?php echo htmlspecialchars(number_format($total_hutang_overall, 0, ',', '.')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <!-- End::Totals Section -->

                <!-- Start::row-2 (Main Purchase List Table) -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    <i class="ri-shopping-cart-2-fill text-2xl me-2"></i> Daftar Pembelian ke Supplier 
                                </div>
                                <div class="flex gap-2 items-center">
                                    <input class="ti-form-control" type="text" placeholder="Cari No. Inv/Supplier di tabel..." id="search-pembelian-table">
                                
                                    <a href="beli.php" class="ti-btn ti-btn-sm !m-0 ti-btn-primary text-nowrap"> <!-- Pastikan path ini benar -->
                                        <i class="ri-add-line me-1 align-middle text-sm font-semibold"></i>Tambah Pembelian
                                    </a>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="max-h-[70vh] overflow-y-auto table-responsive">
                                    <table id="purchasesTable" class="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 table border border-defaultborder dark:border-defaultborder/10 text-nowrap min-w-full">
                                        <thead class="bg-sky-400 sticky top-0 z-10">
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th class="px-4 py-2 text-left">No. Invoice</th>
                                                <th class="px-4 py-2 text-left">Supplier</th>
                                                <th class="px-4 py-2 text-left">Tgl. Pembelian</th>
                                                <th class="px-4 py-2 text-left">Jatuh Tempo</th>
                                                <th class="px-4 py-2 text-right">Total</th>
                                                <th class="px-4 py-2 text-center">Status Bayar</th>
                                                <th class="px-4 py-2 text-center">Status Terima</th>
                                                <th class="px-4 py-2 text-left">User</th>
                                                <th class="px-4 py-2 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($filtered_purchases)): ?>
                                                <?php foreach ($filtered_purchases as $purchase): ?>
                                                    <tr class="border-b border-defaultborder dark:border-defaultborder/10 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars(isset($purchase['invoice_number']) ? $purchase['invoice_number'] : '-'); ?></td>
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars(isset($purchase['supplier_name']) ? $purchase['supplier_name'] : '-'); ?></td>
                                                        <td class="px-4 py-2"><?php echo isset($purchase['purchase_date']) ? htmlspecialchars(date('d M Y', strtotime($purchase['purchase_date']))) : '-'; ?></td>
                                                        <td class="px-4 py-2"><?php echo (isset($purchase['due_date']) && !empty($purchase['due_date'])) ? htmlspecialchars(date('d M Y', strtotime($purchase['due_date']))) : '-'; ?></td>
                                                        <td class="px-4 py-2 text-right">Rp <?php echo htmlspecialchars(number_format(isset($purchase['total_amount']) ? floatval($purchase['total_amount']) : 0, 0, ',', '.')); ?></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <?php 
                                                                $payment_status = isset($purchase['payment_status']) ? $purchase['payment_status'] : 'tidak_diketahui';
                                                                $badge_class = 'bg-gray-500 text-white'; // Default
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
                                                                $received_status = isset($purchase['received_status']) ? $purchase['received_status'] : 'tidak_diketahui';
                                                                $received_badge_class = 'bg-gray-500 text-white'; // Default
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
                                                        <td class="px-4 py-2"><?php echo htmlspecialchars(isset($purchase['user_name']) ? $purchase['user_name'] : '-'); ?></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <a href="view_purchase.php?id=<?php echo isset($purchase['purchase_id']) ? $purchase['purchase_id'] : ''; ?>" class="ti-btn ti-btn-sm ti-btn-info me-4" title="Lihat Detail"> <!-- Pastikan path ini benar -->
                                                                Detail
                                                            </a>
                                                            <a href="edit_purchase.php?purchase_id=<?php echo isset($purchase['purchase_id']) ? $purchase['purchase_id'] : ''; ?>" class="ti-btn ti-btn-sm ti-btn-success me-4" title="Kelola Pembayaran"> <!-- Pastikan path ini benar -->
                                                                Bayar
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center px-4 py-10">Tidak ada data pembelian yang sesuai dengan filter.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--End::row-2 -->
            </div>
        </div>
        <!-- End::app-content -->
        <?php include "includes/footer.php"; // Pastikan path ini benar ?>
        
    </div>
    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->

    <!-- SCRIPTS -->
    <!-- Switch JS -->
    <script src="../assets/js/switch.js"></script> <!-- Pastikan path ini benar -->
    <!-- Popper JS -->
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script> <!-- Pastikan path ini benar -->
    <!-- Preline JS -->
    <script src="../assets/libs/preline/preline.js"></script> <!-- Pastikan path ini benar -->
    <!-- Defaultmenu JS -->
    <script src="../assets/js/defaultmenu.min.js"></script> <!-- Pastikan path ini benar -->
    <!-- Node Waves JS-->
    <script src="../assets/libs/node-waves/waves.min.js"></script> <!-- Pastikan path ini benar -->
    <!-- Sticky JS -->
    <script src="../assets/js/sticky.js"></script> <!-- Pastikan path ini benar -->
    <!-- Simplebar JS -->
    <script src="../assets/libs/simplebar/simplebar.min.js"></script> <!-- Pastikan path ini benar -->
    <script src="../assets/js/simplebar.js"></script> <!-- Pastikan path ini benar -->
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/custom.js"></script> <!-- Pastikan path ini benar -->
    <!-- Custom-Switcher JS -->
    <script src="../assets/js/custom-switcher.min.js"></script> <!-- Pastikan path ini benar -->
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for date inputs
        flatpickr("#start_date_filter", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
        });
        flatpickr("#end_date_filter", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
        });

        // Script untuk pencarian client-side pada tabel
        const searchInput = document.getElementById('search-pembelian-table');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let table = document.getElementById('purchasesTable');
                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) { // Mulai dari 1 untuk skip header (index 0)
                    let tdInvoice = tr[i].getElementsByTagName('td')[0]; 
                    let tdSupplier = tr[i].getElementsByTagName('td')[1]; 
                    
                    let textValue = "";
                    if (tdInvoice) {
                        textValue += (tdInvoice.textContent || tdInvoice.innerText).toLowerCase() + " ";
                    }
                    if (tdSupplier) {
                        textValue += (tdSupplier.textContent || tdSupplier.innerText).toLowerCase();
                    }

                    if (textValue.indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            });
        }
        
        // Hide loader once page is ready
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    });
    </script>
</body>
</html>
