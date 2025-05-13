<?php
/**
 * cashier.php - Halaman utama kasir
 * 
 * Halaman untuk operasi POS Apotek oleh kasir
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

// Include file fungsi cashier
require_once '../functions.php';

if (!$farma->checkPersistentSession()) {
    header("Location: ../signin.php");
    exit;
}
// Cek session untuk autentikasi (implementasi sesuai sistem login Anda)
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

//$pdo = $farma->getPDO(); // Mendapatkan koneksi database
$products = $farma->getAllProducts(); // Mengambil semua produk

// Dapatkan kategori produk
$categories = $farma->getAllCategories();

// Dapatkan metode pembayaran
$paymentMethods = $farma->getActivePaymentMethods();

// Dapatkan dokter (untuk resep)
$doctors = $farma->getDoctorsForDropdown();

// Default tampilkan produk populer
$popularProducts = $farma->getPopularProductsForCashier(12);

// Penanganan pencarian produk via AJAX akan dilakukan di file terpisah
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farma Medika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-blue-600 text-white shadow-md">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-pills text-2xl mr-2"></i>
                    <h1 class="text-xl font-semibold">Farma Medika - <? echo $_SESSION["username"]?></h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative inline-block text-left"> <!-- Wrapper for positioning -->
                        <button id="view-pending-btn" class="relative bg-orange-400 hover:bg-orange-500 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                            <i class="fas fa-pause-circle mr-1"></i>
                            <span id="pending-count-badge-container" class="absolute -top-2 -right-2"> 
                                <!-- Badge will be dynamically shown/hidden here by JS -->
                            </span>
                        </button>
                        <div id="pending-transactions-list" 
                             class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden z-[60] max-h-80 overflow-y-auto">
                            <!-- Content will be injected here by JS. JS should append to this div directly. -->
                            <!-- The py-1, role, etc. divs can be removed if JS directly populates this with a list or message -->
                        </div>
                    </div>
                    <span class="hidden md:inline-block">Kasir: <span id="cashier-name"><? echo $_SESSION["username"]?></span></span>
                    <a href="../logout.php" class="bg-blue-700 hover:bg-blue-800 px-3 py-1 rounded-md inline-flex items-center text-white">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span class="hidden md:inline-block">Logout</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-grow flex flex-col md:flex-row">
            <!-- Left Panel - Products -->
            <div class="w-full md:w-2/3 p-4">
                <!-- Categories -->
                <div class="mb-4 overflow-x-auto">
                    <div class="flex space-x-2 pb-2">
                        <button class="category-btn bg-blue-600 text-white px-4 py-2 rounded-md whitespace-nowrap" data-id="0">
                            Semua
                        </button>
                        <?php foreach ($categories as $category): ?>
                            <button class="category-btn bg-white hover:bg-gray-100 px-4 py-2 rounded-md shadow whitespace-nowrap" data-id="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" id="product-search" placeholder="Cari obat atau scan barcode..." 
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="absolute right-3 top-3">
                            <button id="clear-search" class="text-gray-400 hover:text-gray-600 mr-2" style="display: none;">
                                <i class="fas fa-times fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="products-container" class="scrollspy-scrollable-parent-2 max-h-[50vh] overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($popularProducts as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-md p-3 cursor-pointer hover:shadow-lg transition" 
                             data-product='<?php echo json_encode([
                                 "product_id" => $product['product_id'],
                                 "product_name" => $product['product_name'],
                                 "kode_item" => $product['kode_item'],
                                 "posisi" => $product['posisi'],
                                 "price" => $product['price'],
                                 "unit" => $product['unit'],
                                 "stock_quantity" => $product['stock_quantity'],
                                 "minimum_stock" => $product['minimum_stock'],
                                 "requires_prescription" => $product['requires_prescription']
                             ]); ?>'>
                            <div class="h-24 bg-gray-200 rounded-md mb-2 flex items-center justify-center relative">
                                <i class="fas fa-pills text-gray-400 text-3xl"></i>
                                <?php if ($product['requires_prescription']): ?>
                                    <span class="absolute top-0 right-0 bg-yellow-500 text-white text-xs px-1 rounded">Resep</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-medium"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['kode_item']); ?></p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-blue-600 font-bold">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                <span class="text-xs text-gray-500">Stok: <?php echo $product['stock_quantity']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Panel - Cart -->
            <div class="w-full md:w-1/3 bg-white shadow-md p-4 flex flex-col">
                <!-- Customer Info -->
                <div class="mb-4">
                    <h2 class="text-lg font-semibold mb-2">Pelanggan</h2>
                    <div class="flex space-x-2">
                        <input type="text" id="customer-name" placeholder="Nama Pelanggan" 
                               class="flex-grow px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Prescription Info (hidden by default) -->
                <div id="prescription-info" class="mb-4 hidden">
                    <h2 class="text-lg font-semibold mb-2">Informasi Resep</h2>
                    <div class="space-y-2">
                        <select id="doctor-id" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="prescription-number" placeholder="Nomor Resep" 
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-grow overflow-y-auto mb-4">
                    <h2 class="text-lg font-semibold mb-2">Pesanan</h2>
                    
                    <div id="cart-items" class="space-y-2">
                        <!-- Cart items will be added here dynamically -->
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                            <p>Keranjang kosong</p>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="border-t pt-3 mb-4">
                    <div class="flex justify-between mb-1">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <!--<div class="flex justify-between mb-1">
                        <span>Pajak (0%)</span>
                        <span id="tax-amount">Rp 0</span>
                    </div>-->
                    <div class="flex justify-between mb-1">
                        <span>Diskon</span>
                        <div class="flex items-center">
                            <span>Rp&nbsp;</span>
                            <input type="tel" id="discount-amount" placeholder="0" min="0" 
                                   class="w-20 px-2 py-1 border rounded-md text-right mr-1">
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-2xl mb-3 border-t pt-2">
                        <span>Total</span>
                        <span id="grand-total">Rp 0</span>
                    </div>
                </div>
                
                <!-- Payment Section -->
                <div>
                    <div class="mb-2">
                        <h3 class="text-md font-medium">Metode Pembayaran</h3>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <?php foreach ($paymentMethods as $method): ?>
                            <button class="payment-method-btn bg-blue-100 text-blue-600 py-2 rounded-md hover:bg-blue-200" 
                                    data-id="<?php echo $method['payment_method_id']; ?>">
                                <?php if ($method['method_name'] == 'Tunai'): ?>
                                    <i class="fas fa-money-bill mr-1"></i>
                                <?php elseif ($method['method_name'] == 'Kredit / Debit'): ?>
                                    <i class="fas fa-credit-card mr-1"></i>
                                <?php elseif ($method['method_name'] == 'QRIS'): ?>
                                    <i class="fas fa-qrcode mr-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-money-check mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($method['method_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <button id="hold-transaction-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white w-full py-2 rounded-md font-medium mb-2">
    <i class="fas fa-pause-circle mr-1"></i> Tahan Transaksi
</button>

                    
                    <!-- Cash Payment Input (only shown for cash payments) -->
                    <div id="cash-payment-fields" class="mb-3 hidden">
                        <div class="mb-2">
                            <label class="block text-sm font-medium mb-1">Jumlah Dibayar (Rp)</label>
                            <div class="flex items-center border rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">
                                <span class="px-3 py-2 bg-gray-100 text-gray-500">Rp</span>
                                <input type="text" id="cash-amount" class="w-full px-3 py-2 border-0 focus:outline-none" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="block text-sm font-medium mb-1">Kembalian</label>
                            <p class="text-2xl font-bold text-green-600" id="change-amount">Rp 0</p>
                        </div>
                    </div>
                    
                    <button id="process-payment-btn" class="bg-green-600 hover:bg-green-700 text-white w-full py-3 rounded-md font-medium disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                        <i class="fas fa-check-circle mr-1"></i> Proses Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-4 text-center">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Pembayaran Berhasil</h3>
                <p class="text-gray-600 mb-4">Transaksi telah berhasil disimpan</p>
                <p class="font-medium mb-4">No. Invoice: <span id="success-invoice"></span></p>
            </div>
            <div class="p-4 border-t flex justify-center space-x-4">
                <button id="success-print" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-print mr-1"></i> Cetak Struk
                </button>
                <button id="success-new" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-plus mr-1"></i> Transaksi Baru
                </button>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        // --- Pending Transactions Variables & Initialization ---
        let pendingTransactions_inline = JSON.parse(localStorage.getItem('pendingTransactions_fm_inline')) || [];
    
        function updatePendingCount_inline() {
            const count = pendingTransactions_inline.length; // Get the count
            const badgeContainer = $('#pending-count-badge-container');
        
            if (count > 0) {
                // Create and show the badge
                badgeContainer.html(`<span id="pending-count" class="flex items-center justify-center text-xs font-bold text-white bg-red-600 !rounded-full h-5 w-5">${count}</span>`);
                // The !rounded-full is to override other Tailwind rounding if necessary.
                // Tailwind's bg-danger might be bg-red-600 or similar. Adjust if you have a custom 'danger' class.
            } else {
                // Hide or remove the badge if count is 0
                badgeContainer.empty(); // Remove the badge span
            }
        }
    
        function renderPendingTransactionsList_inline() {
    const listContainer = $('#pending-transactions-list');
    listContainer.empty(); // Clear previous content

    if (pendingTransactions_inline.length === 0) {
        listContainer.append('<div class="p-4 text-center text-sm text-gray-500">Tidak ada transaksi tertunda.</div>');
        return;
    }

    const ul = $('<ul class="divide-y divide-gray-100"></ul>'); // Using Tailwind's divide for separators
    pendingTransactions_inline.forEach((txn, index) => {
        // Using slightly more Tailwind classes for better default styling within the dropdown
        const li = $(`
            <li class="p-3 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <p class="font-medium text-gray-900">ID: ${txn.id.substring(txn.id.length - 6)} (${txn.customerName || 'N/A'})</p>
                        <p class="text-gray-500">${txn.items.length} item - Total: ${formatCurrency(txn.grandTotal || 0)}</p>
                    </div>
                    <div class="ml-2 flex-shrink-0 flex">
                        <button class="resume-pending-btn-inline p-1 inline-flex items-center justify-center text-green-500 hover:text-green-700 focus:outline-none" data-index="${index}" title="Lanjutkan">
                            <i class="fas fa-play-circle fa-lg"></i>
                        </button>
                        <button class="delete-pending-btn-inline p-1 inline-flex items-center justify-center text-red-500 hover:text-red-700 focus:outline-none" data-index="${index}" title="Hapus">
                            <i class="fas fa-trash-alt fa-lg"></i>
                        </button>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">Ditahan: ${txn.timestamp}</p>
            </li>
        `);
        ul.append(li);
    });
    listContainer.append(ul);
}
    
        // Function to clear UI elements for a new or held transaction
        // Relies on global cartItems, renderCartItems, updateCartSummary, selectedPaymentMethod from your js/cashier.js
        function clearUIForHeldOrNewTransaction_inline() {
            cartItems = []; // This is your global cartItems from js/cashier.js
            renderCartItems(); // Your global function
            
            $('#customer-name').val('');
            $('#doctor-id').val('').trigger('change'); 
            $('#prescription-number').val('');
            $('#discount-amount').val('0'); 
            
            selectedPaymentMethod = null; // Your global variable
            $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
            $('#cash-payment-fields').addClass('hidden');
            $('#cash-amount').val('');
            $('#change-amount').text(formatCurrency(0)); // Your global formatCurrency
            
            updateCartSummary(); // Your global function
        }
    
        // Initialize count and list on page load
        updatePendingCount_inline();
        if ($('#pending-transactions-list').length) {
            renderPendingTransactionsList_inline();
        }
    
    
        // --- Pending Transaction Event Handlers ---
    
        $('#hold-transaction-btn').on('click', function() {
            // cartItems is the global array from your js/cashier.js
            if (cartItems.length === 0) {
                Swal.fire('Info', 'Keranjang kosong, tidak ada yang bisa dihold.', 'info');
                return;
            }
    
            let currentSubtotal = 0; 
            cartItems.forEach(item => { currentSubtotal += item.total; });
            
            const currentDiscountString = String($('#discount-amount').val()).replace(/[^\d]/g, '');
            const currentDiscount = parseInt(currentDiscountString) || 0;
            const currentGrandTotal = currentSubtotal - currentDiscount;
    
            const transactionToHold = {
                id: 'txn_inline_' + Date.now(),
                timestamp: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short'}),
                customerName: $('#customer-name').val().trim(),
                doctorId: $('#doctor-id').val(),
                prescriptionNumber: $('#prescription-number').val().trim(),
                items: JSON.parse(JSON.stringify(cartItems)), // Deep copy of cartItems
                discount: currentDiscount,
                subtotal: currentSubtotal,
                grandTotal: currentGrandTotal
            };
    
            pendingTransactions_inline.push(transactionToHold);
            localStorage.setItem('pendingTransactions_fm_inline', JSON.stringify(pendingTransactions_inline));
            
            clearUIForHeldOrNewTransaction_inline();
            updatePendingCount_inline();
            renderPendingTransactionsList_inline(); 
    
            Swal.fire('Berhasil', 'Transaksi telah dihold.', 'success');
        });
    
        $('#view-pending-btn').on('click', function(event) {
            event.stopPropagation(); // Prevent click from closing the dropdown immediately if it's part of it
            renderPendingTransactionsList_inline(); 
            // This toggles visibility. If using absolute positioning for dropdown, this works.
            $('#pending-transactions-list').toggleClass('hidden'); 
        });
    
        // Hide pending list if clicked outside (for dropdown behavior)
        $(document).on('click', function(event) {
            if (!$('#view-pending-btn').is(event.target) && $('#view-pending-btn').has(event.target).length === 0 &&
                !$('#pending-transactions-list').is(event.target) && $('#pending-transactions-list').has(event.target).length === 0) {
                $('#pending-transactions-list').addClass('hidden');
            }
        });
    
    
        // Event delegation for dynamically added resume/delete buttons
        $('#pending-transactions-list').on('click', '.resume-pending-btn-inline', function() {
            const index = $(this).data('index');
            const transactionToResume = pendingTransactions_inline[index];
    
            // cartItems is global from your js/cashier.js
            if (cartItems.length > 0) {
                 Swal.fire({
                    title: 'Transaksi Aktif!',
                    text: "Ada item di keranjang. Tahan transaksi saat ini atau kosongkan untuk melanjutkan?",
                    icon: 'warning',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Tahan Saat Ini',
                    denyButtonText: `Kosongkan Saat Ini`,
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#eab308', 
                    denyButtonColor: '#ef4444', 
                }).then((result) => {
                    if (result.isConfirmed) { 
                        $('#hold-transaction-btn').click(); 
                        if(cartItems.length === 0) { // Check if hold was successful
                            loadHeldTransactionIntoUI_inline(transactionToResume, index);
                        } else {
                             Swal.fire('Info', 'Transaksi saat ini belum ditahan. Silakan coba lagi atau kosongkan.', 'info');
                        }
                    } else if (result.isDenied) { 
                        clearUIForHeldOrNewTransaction_inline(); 
                        loadHeldTransactionIntoUI_inline(transactionToResume, index);
                    }
                });
            } else {
                loadHeldTransactionIntoUI_inline(transactionToResume, index);
            }
        });
        
        function loadHeldTransactionIntoUI_inline(txn, originalIndex) {
            clearUIForHeldOrNewTransaction_inline(); 
    
            cartItems = JSON.parse(JSON.stringify(txn.items)); // Restore to global cartItems
            renderCartItems(); // Your global function
    
            $('#customer-name').val(txn.customerName);
            $('#doctor-id').val(txn.doctorId).trigger('change');
            $('#prescription-number').val(txn.prescriptionNumber);
            $('#discount-amount').val(txn.discount || '0');
            
            updateCartSummary(); // Your global function
    
            pendingTransactions_inline.splice(originalIndex, 1);
            localStorage.setItem('pendingTransactions_fm_inline', JSON.stringify(pendingTransactions_inline));
            updatePendingCount_inline();
            renderPendingTransactionsList_inline();
            $('#pending-transactions-list').addClass('hidden');
    
            Swal.fire('Berhasil', `Transaksi ${txn.id.substring(txn.id.length - 6)} telah dilanjutkan.`, 'success');
        }
    
        $('#pending-transactions-list').on('click', '.delete-pending-btn-inline', function() {
            const index = $(this).data('index');
            Swal.fire({
                title: 'Anda yakin?',
                text: "Transaksi tertunda ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    pendingTransactions_inline.splice(index, 1);
                    localStorage.setItem('pendingTransactions_fm_inline', JSON.stringify(pendingTransactions_inline));
                    updatePendingCount_inline();
                    renderPendingTransactionsList_inline();
                    Swal.fire('Dihapus!', 'Transaksi tertunda telah dihapus.', 'success');
                }
            });
        });
    });
    </script>
    <script src="js/cashier.js"></script>
</body>
</html>
