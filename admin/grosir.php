<?php
require_once '../functions.php'; // Path to your functions.php

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php");
    exit();
}

$pdo = $farma->getPDO();

// Ambil data yang diperlukan untuk form
$products = $farma->getAllProductsForDropdown(); // Still useful if you want to initially load something or for reference
$payment_methods = $farma->getActivePaymentMethods();
//$doctors = $farma->getDoctorsForDropdown(); // Jika diperlukan untuk memilih dokter

?>
<!DOCTYPE html>
<html lang="id" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php"; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Style kustom untuk pencarian produk non-select2 */
        .product-search-container {
            position: relative;
        }
        #product-search {
            width: 100%;
            padding-right: 30px; /* Space for a potential clear button or icon */
        }
        #product-suggestions {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            list-style: none;
            padding: 0;
            margin: 0;
            display: none; /* Hidden by default */
        }
        #product-suggestions li {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        #product-suggestions li:last-child {
            border-bottom: none;
        }
        #product-suggestions li:hover, #product-suggestions li.active {
            background-color: #f0f4f7;
        }
        .item-table input {
            min-width: 80px;
        }
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
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <form id="wholesale-form">
                            <div class="box">
                                <div class="box-header justify-between">
                                    <div class="box-title">
                                        <i class="ri-shopping-cart-2-fill text-2xl"></i> Penjualan Grosir (Hanya di lakukan oleh Admin)
                                    </div>
                                </div>
                                <div class="box-body">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                        <div>
                                            <label for="customer_name" class="form-label">Nama Pelanggan</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Nama Pelanggan" required>
                                        </div>
                                        <div>
                                            <label for="sale_date" class="form-label">Tanggal Transaksi</label>
                                            <input type="text" class="form-control" id="sale_date" name="sale_date" required>
                                        </div>
                                        
                                        <div>
                                            <label for="payment_status" class="form-label">Status Pembayaran</label>
                                            <select class="form-control" id="payment_status" name="payment_status" required>
                                                <option value="lunas">Lunas</option>
                                                <option value="hutang">Hutang</option>
                                                <option value="cicil">Bayar Sebagian</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="notes" class="form-label">Catatan</label>
                                            <input type="text" class="form-control" id="notes" name="notes" placeholder="Catatan (jika ada)">
                                        </div>
                                    </div>

                                    <div class="mb-4 product-search-container">
                                        <label for="product-search" class="form-label">Cari dan Tambah Produk (Barcode atau Ketik Nama)</label>
                                        <input type="text" class="form-control" id="product-search" placeholder="Scan Barcode atau Ketik Nama Produk..." autofocus>
                                        <ul id="product-suggestions">
                                            </ul>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table-auto w-full text-left item-table">
                                            <thead>
                                                <tr class="bg-gray-100">
                                                    <th class="p-2">Produk</th>
                                                    <th class="p-2">Stok</th>
                                                    <th class="p-2">Kuantitas</th>
                                                    <th class="p-2">Harga Satuan</th>
                                                    <th class="p-2">Subtotal</th>
                                                    <th class="p-2">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cart-items">
                                                </tbody>
                                            <tfoot>
                                                <!--<tr class="border-t-2 font-bold">
                                                    <td colspan="4" class="text-right p-2">Subtotal</td>
                                                    <td id="cart-subtotal" class="text-right p-2">Rp 0</td>
                                                    <td></td>
                                                </tr>-->
                                                <tr class="border-t-2 font-bold text-xl">
                                                    <td colspan="4" class="text-right p-2">Total</td>
                                                    <td id="cart-total" class="text-right p-2">Rp 0</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-6 text-right">
                                        <button type="button" class="ti-btn ti-btn-danger" id="reset-cart">Reset</button>
                                        <button type="submit" class="ti-btn ti-btn-primary">Simpan Transaksi</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div> <?php include "includes/footer.php";?>
    </div> <div class="scrollToTop">
        <span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span>
    </div>
    <div id="responsive-overlay"></div>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    $(document).ready(function() {
        // Inisialisasi Flatpickr
        flatpickr("#sale_date", {
            enableTime: false,
            dateFormat: "Y-m-d",
            defaultDate: "today",
            locale: "id"
        });

        // Helper untuk format Rupiah
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka);
        }

        let searchTimeout;
        let lastInputValue = '';
        let isProcessingBarcode = false;
        let selectedSuggestionIndex = -1;
        const suggestionsList = $('#product-suggestions');
        const productSearchInput = $('#product-search');

        // Function to fetch product suggestions
        function fetchProductSuggestions(keyword) {
            if (keyword.length < 2) { // Minimal 2 karakter untuk pencarian
                suggestionsList.empty().hide();
                return;
            }

            $.ajax({
                url: 'find_product.php', // API endpoint untuk mencari produk
                type: 'GET',
                dataType: 'json',
                data: { keyword: keyword },
                success: function(data) {
                    suggestionsList.empty();
                    if (data.length > 0) {
                        data.forEach((product, index) => {
                            
                            const listItem = $(`<li
                                data-id="${product.id}"
                                data-product-name="${product.product_name}"
                                data-kode-item="${product.kode_item}"
                                data-stock="${product.stock_quantity}"
                                data-price="${product.price}"
                                data-unit="${product.unit}">
                                ${product.product_name} (${product.kode_item}) - Stok: ${product.stock_quantity} ${product.unit}
                            </li>`);
                            listItem.on('click', function() {
                                handleProductSelection($(this).data());
                            });
                            suggestionsList.append(listItem);
                        });
                        suggestionsList.show();
                        selectedSuggestionIndex = -1; // Reset selection
                    } else {
                        suggestionsList.hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching product suggestions:", status, error);
                    suggestionsList.empty().hide();
                }
            });
        }

        // Function to handle product selection (from suggestion or barcode)
        function handleProductSelection(productData) {
            if ($(`#row-${productData.id}`).length > 0) {
                Swal.fire('Info', 'Produk sudah ada di keranjang.', 'info');
                return;
            }

            /*const cartRow = `
                <tr id="row-${productData.id}" data-id="${productData.id}" class="item-row border-b">
                    <td class="p-2">${productData.product_name} (${productData.kode_item})</td>
                    <td class="p-2 stock-available">${productData.stock_quantity} ${productData.unit}</td>
                    <td class="p-2"><input type="number" class="form-control form-control-sm item-qty" value="1" min="1" max="${productData.stock_quantity}"></td>
                    <td class="p-2"><input type="number" class="form-control form-control-sm item-price" value="${productData.price}" min="0.01"></td>
                    <td class="p-2 item-subtotal text-right">${formatRupiah(productData.price)}</td>
                    <td class="p-2"><button type="button" class="ti-btn ti-btn-icon ti-btn-danger-full remove-item"><i class="ri-delete-bin-line"></i></button></td>
                </tr>
            `;*/
            const cartRow = `
                <tr id="row-${productData.id}" data-id="${productData.id}" class="item-row border-b">
                    
                    <td class="p-2">${productData.productName} (${productData.kodeItem})</td>
                    <td class="p-2 stock-available">${productData.stock} ${productData.unit}</td>
                    <td class="p-2"><input type="number" class="form-control form-control-sm item-qty" value="1" min="1" max="${productData.stock}"></td>
                    <td class="p-2"><input type="tel" class="form-control form-control-sm item-price" value="${productData.price}" min="0.01"></td>
                    <td class="p-2 item-subtotal">${formatRupiah(productData.price)}</td>
                    <td class="p-2"><button type="button" class="ti-btn ti-btn-icon ti-btn-danger-full remove-item"><i class="ri-delete-bin-line"></i></button></td>
                </tr>
            `;
            $('#cart-items').append(cartRow);
            updateTotals();
            productSearchInput.val(''); // Clear input after adding
            suggestionsList.empty().hide(); // Hide suggestions
        }

        // Event listener for product search input
        productSearchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const keyword = $(this).val();

            if (keyword === lastInputValue && !isProcessingBarcode) { // Prevent redundant calls
                return;
            }
            lastInputValue = keyword;

            // Heuristic for barcode: rapid input change and specific length/pattern
            // Adjust sensitivity if needed
            if (keyword.length > 5 && (Date.now() - $(this).data('lastTypingTime') < 100)) { // Fast typing = potential barcode
                isProcessingBarcode = true;
                // Attempt to add as barcode directly
                $.ajax({
                    url: 'find_product.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { keyword: keyword },
                    success: function(data) {
                        if (data.length === 1 && data[0].barcode === keyword) { // Exact barcode match
                            handleProductSelection(data[0]);
                        } else {
                            // If not an exact barcode match, proceed with suggestions
                            fetchProductSuggestions(keyword);
                        }
                    },
                    complete: function() {
                        isProcessingBarcode = false;
                        productSearchInput.data('lastTypingTime', Date.now()); // Reset time
                    }
                });
            } else { // Manual typing, show suggestions after a delay
                searchTimeout = setTimeout(() => {
                    isProcessingBarcode = false; // Ensure barcode flag is off for manual typing
                    fetchProductSuggestions(keyword);
                }, 300); // Debounce for 300ms
            }
            $(this).data('lastTypingTime', Date.now());
        });

        productSearchInput.on('keydown', function(e) {
            const suggestions = suggestionsList.find('li');
            if (suggestions.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedSuggestionIndex = (selectedSuggestionIndex + 1) % suggestions.length;
                suggestions.removeClass('active');
                suggestions.eq(selectedSuggestionIndex).addClass('active');
                suggestionsList[0].scrollTop = suggestions.eq(selectedSuggestionIndex).position().top;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedSuggestionIndex = (selectedSuggestionIndex - 1 + suggestions.length) % suggestions.length;
                suggestions.removeClass('active');
                suggestions.eq(selectedSuggestionIndex).addClass('active');
                suggestionsList[0].scrollTop = suggestions.eq(selectedSuggestionIndex).position().top;
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedSuggestionIndex !== -1) {
                    suggestions.eq(selectedSuggestionIndex).click(); // Simulate click on selected item
                } else {
                    // If no suggestion selected, perform a direct search for the current input value
                    $.ajax({
                        url: 'find_product.php',
                        type: 'GET',
                        dataType: 'json',
                        data: { keyword: productSearchInput.val() },
                        success: function(data) {
                            if (data.length === 1) { // If exactly one match, add it
                                handleProductSelection(data[0]);
                            } else if (data.length > 1) {
                                Swal.fire('Info', 'Beberapa produk ditemukan. Harap pilih dari daftar saran.', 'info');
                                // Optionally, keep suggestions open and focus first item
                                suggestionsList.empty();
                                data.forEach((product, index) => {
                                    const listItem = $(`<li data-id="${product.product_id}" data-product-name="${product.product_name}" data-stock="${product.stock_quantity}" data-price="${product.price}" data-unit="${product.unit}">${product.product_name} (${product.kode_item}) - Stok: ${product.stock_quantity} ${product.unit}</li>`);
                                    listItem.on('click', function() {
                                        handleProductSelection($(this).data());
                                    });
                                    suggestionsList.append(listItem);
                                });
                                suggestionsList.show();
                                selectedSuggestionIndex = -1; // Reset selection
                            } else {
                                Swal.fire('Info', 'Produk tidak ditemukan.', 'info');
                            }
                        }
                    });
                }
            }
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.product-search-container').length) {
                suggestionsList.hide();
            }
        });


        // Event listener for update quantity, price
        $('#cart-items').on('input', '.item-qty, .item-price', function() {
            updateTotals();
        });

        // Hapus item dari keranjang
        $('#cart-items').on('click', '.remove-item', function() {
            $(this).closest('tr').remove();
            updateTotals();
        });
        
        // Reset keranjang
        $('#reset-cart').on('click', function() {
            Swal.fire({
                title: 'Anda yakin?',
                text: "Keranjang akan dikosongkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, kosongkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#cart-items').empty();
                    updateTotals();
                    Swal.fire('Dikosongkan!', 'Keranjang belanja telah dikosongkan.', 'success');
                }
            })
        });

        // Fungsi untuk menghitung ulang total
        function updateTotals() {
            let subtotal = 0;
            $('.item-row').each(function() {
                const row = $(this);
                const qty = parseFloat(row.find('.item-qty').val()) || 0;
                let price = parseFloat(row.find('.item-price').val()) || 0;

                // Validate price: cannot be zero
                if (price <= 0) {
                    row.find('.item-price').addClass('border-red-500'); // Add a visual cue
                    // Optionally, you can set a default price or prevent calculation
                    // For now, we'll just alert and continue with 0, but ideally, prevent submit
                    // Swal.fire('Peringatan', 'Harga satuan tidak boleh nol atau negatif.', 'warning');
                    // price = 0; // Or keep its value to show the user it's invalid
                } else {
                    row.find('.item-price').removeClass('border-red-500');
                }

                const itemTotal = qty * price; // No discount per item
                row.find('.item-subtotal').text(formatRupiah(itemTotal));
                subtotal += itemTotal;
            });

            $('#cart-subtotal').text(formatRupiah(subtotal));
            $('#cart-total').text(formatRupiah(subtotal)); // Grand total is just subtotal without tax/global discount
        }

        // Submit form
        $('#wholesale-form').on('submit', function(e) {
            e.preventDefault();

            const items = [];
            let hasZeroPrice = false;
            $('.item-row').each(function() {
                const row = $(this);
                const productId = row.data('id');
                const quantity = parseFloat(row.find('.item-qty').val()) || 0;
                const unitPrice = parseFloat(row.find('.item-price').val()) || 0;
                const stockAvailable = parseFloat(row.find('.stock-available').text().split(' ')[0]) || 0; // Extract number from "stock unit" string

                if (unitPrice <= 0) {
                    hasZeroPrice = true;
                    return false; // Break .each loop
                }
                
                if (quantity > stockAvailable) {
                    Swal.fire('Error', `Kuantitas untuk produk ${row.find('td:first').text()} melebihi stok yang tersedia (${stockAvailable}).`, 'error');
                    hasZeroPrice = true; // Use this flag to prevent submission for stock issues too
                    return false; // Break .each loop
                }

                items.push({
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice,
                    // Frontend-only fields for display in receipt
                    display_quantity: quantity,
                    selected_unit: row.find('.stock-available').text().split(' ')[1], // Get unit from stock display
                    total_price_rounded: quantity * unitPrice
                });
            });

            if (hasZeroPrice) {
                Swal.fire('Error', 'Harga satuan tidak boleh nol atau negatif untuk beberapa produk.', 'error');
                return;
            }

            if (items.length === 0) {
                Swal.fire('Error', 'Keranjang belanja masih kosong.', 'error');
                return;
            }

            const subtotal = items.reduce((sum, item) => sum + item.total_price_rounded, 0);
            const totalAmount = subtotal; // No tax or global discount

            const transactionData = {
                customer_name: $('#customer_name').val(),
                sale_date: $('#sale_date').val(),
                payment_method_id: $('#payment_method_id').val(),
                notes: $('#notes').val(),
                items: items,
                subtotal: subtotal,
                discount_amount: 0, // Always 0
                tax_amount: 0,      // Always 0
                total_amount: totalAmount,
                //payment_status: 'lunas', // Asumsi grosir langsung lunas
                payment_status: $('#payment_status').val(),
                doctor_id: null, // Grosir tidak perlu dokter
                prescription_number: null
            };
            
            Swal.fire({
                title: 'Memproses Transaksi...',
                text: 'Mohon tunggu.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'process_sale.php', // API endpoint untuk menyimpan transaksi
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(transactionData),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: `Transaksi berhasil disimpan dengan No. Invoice: ${response.invoice_number}`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reset form
                            $('#wholesale-form')[0].reset();
                            $('#cart-items').empty();
                            updateTotals();
                        });
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error!', 'Terjadi kesalahan saat menghubungi server: ' + xhr.responseText, 'error');
                }
            });
        });

    });
    </script>
</body>
</html>
