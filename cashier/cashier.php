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

$products = $farma->getAllProducts(); 
$categories = $farma->getAllCategories();
$paymentMethods = $farma->getActivePaymentMethods();
$doctors = $farma->getDoctorsForDropdown();
$popularProducts = $farma->getPopularProductsForCashier(12);

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
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Tambahan style untuk memastikan sticky top bekerja dengan baik jika ada elemen lain */
        body {
            /* Jika header utama Anda memiliki tinggi tetap dan tidak sticky, 
               Anda mungkin tidak memerlukan padding-top di body.
               Jika header utama sticky, Anda perlu padding-top di body 
               agar konten tidak tertutup header.
               Untuk sticky total bar di bawah header non-sticky, ini tidak diperlukan.
            */
        }
        #sticky-total-price-header {
            /* top: 0; /* Default jika ingin di paling atas viewport */
            /* Jika header utama Anda memiliki tinggi Xpx dan tidak sticky, 
               dan Anda ingin bar total ini muncul di bawahnya saat scroll,
               Anda mungkin perlu menyesuaikan top atau cara positioningnya.
               Untuk saat ini, kita buat dia sticky di paling atas.
            */
            /* Z-index harus lebih tinggi dari konten lain tapi mungkin di bawah modal */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header Utama -->
        <header class="bg-blue-600 text-white shadow-md">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-pills text-2xl mr-2"></i>
                    <h1 class="text-xl font-semibold">Farma Medika - <? echo $_SESSION["username"]?></h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative inline-block text-left">
                        <button id="view-pending-btn" class="relative bg-orange-400 hover:bg-orange-500 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                            <i class="fas fa-pause-circle mr-1"></i>
                            <span id="pending-count-badge-container" class="absolute -top-2 -right-2"> 
                            </span>
                        </button>
                        <div id="pending-transactions-list" 
                             class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden z-[60] max-h-80 overflow-y-auto">
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

        <!-- Sticky Total Price Header (BARU) -->
        <div id="sticky-total-price-header" class="sticky top-0 bg-blue-100 shadow-xl z-40 p-4">
            <!-- TOTAL -->
            <div class="flex justify-between items-center border-b border-blue-200 pb-2 mb-3"> <!-- Optional: Ganti warna border agar kontras -->
                <span class="text-3xl font-bold text-blue-700">TOTAL :</span>
                <span id="sticky-grand-total-display" class="text-3xl font-bold text-blue-800">Rp 0</span>
            </div>
        
            <!-- METODE PEMBAYARAN -->
            <div class="grid grid-cols-4 gap-4 mb-4 items-center">
                <?php foreach ($paymentMethods as $method): ?>
                    <button class="payment-method-btn bg-white hover:bg-blue-400 text-blue-600 py-2 rounded-md shadow-sm" 
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
                <div class="flex items-center col-span-1">
                    <span class="mr-1 text-gray-700">Rp</span> <!-- Optional: Sesuaikan warna teks jika perlu -->
                    <input type="tel" id="discount-amount" placeholder="Diskon (ex. 2000)" min="0"
                        class="w-full px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        
            <!-- INPUT TUNAI & KEMBALIAN -->
            <div id="cash-payment-fields" class="mb-4 hidden">
                <div class="flex flex-col md:flex-row md:gap-4"> 
                    <div class="mb-2 md:mb-0 md:w-1/2"> 
                        <label for="cash-amount" class="block text-sm font-medium mb-1 text-gray-700">Jumlah Dibayar</label> <!-- Optional: Sesuaikan warna teks -->
                        <div class="flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 bg-white">
                            <span class="px-3 py-2 bg-gray-100 text-gray-500">Rp</span>
                            <input type="tel" id="cash-amount" class="w-full px-3 py-2 border-0 focus:outline-none" placeholder="0">
                        </div>
                    </div>
                    <div class="md:w-1/2"> 
                        <label class="block text-sm font-medium mb-1 text-gray-700">Kembalian</label> <!-- Optional: Sesuaikan warna teks -->
                        <p class="text-2xl font-bold text-green-600" id="change-amount">Rp 0</p>
                    </div>
                </div>
            </div>
        
            <!-- BUTTONS -->
            <div class="flex gap-4">
                <button id="hold-transaction-btn" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-md font-medium">
                    <i class="fas fa-pause-circle mr-1"></i> Tahan Transaksi
                </button>
                <button id="process-payment-btn" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-md font-medium disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                    <i class="fas fa-check-circle mr-1"></i> Proses Pembayaran
                </button>
            </div>
        </div>




        <!-- Main Content -->
        <div class="flex-grow flex flex-col md:flex-row pt-0"> 
            <div class="w-full md:w-1/2 p-4">
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

                
                <div id="products-container" class="max-h-[calc(100vh-350px)] md:max-h-[calc(100vh-350px)] overflow-y-auto grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4"> 
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

            
            <div class="w-full lg:w-1/1 bg-white shadow-md p-4 flex flex-col max-h-[calc(100vh-var(--header-height,60px)-var(--sticky-total-height,60px))]"> 
                <div class="mb-4">
                    <h2 class="text-lg font-semibold mb-2">Pelanggan</h2>
                    <div class="flex space-x-2">
                        <input type="text" id="customer-name" placeholder="Nama Pelanggan" 
                               class="flex-grow px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                
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

                
                <div class="flex-grow overflow-y-auto mb-4"> 
                    <h2 class="text-lg font-semibold mb-2">Pesanan</h2>
                    
                    <div id="cart-items" class="space-y-2">
                        
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                            <p>Keranjang kosong</p>
                        </div>
                    </div>
                </div>

              
                <div class="border-t pt-3 mb-4 hidden">
                    <div class="flex justify-between font-bold text-2xl mb-3 border-t pt-2">
                        <span>Total</span>
                        <span id="grand-total">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        
    </div>

    <script>
    $(document).ready(function() {
        // --- Bagian untuk Sticky Total Header ---
        const stickyTotalDisplay = $('#sticky-grand-total-display');
        const originalGrandTotalDisplay = $('#grand-total'); // Elemen yang diupdate oleh cashier.js

        // Fungsi untuk mengupdate sticky total
        /*function updateStickyTotal() {
            stickyTotalDisplay.text(originalGrandTotalDisplay.text());
        }*/
        
        function updateStickyTotal() {
            const value = originalGrandTotalDisplay.text().trim();
            stickyTotalDisplay.text(value);
        }


        // Panggil sekali saat load untuk inisialisasi
        updateStickyTotal();

        // Gunakan MutationObserver untuk memantau perubahan pada #grand-total
        // Ini akan bereaksi terhadap perubahan yang dilakukan oleh cashier.js
        const observer = new MutationObserver(function(mutationsList, observer) {
            // Untuk setiap mutasi (perubahan)
            for(const mutation of mutationsList) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    updateStickyTotal();
                    break; // Cukup satu update per batch mutasi
                }
            }
        });

        // Mulai mengamati target node untuk konfigurasi mutasi yang ditentukan
        if (originalGrandTotalDisplay.length) { // Pastikan elemennya ada
            observer.observe(originalGrandTotalDisplay[0], { 
                attributes: false, // tidak perlu memantau atribut
                childList: true,   // pantau perubahan pada anak-anak (termasuk teks)
                subtree: true,     // pantau perubahan pada semua turunan
                characterData: true // pantau perubahan pada data karakter (teks)
            });
        }
        // --- Akhir Bagian Sticky Total Header ---


        // --- Pending Transactions Variables & Initialization (kode Anda yang sudah ada) ---
        let pendingTransactions_inline = JSON.parse(localStorage.getItem('pendingTransactions_fm_inline')) || [];
    
        function updatePendingCount_inline() {
            const count = pendingTransactions_inline.length; 
            const badgeContainer = $('#pending-count-badge-container');
        
            if (count > 0) {
                badgeContainer.html(`<span id="pending-count" class="flex items-center justify-center text-xs font-bold text-white bg-red-600 !rounded-full h-5 w-5">${count}</span>`);
            } else {
                badgeContainer.empty(); 
            }
        }
    
        function renderPendingTransactionsList_inline() {
            const listContainer = $('#pending-transactions-list');
            listContainer.empty(); 

            if (pendingTransactions_inline.length === 0) {
                listContainer.append('<div class="p-4 text-center text-sm text-gray-500">Tidak ada transaksi tertunda.</div>');
                return;
            }

            const ul = $('<ul class="divide-y divide-gray-100"></ul>'); 
            pendingTransactions_inline.forEach((txn, index) => {
                const li = $(`
                    <li class="p-3 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm">
                                <p class="font-medium text-gray-900">ID: ${txn.id.substring(txn.id.length - 6)} (${txn.customerName || 'N/A'})</p>
                                <p class="text-gray-500">${txn.items.length} item - Total: ${typeof formatCurrency === 'function' ? formatCurrency(txn.grandTotal || 0) : 'Rp ' + (txn.grandTotal || 0)}</p>
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
            
        function clearUIForHeldOrNewTransaction_inline() {
            if (typeof cartItems !== 'undefined') cartItems = []; 
            if (typeof renderCartItems === 'function') renderCartItems(); 
            
            $('#customer-name').val('');
            $('#doctor-id').val('').trigger('change'); 
            $('#prescription-number').val('');
            $('#discount-amount').val('0'); 
            
            if (typeof selectedPaymentMethod !== 'undefined') selectedPaymentMethod = null; 
            $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
            $('#cash-payment-fields').addClass('hidden');
            $('#cash-amount').val('');
            $('#change-amount').text(typeof formatCurrency === 'function' ? formatCurrency(0) : 'Rp 0'); 
            
            if (typeof updateCartSummary === 'function') updateCartSummary(); 
        }
        
        updatePendingCount_inline();
        if ($('#pending-transactions-list').length) {
            renderPendingTransactionsList_inline();
        }
        
        $('#hold-transaction-btn').on('click', function() {
            if (typeof cartItems === 'undefined' || cartItems.length === 0) {
                Swal.fire('Info', 'Keranjang kosong, tidak ada yang bisa dihold.', 'info');
                return;
            }
        
            let currentSubtotal = 0; 
            if (typeof cartItems !== 'undefined') {
                cartItems.forEach(item => { currentSubtotal += item.total; });
            }
            
            const currentDiscountString = String($('#discount-amount').val()).replace(/[^\d]/g, '');
            const currentDiscount = parseInt(currentDiscountString) || 0;
            const currentGrandTotal = currentSubtotal - currentDiscount;
        
            const transactionToHold = {
                id: 'txn_inline_' + Date.now(),
                timestamp: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short'}),
                customerName: $('#customer-name').val().trim(),
                doctorId: $('#doctor-id').val(),
                prescriptionNumber: $('#prescription-number').val().trim(),
                items: typeof cartItems !== 'undefined' ? JSON.parse(JSON.stringify(cartItems)) : [], 
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
            event.stopPropagation(); 
            renderPendingTransactionsList_inline(); 
            $('#pending-transactions-list').toggleClass('hidden'); 
        });
    
        $(document).on('click', function(event) {
            if (!$('#view-pending-btn').is(event.target) && $('#view-pending-btn').has(event.target).length === 0 &&
                !$('#pending-transactions-list').is(event.target) && $('#pending-transactions-list').has(event.target).length === 0) {
                $('#pending-transactions-list').addClass('hidden');
            }
        });
            
        $('#pending-transactions-list').on('click', '.resume-pending-btn-inline', function() {
            const index = $(this).data('index');
            const transactionToResume = pendingTransactions_inline[index];
        
            if (typeof cartItems !== 'undefined' && cartItems.length > 0) {
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
                        if(typeof cartItems === 'undefined' || cartItems.length === 0) { 
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
        
            if (typeof cartItems !== 'undefined') cartItems = JSON.parse(JSON.stringify(txn.items)); 
            if (typeof renderCartItems === 'function') renderCartItems(); 
        
            $('#customer-name').val(txn.customerName);
            $('#doctor-id').val(txn.doctorId).trigger('change');
            $('#prescription-number').val(txn.prescriptionNumber);
            $('#discount-amount').val(txn.discount || '0');
            
            if (typeof updateCartSummary === 'function') updateCartSummary(); 
        
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
    <script src="js/cashier.js"></script> {/* Pastikan cashier.js di-load setelah inline script */}
</body>
</html>
