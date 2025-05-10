<?php
require_once '../functions.php';

// Cek role admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php");
    exit();
}

$user_id_session = $_SESSION['user_id'] ?? null; 

$form_message = '';
$form_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_operational_expense'])) {
    $expense_data = [
        'expense_date' => $_POST['expense_date'],
        'description' => trim($_POST['description']),
        'category' => trim($_POST['category']) ?: null,
        'amount' => $_POST['amount'],
        'user_id' => $user_id_session
    ];

    $result = $farma->addOperationalExpense($expense_data);
    if ($result['success']) {
        $form_message = $result['message'];
        $form_message_type = 'success';
    } else {
        $form_message = $result['message'];
        $form_message_type = 'error';
    }
}

$endDate = date('Y-m-d'); 
$startDate = $farma->getEarliestSaleDate(); 
if (!$startDate) {
    $startDate = date('Y-m-01'); 
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    if (DateTime::createFromFormat('Y-m-d', $_GET['start_date']) !== false) {
        $startDate = $_GET['start_date'];
    }
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
     if (DateTime::createFromFormat('Y-m-d', $_GET['end_date']) !== false) {
        $endDate = $_GET['end_date'];
    }
}

$historical_report = $farma->getHistoricalFinancialSummary($startDate, $endDate); 
$report_data = $historical_report['data'];
$report_error = $historical_report['error'];

$operational_expenses_details_result = $farma->getOperationalExpenseDetailsInRange($startDate, $endDate);
$operational_expenses_details_for_js = $operational_expenses_details_result['data']; 
$operational_expenses_details_error = $operational_expenses_details_result['error'];

$grand_total_revenue = 0;
$grand_total_cogs = 0;
$grand_total_purchase_payments = 0;
$grand_total_operational_expenses = 0;
$grand_total_net_profit = 0;

if (!$report_error && !empty($report_data)) {
    foreach ($report_data as $day_summary) {
        $grand_total_revenue += (float)$day_summary['daily_revenue'];
        $grand_total_cogs += (float)$day_summary['daily_cogs'];
        $grand_total_purchase_payments += (float)$day_summary['daily_purchase_payments'];
        $grand_total_operational_expenses += (float)$day_summary['daily_operational_expenses'];
        $grand_total_net_profit += (float)$day_summary['daily_net_profit'];
    }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .profit-value { @apply text-green-600 font-semibold; }
        .expense-value { @apply text-red-600 font-semibold; }
        .value-col { @apply text-right; }
        .header-col { @apply text-right; }
        .total-row td { @apply font-bold; }
        .message-success { @apply bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4; }
        .message-error { @apply bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4; }
        /* Gaya untuk modal (jika Anda tidak menggunakan Preline UI default sepenuhnya) */
        /* .modal-overlay { @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden; } */
        /* .modal-content { @apply bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl max-w-lg w-full; } */
        /* .modal-header { @apply flex justify-between items-center pb-3 border-b dark:border-gray-700; } */
        /* .modal-title { @apply text-xl font-semibold text-gray-900 dark:text-white; } */
        /* .modal-close-btn { @apply text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl font-bold cursor-pointer; } */
        /* .modal-body { @apply mt-4 max-h-80 overflow-y-auto; } */
        .operational-expense-trigger { @apply cursor-pointer text-blue-600 dark:text-blue-400 hover:underline; }
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
                <!-- Form Tambah Pengeluaran Operasional -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header"><div class="box-title">Tambah Pengeluaran Operasional Harian</div></div>
                            <div class="box-body p-6">
                                <?php if ($form_message): ?>
                                <div class="<?php echo $form_message_type === 'success' ? 'message-success' : 'message-error'; ?>" role="alert">
                                    <?php echo htmlspecialchars($form_message); ?>
                                </div>
                                <?php endif; ?>
                                <form method="POST" action="pemasukan.php<?php echo "?start_date=$startDate&end_date=$endDate"; ?>">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div><label for="expense_date" class="form-label">Tanggal Pengeluaran:</label><input type="text" class="ti-form-input flatpickr-date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                                        <div><label for="description" class="form-label">Deskripsi:</label><input type="text" class="ti-form-input" id="description" name="description" required></div>
                                        <div><label for="category" class="form-label">Kategori (Opsional):</label><input type="text" class="ti-form-input" id="category" name="category" placeholder="Contoh: Gaji, Listrik"></div>
                                        <div><label for="amount" class="form-label">Jumlah (Rp):</label><input type="tel" step="0.01" class="ti-form-input" id="amount" name="amount" required></div>
                                    </div>
                                    <div class="mt-4"><button type="submit" name="add_operational_expense" class="ti-btn ti-btn-primary">Tambah Pengeluaran</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Laporan Historis Ringkas -->
                <div class="grid grid-cols-12 gap-x-6 mt-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between items-center">
                                <div class="box-title">Ringkasan Keuangan Keseluruhan (<?php echo htmlspecialchars(date('d M Y', strtotime($startDate))); ?> - <?php echo htmlspecialchars(date('d M Y', strtotime($endDate))); ?>)</div>
                                <form method="GET" action="pemasukan.php" class="flex flex-wrap items-center gap-2">
                                    <div><label for="start_date_picker" class="form-label sr-only">Tanggal Mulai</label><input type="text" class="ti-form-input flatpickr-date w-40" id="start_date_picker" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" placeholder="Tgl Mulai"></div>
                                    <div><label for="end_date_picker" class="form-label sr-only">Tanggal Akhir</label><input type="text" class="ti-form-input flatpickr-date w-40" id="end_date_picker" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" placeholder="Tgl Akhir"></div>
                                    <button type="submit" class="ti-btn ti-btn-primary ti-btn-sm">Filter</button>
                                </form>
                            </div>
                            <div class="box-body p-6">
                                <?php if ($report_error): ?>
                                <div class="message-error" role="alert"><strong class="font-bold">Error!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($report_error); ?></span></div>
                                <?php elseif (empty($report_data)): ?>
                                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Informasi:</strong> Tidak ada data ringkasan ditemukan untuk rentang tanggal yang dipilih.</div>
                                <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                        <thead class="bg-gray-100 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Tanggal</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Pemasukan</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Modal</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Pengeluaran Pembelian</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Pengeluaran Operasional</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-700 uppercase tracking-wider">Laba Bersih</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-gray-400 dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            <?php foreach ($report_data as $day_summary): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars(date('d M Y', strtotime($day_summary['event_date']))); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white value-col">Rp <?php echo htmlspecialchars(number_format($day_summary['daily_revenue'], 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php echo htmlspecialchars(number_format($day_summary['daily_cogs'], 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php echo htmlspecialchars(number_format($day_summary['daily_purchase_payments'], 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white value-col expense-value underline">
                                                    <?php if ((float)$day_summary['daily_operational_expenses'] > 0): ?>
                                                        <span class="operational-expense-trigger" data-date="<?php echo htmlspecialchars($day_summary['event_date']); ?>" data-hs-overlay="#modal-pengeluaran">
                                                            Rp <?php echo htmlspecialchars(number_format($day_summary['daily_operational_expenses'], 0, ',', '.')); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        Rp <?php echo htmlspecialchars(number_format($day_summary['daily_operational_expenses'], 0, ',', '.')); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm value-col <?php echo ((float)$day_summary['daily_net_profit'] >= 0 ? 'profit-value' : 'expense-value'); ?>">Rp <?php echo htmlspecialchars(number_format($day_summary['daily_net_profit'], 0, ',', '.')); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="bg-gray-400 dark:bg-gray-700 total-row">
                                            <tr>
                                                <td class="px-6 py-4 text-left text-sm font-bold text-gray-700 dark:text-gray-200 uppercase">Total Pengeluaran</td>
                                                <td class="px-6 py-4 text-left text-sm text-gray-900 dark:text-white value-col">Rp <?php echo htmlspecialchars(number_format($grand_total_revenue, 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 text-left text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php echo htmlspecialchars(number_format($grand_total_cogs, 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 text-left text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php echo htmlspecialchars(number_format($grand_total_purchase_payments, 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 text-left text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php echo htmlspecialchars(number_format($grand_total_operational_expenses, 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 text-left text-sm value-col <?php echo ($grand_total_net_profit >= 0 ? 'profit-value' : 'expense-value'); ?>">Rp <?php echo htmlspecialchars(number_format($grand_total_net_profit, 0, ',', '.')); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Rincian Pengeluaran Operasional (Tabel Bawah) - Ini bisa Anda HAPUS jika tidak diperlukan lagi setelah modal berfungsi -->
                 <!--<div class="grid grid-cols-12 gap-x-6 mt-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header"><div class="box-title">Rincian Pengeluaran Operasional (<?php //echo htmlspecialchars(date('d M Y', strtotime($startDate))); ?> - <?php //echo htmlspecialchars(date('d M Y', strtotime($endDate))); ?>)</div></div>
                            <div class="box-body p-6">
                                <?php //if ($operational_expenses_details_error): ?>
                                <div class="message-error" role="alert"><strong class="font-bold">Error!</strong> <span class="block sm:inline"><?php //echo htmlspecialchars($operational_expenses_details_error); ?></span></div>
                                <?php //elseif (empty($operational_expenses_details_for_js)): ?>
                                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Informasi:</strong> Tidak ada rincian pengeluaran operasional ditemukan untuk rentang tanggal yang dipilih.</div>
                                <?php //else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                        <thead class="bg-gray-100 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                                <th class="px-6 py-3 header-col text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dicatat Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            <?php //foreach ($operational_expenses_details_for_js as $expense): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php //echo htmlspecialchars(date('d M Y', strtotime($expense['expense_date']))); ?></td>
                                                <td class="px-6 py-4 whitespace-normal text-sm text-gray-900 dark:text-white max-w-xs break-words"><?php //echo htmlspecialchars($expense['description']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php //echo htmlspecialchars($expense['category'] ?: '-'); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white value-col expense-value">Rp <?php //echo htmlspecialchars(number_format($expense['amount'], 0, ',', '.')); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php //echo htmlspecialchars($expense['recorded_by_user'] ?: 'Sistem'); ?></td>
                                            </tr>
                                            <?php //endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php //endif; ?>
                            </div>
                        </div>
                    </div>
                </div>-->

            </div>
        </div>
        <?php include "includes/footer.php";?>
    </div>

    <!-- Modal Anda - PINDAHKAN KE SINI, HANYA SATU KALI DI LUAR LOOP -->
    <div id="modal-pengeluaran" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:!mt-7 !mt-14 ease-out ti-modal-box">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title" id="dynamicModalTitle">
                        Rincian Pengeluaran
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn"
                        data-hs-overlay="#modal-pengeluaran">
                        <span class="sr-only">Close</span>
                        <svg class="w-3.5 h-3.5" width="8" height="8" viewBox="0 0 8 8" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052 0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062 1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672 7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907 7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471 4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z"
                                fill="currentColor"></path>
                        </svg>
                    </button>
                </div>
                <div class="ti-modal-body" id="dynamicModalBody">
                    <!-- Konten tabel rincian akan dimasukkan di sini oleh JavaScript -->
                </div>
                <div class="ti-modal-footer">
                    <button type="button" class="hs-dropdown-toggle ti-btn btn-wave ti-btn-secondary"
                        data-hs-overlay="#modal-pengeluaran">
                        Close
                    </button>
                </div>
            </div>
        </div>
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
    <script>
    const allOperationalExpensesForModal = <?php echo json_encode($operational_expenses_details_for_js ?? []); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        flatpickr(".flatpickr-date", {
            dateFormat: "Y-m-d", // Pastikan format konsisten
            allowInput: true
        });

        const expenseTriggers = document.querySelectorAll('.operational-expense-trigger');
        const modalTitleElement = document.getElementById('dynamicModalTitle');
        const modalBodyElement = document.getElementById('dynamicModalBody');

        function formatNumber(num) {
            return 'Rp ' + parseFloat(num).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
        
        function formatDateForTitle(dateString) {
            // Asumsikan dateString adalah 'YYYY-MM-DD'
            const parts = dateString.split('-');
            if (parts.length === 3) {
                const date = new Date(parts[0], parts[1] - 1, parts[2]); // Bulan adalah 0-indexed
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return date.toLocaleDateString('id-ID', options);
            }
            return dateString; // Fallback jika format tidak sesuai
        }

        function escapeHtml(unsafe) {
            if (unsafe === null || typeof unsafe === 'undefined') {
                return '';
            }
            return unsafe
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        expenseTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(event) {
                // event.stopPropagation(); // Mungkin diperlukan jika event bubbling dari td mengganggu
                
                const clickedDate = this.getAttribute('data-date'); // 'this' merujuk ke span yang diklik
                
                const expensesForDate = allOperationalExpensesForModal.filter(expense => expense.expense_date === clickedDate);
                
                if (modalTitleElement) {
                    modalTitleElement.textContent = 'Rincian Pengeluaran - ' + formatDateForTitle(clickedDate);
                }
                
                if (modalBodyElement) {
                    if (expensesForDate.length > 0) {
                        let tableHtml = `
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                    <thead class="bg-gray-100 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">`;
                        expensesForDate.forEach(expense => {
                            tableHtml += `
                                        <tr>
                                            <td class="px-4 py-2 whitespace-normal text-sm text-gray-700 dark:text-gray-300">${escapeHtml(expense.description)}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${escapeHtml(expense.category || '-')}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 text-right">${formatNumber(expense.amount)}</td>
                                        </tr>`;
                        });
                        tableHtml += `
                                    </tbody>
                                </table>
                            </div>`;
                        modalBodyElement.innerHTML = tableHtml;
                    } else {
                        modalBodyElement.innerHTML = '<p class="text-gray-700 dark:text-gray-300">Tidak ada rincian pengeluaran operasional untuk tanggal ini.</p>';
                    }
                }
                // Preline UI akan menangani pembukaan modal karena data-hs-overlay ada di span.
            });
        });
    });
    </script>
</body>
</html>
