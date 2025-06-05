<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}


// Set default dates for filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to first day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today

// Get sales transactions
$transactions = $farma->getSalesTransactions($startDate, $endDate);
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
                                    <div class="flex gap-1 items-center">
                                        <span class="text-sm">Dari:</span>
                                        <input id="start-date" class="ti-form-control flatpickr-date" type="text" value="<?php echo $startDate; ?>" onchange="filterByDate()">
                                        <span class="text-sm">Ke:</span>
                                        <input id="end-date" class="ti-form-control flatpickr-date" type="text" value="<?php echo $endDate; ?>" onchange="filterByDate()">
                                       
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <input class="ti-form-control" type="text" placeholder="Cari" id="search-penjualan" onkeyup="caripenjualan()">
                                </div>
                            </div>
                            <div class="box-body">
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
                                                            <?php echo $sale['invoice_number']; ?>
                                                        </a>
                                                    </td>
                                                    <td class="py-2 px-4"><?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
                                                    <td class="py-2 px-4"><?php echo $sale['customer_name'] ?: '-'; ?></td>
                                                    <td class="py-2 px-4"><?php echo $sale['cashier_name']; ?></td>
                                                    <td class="py-2 px-4"><?php echo $sale['prescription_number'] ?: '-'; ?></td>
                                                    <td class="py-2 px-4"><?php echo isset($sale['doctor_name']) ? $sale['doctor_name'] : '-'; ?></td>
                                                    <td class="py-2 px-4"><?php echo $sale['payment_method'] ?: '-'; ?></td>
                                                    <td class="py-2 px-4 text-right font-semibold">
                                                        <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="py-2 px-4">
                                                        <?php if ($sale['payment_status'] == 'paid') : ?>
                                                            <span class="bg-green-400 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Lunas</span>
                                                        <?php elseif ($sale['payment_status'] == 'pending') : ?>
                                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">Pending</span>
                                                        <?php elseif ($sale['payment_status'] == 'partially_paid') : ?>
                                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">Sebagian</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-2 px-4">
                                                        <div class="flex gap-2">
                                                            <a href="detail_penjualan.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-light">
                                                                <i class="ri-eye-line text-lg text-blue-600"></i>
                                                            </a>
                                                            <a href="cetak_invoice.php?id=<?php echo $sale['sale_id']; ?>" target="_blank" class="btn btn-sm btn-light">
                                                                <i class="ri-printer-line text-lg text-green-600"></i>
                                                            </a>
                                                            
                                                            <button onclick="deleteSale(<?php echo $sale['sale_id']; ?>, '<?php echo $sale['invoice_number']; ?>')" class="btn btn-sm btn-danger">
                                                                <i class="ri-delete-bin-line text-lg text-red-600"></i>
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
    
    <script src="../assets/js/autoformatexpire.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function caripenjualan() {
            let input, filter, tbody, tr, td, i, txtValue;
            input = document.getElementById("search-penjualan");
            filter = input.value.toUpperCase();
            tbody = document.getElementById("penjualan-tbody");
            tr = tbody.getElementsByTagName("tr");
            
            // Loop through all table rows, and hide those who don't match the search query
            for (i = 0; i < tr.length; i++) {
                // Check if there are cells in this row (avoid errors on empty tables)
                if (tr[i].getElementsByTagName("td").length === 0) continue;
                
                let found = false;
                // Check multiple columns for matches (invoice, customer name, prescription)
                for (let j = 0; j < 5; j++) { // Check first 5 columns
                    td = tr[i].getElementsByTagName("td")[j];
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
            window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
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
                    window.location.href = `hapus_penjualan.php?id=${saleId}`;
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
    flatpickr("#start-date", { dateFormat: "Y-m-d", allowInput: true });
    flatpickr("#end-date", { dateFormat: "Y-m-d", allowInput: true });
});
    </script>

</body>

</html>
