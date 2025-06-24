<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

// Set default dates for filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales transactions
$transactions = $farma->getSalesTransactions($startDate, $endDate);

// Get user sales summary if requested
$userSalesSummaryData = null;
$userSalesSummary = [];
$paymentMethodHeaders = [];
if (isset($_GET['show_user_summary']) && $_GET['show_user_summary'] == 'true') {
    $userSalesSummaryData = $farma->getSalesSummaryByUser($startDate, $endDate);
    if ($userSalesSummaryData) {
        $userSalesSummary = $userSalesSummaryData['users_summary'];
        $paymentMethodHeaders = $userSalesSummaryData['payment_method_headers'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">

<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">
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
                                    <i class="ri-file-list-3-line text-2xl mb-3"></i></i> Penjualan 
                                    <div class="flex gap-1 items-center justify-center">
                                        <span class="text-sm">Dari:</span>
                                        <input id="start-date" class="ti-form-control flatpickr-date w-32" type="text" value="<?php echo htmlspecialchars($startDate); ?>" onchange="filterByDate()">
                                        <span class="text-sm">Ke:</span>
                                        <input id="end-date" class="ti-form-control flatpickr-date w-32" type="text" value="<?php echo htmlspecialchars($endDate); ?>" onchange="filterByDate()">
                                       
                                        <button 
                                            onclick="toggleUserSalesReport('<?php echo htmlspecialchars($startDate); ?>', '<?php echo htmlspecialchars($endDate); ?>')" 
                                            class="ti-btn ti-btn-info text-xs">
                                            <?php echo (isset($_GET['show_user_summary']) && $_GET['show_user_summary'] == 'true') ? 'Sembunyikan' : 'Tampilkan'; ?> Ringkasan per Kasir
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <input class="ti-form-control" type="text" placeholder="Cari No.Inv/Cust/Resep" id="search-penjualan" onkeyup="caripenjualan()">
                                </div>
                            </div>
                            <div class="box-body">
                                <!-- User Sales Summary Section -->
                                <?php if (isset($_GET['show_user_summary']) && $_GET['show_user_summary'] == 'true') : ?>
                                <div class="box mb-6 border border-gray-300 dark:border-gray-700" id="user-sales-summary-section">
                                    <div class="box-header bg-gray-100 dark:bg-gray-800 p-3">
                                        <h5 class="box-title text-base font-semibold">Ringkasan Penjualan per Kasir (<?php echo htmlspecialchars(date('d M Y', strtotime($startDate))); ?> - <?php echo htmlspecialchars(date('d M Y', strtotime($endDate))); ?>)</h5>
                                    </div>
                                    <div class="box-body p-0">
                                        <div class="table-responsive">
                                            <table class="table w-full text-sm border-collapse">
                                                <thead class="bg-gray-200 dark:bg-black/20">
                                                    <tr>
                                                        <th class="py-2 px-3 text-left">Nama Kasir</th>
                                                        <th class="py-2 px-3 text-right">Total Transaksi</th>
                                                        <th class="py-2 px-3 text-right">Total Produk Terjual</th>
                                                        <?php foreach ($paymentMethodHeaders as $header) : ?>
                                                            <th class="py-2 px-3 text-right">Total <?php echo htmlspecialchars($header['name']); ?>
                                                            </th>
                                                        <?php endforeach; ?>
                                                        <th class="py-2 px-3 text-right font-bold">Total Penjualan (Rp)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($userSalesSummary)) : ?>
                                                        <tr>
                                                            <td colspan="<?php echo 3 + count($paymentMethodHeaders) + 1; ?>" class="text-center py-3 px-3 border-t border-gray-200 dark:border-gray-700">Tidak ada data ringkasan penjualan per kasir untuk periode ini.</td>
                                                        </tr>
                                                    <?php else : ?>
                                                        <?php foreach ($userSalesSummary as $summary) : ?>
                                                            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                                <td class="py-2 px-3"><?php echo htmlspecialchars($summary['cashier_name']); ?></td>
                                                                <td class="py-2 px-3 text-center"><?php echo number_format($summary['total_transactions']); ?></td>
                                                                <td class="py-2 px-3 text-center"><?php echo number_format($summary['total_products_sold'] ?? 0); ?></td>
                                                                <?php foreach ($paymentMethodHeaders as $header) : ?>
                                                                    <td class="py-2 px-3 text-center"><?php echo number_format($summary[$header['alias_key']] ?? 0, 0, ',', '.'); ?></td>
                                                                <?php endforeach; ?>
                                                                <td class="py-2 px-3 text-center font-bold"><?php echo number_format($summary['grand_total_sales_value'], 0, ',', '.'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <!-- End User Sales Summary Section -->

                                <div class="max-h-[70vh] overflow-y-auto table-responsive">
                                    <table class="scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 table border border-defaultborder dark:border-defaultborder/10 text-nowrap">
                                        <thead class="bg-sky-400 sticky top-0">
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th class="py-3 px-4 text-white">No. Invoice</th>
                                                <th class="py-3 px-4 text-white">Tanggal</th>
                                                <th class="py-3 px-4 text-white">Customer</th>
                                                <th class="py-3 px-4 text-white">Kasir</th>
                                                <th class="py-3 px-4 text-white">No. Resep</th>
                                                <th class="py-3 px-4 text-white">Dokter</th>
                                                <th class="py-3 px-4 text-white">Metode</th>
                                                <th class="py-3 px-4 text-white">Total</th>
                                                <th class="py-3 px-4 text-white">Status</th>
                                                <th class="py-3 px-4 text-white">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="penjualan-tbody"> 
                                            <?php if (empty($transactions)) : ?>
                                                <tr>
                                                    <td colspan="10" class="text-center py-4">Tidak ada data transaksi</td>
                                                </tr>
                                            <?php else : ?>
                                                <?php foreach ($transactions as $index => $sale) : ?>
                                                <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                    <td class="py-2 px-4">
                                                        <a href="detail_penjualan.php?id=<?php echo $sale['sale_id']; ?>" class="text-blue-600 hover:underline">
                                                            <?php echo htmlspecialchars($sale['invoice_number']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="py-2 px-4"><?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
                                                    <td class="py-2 px-4"><?php echo htmlspecialchars($sale['customer_name'] ?: '-'); ?></td>
                                                    <td class="py-2 px-4"><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                                                    <td class="py-2 px-4"><?php echo htmlspecialchars($sale['prescription_number'] ?: '-'); ?></td>
                                                    <td class="py-2 px-4"><?php echo isset($sale['doctor_name']) ? htmlspecialchars($sale['doctor_name']) : '-'; ?></td>
                                                    <td class="py-2 px-4"><?php echo htmlspecialchars($sale['payment_method'] ?: '-'); ?></td>
                                                    <td class="py-2 px-4 text-right font-semibold">
                                                        <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="py-2 px-4">
                                                        <?php if ($sale['payment_status'] == 'paid') : ?>
                                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-green-900 dark:text-green-300">Lunas</span>
                                                        <?php elseif ($sale['payment_status'] == 'pending') : ?>
                                                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-yellow-900 dark:text-red-300">Hutang</span>
                                                        <?php elseif ($sale['payment_status'] == 'partially_paid') : ?>
                                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-blue-900 dark:text-blue-300">Cicil</span>
                                                        <?php else : ?>
                                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($sale['payment_status']); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-2 px-4">
                                                        <div class="flex gap-2">
                                                            <a href="detail_penjualan.php?id=<?php echo $sale['sale_id']; ?>" class="ti-btn ti-btn-icon ti-btn-sm ti-btn-light">
                                                                <i class="ri-eye-line text-lg"></i>
                                                            </a>
                                                            <a href="cetak_invoice.php?id=<?php echo $sale['sale_id']; ?>" target="_blank" class="ti-btn ti-btn-icon ti-btn-sm ti-btn-light">
                                                                <i class="ri-printer-line text-lg"></i>
                                                            </a>
                                                            <button onclick="deleteSale(<?php echo $sale['sale_id']; ?>, '<?php echo htmlspecialchars($sale['invoice_number']); ?>')" class="ti-btn ti-btn-icon ti-btn-sm ti-btn-danger">
                                                                <i class="ri-delete-bin-line text-lg"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
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
    <!-- Color Picker JS -->
    <script src="../assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>
    <!-- Date & Time Picker JS -->
    <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/custom.js"></script>
    <!-- Custom-Switcher JS -->
    <script src="../assets/js/custom-switcher.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function caripenjualan() {
            let input, filter, tbody, tr, td, i, txtValue;
            input = document.getElementById("search-penjualan");
            filter = input.value.toUpperCase();
            tbody = document.getElementById("penjualan-tbody");
            tr = tbody.getElementsByTagName("tr");
            
            for (i = 0; i < tr.length; i++) {
                if (tr[i].getElementsByTagName("td").length === 0) continue;
                
                let found = false;
                // Check relevant columns: No. Invoice (0), Customer (2), No. Resep (4)
                const colsToSearch = [0, 2, 4]; 
                for (let k = 0; k < colsToSearch.length; k++) {
                    td = tr[i].getElementsByTagName("td")[colsToSearch[k]];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
        
        function filterByDate() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            // show_user_summary parameter will be preserved if already in URL by default
            window.location.href = url.toString();
        }
        
        function deleteSale(saleId, invoiceNumber) {
            Swal.fire({
                title: 'Konfirmasi',
                text: `Anda yakin ingin menghapus transaksi dengan No. Invoice ${invoiceNumber}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Ambil parameter tanggal dan show_user_summary saat ini untuk disertakan kembali
                    const currentUrl = new URL(window.location.href);
                    const startDate = currentUrl.searchParams.get('start_date') || '<?php echo $startDate; ?>';
                    const endDate = currentUrl.searchParams.get('end_date') || '<?php echo $endDate; ?>';
                    const showUserSummary = currentUrl.searchParams.get('show_user_summary');

                    let redirectUrl = `hapus_penjualan.php?id=${saleId}&start_date=${startDate}&end_date=${endDate}`;
                    if (showUserSummary) {
                        redirectUrl += `&show_user_summary=${showUserSummary}`;
                    }
                    window.location.href = redirectUrl;
                }
            });
        }

        function toggleUserSalesReport(currentStartDate, currentEndDate) {
            const url = new URL(window.location.href);
            const showSummaryParam = url.searchParams.get('show_user_summary');

            if (showSummaryParam === 'true') {
                url.searchParams.delete('show_user_summary');
            } else {
                url.searchParams.set('show_user_summary', 'true');
            }
            // Ensure current date parameters are included
            url.searchParams.set('start_date', currentStartDate);
            url.searchParams.set('end_date', currentEndDate);
            
            window.location.href = url.toString();
        }

        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#start-date", { dateFormat: "Y-m-d", allowInput: true });
            flatpickr("#end-date", { dateFormat: "Y-m-d", allowInput: true });
        });
    </script>

</body>

</html>
