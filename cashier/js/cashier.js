// Cart data array
let cartItems = [];
let selectedPaymentMethod = null;
let lastTransactionId = null;
let lastInvoiceNumber = null;

// Barcode scanner detection variables
let barcodeBuffer = '';
let lastKeyTime = 0;
let avgKeyInterval = 0;
let keyCount = 0;
let isBarcodeScanning = false;
let barcodeTimeout;

const PCS_PER_STRIP = 10; // Definisikan konstanta konversi

// Helper function to round up to the nearest thousand
const roundUpToNearestThousand = (num) => Math.ceil(num / 1000) * 1000;

// Format number to currency
function formatCurrency(number) {
    return 'Rp ' + number.toLocaleString('id-ID');
}

// Update cart summary
function updateCartSummary() {
    let subtotal = 0;
    cartItems.forEach(item => {
        subtotal += item.total;
    });
    const taxRate = 0;
    const discountAmount = parseInt($('#discount-amount').val()) || 0;
    const taxAmount = subtotal * taxRate;
    const grandTotal = subtotal + taxAmount - discountAmount;
    $('#subtotal').text(formatCurrency(subtotal));
    $('#tax-amount').text(formatCurrency(taxAmount));
    $('#grand-total').text(formatCurrency(grandTotal));
    if (cartItems.length > 0 && selectedPaymentMethod !== null) {
        $('#process-payment-btn').prop('disabled', false);
    } else {
        $('#process-payment-btn').prop('disabled', true);
    }
    const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
    if (needsPrescription) {
        $('#prescription-info').removeClass('hidden');
    } else {
        $('#prescription-info').addClass('hidden');
    }
}

function renderManualSearchResults(products) {
    const resultsContainer = $('#search-results-container');
    resultsContainer.empty(); // Kosongkan hasil sebelumnya

    if (products.length === 0) {
        // Tetap tampilkan pesan jika tidak ada produk, tapi dengan styling list item
        resultsContainer.html('<div class="search-result-item text-gray-500">Produk tidak ditemukan.</div>');
        resultsContainer.removeClass('hidden');
        return;
    }

    products.forEach(product => {
        // Sesuaikan properti produk dengan data dari backend Anda
        const productName = product.product_name || 'Nama Produk Tidak Ada';
        // Coba beberapa kemungkinan nama field harga dari respons AJAX Anda
        const productPrice = parseFloat(product.price || product.selling_price || product.harga_jual || 0); 
        const itemCode = product.kode_item || product.item_code || ''; // Coba beberapa nama field kode
        const unitName = product.unit || product.unit_name || 'Pcs'; // Coba beberapa nama field unit

        // Membuat elemen list sederhana untuk setiap produk
        const resultItemHTML = `
            <div class="search-result-item p-3 hover:bg-gray-100 cursor-pointer border-b last:border-b-0">
                <div class="font-semibold text-sm">${productName}</div>
                <div class="text-xs text-gray-600 flex justify-between items-center mt-0.5">
                    <span>${itemCode ? `Kode: ${itemCode}` : ''}</span>
                    <span class="text-blue-600 font-medium">Rp ${productPrice.toLocaleString('id-ID')} / ${unitName}</span>
                </div>
            </div>
        `;
        
        const resultItem = $(resultItemHTML);

        resultItem.on('click', function() {
            addProductToCart(product, 1); // Fungsi Anda untuk menambah ke keranjang
            $('#product-search').val(''); // Kosongkan input pencarian
            resultsContainer.empty().addClass('hidden'); // Sembunyikan hasil pencarian
            $('#product-search').focus(); // Fokus kembali ke input (opsional)
        });
        resultsContainer.append(resultItem);
    });

    if (products.length > 0) {
        resultsContainer.removeClass('hidden'); // Tampilkan kontainer jika ada hasil
    } else {
        resultsContainer.addClass('hidden'); // Sembunyikan jika tidak (meskipun sudah ditangani di awal)
    }
}

// Render cart items
function renderCartItems() {
    if (cartItems.length === 0) {
        $('#cart-items').html(`
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                <p>Keranjang kosong</p>
            </div>
        `);
        return;
    }
    let html = '';
    cartItems.forEach((item, index) => {
        let unitSelectorHtml = '<div class="unit-selector-group text-sm mt-1" data-index="' + index + '">';
        unitSelectorHtml += '<span class="text-gray-700 mr-1 font-medium"></span>';
        const baseUnitOriginalCase = item.unit; 
        const selectedUnit = item.selected_unit || baseUnitOriginalCase;

        if (baseUnitOriginalCase && baseUnitOriginalCase.toLowerCase() === 'strip') {
            const optionStrip = baseUnitOriginalCase; 
            const optionPcs = "Tablet"; 
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionStrip}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit.toLowerCase() === optionStrip.toLowerCase() ? 'checked' : '' }>${optionStrip}</label>`;
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionPcs}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit.toLowerCase() === optionPcs.toLowerCase() ? 'checked' : '' }>${optionPcs}</label>`;
        } else {
            const displayUnit = baseUnitOriginalCase || 'N/A'; 
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${displayUnit}" class="unit-radio mr-0.5" data-item-index="${index}" checked>${displayUnit}</label>`;
        }
        unitSelectorHtml += '</div>';

        html += `
            <div class="border-b bg-white p-3 rounded-md shadow-sm mb-2">
                <div class="flex justify-between mb-1">
                    <span class="font-semibold">${item.name}</span>
                    <span class="text-blue-600">Rp ${item.price.toLocaleString('id-ID')} / ${item.unit}</span>
                </div>
                <div class="flex justify-between items-start">
                    <div class="flex-grow">
                        <div class="flex items-center space-x-1">
                            <button class="cart-qty-btn cart-qty-minus-btn p-1 border rounded-md hover:bg-gray-100" data-index="${index}" aria-label="Kurangi kuantitas">
                                <i class="fas fa-minus fa-xs"></i>
                            </button>
                            <label for="qty-${index}" class="sr-only">Kuantitas untuk ${item.name}</label>
                            <input type="tel" id="qty-${index}" class="cart-quantity-input w-16 px-3 py-1 text-center border rounded-md" value="${item.display_quantity}" data-index="${index}" step="any" min="0"> 
                            <button class="cart-qty-btn cart-qty-plus-btn p-1 border rounded-md hover:bg-gray-100" data-index="${index}" aria-label="Tambah kuantitas">
                                <i class="fas fa-plus fa-xs"></i>
                            </button>
                        </div>
                        ${unitSelectorHtml}
                    </div>
                    <div class="text-right">
                        <span class="block">Rp ${item.total.toLocaleString('id-ID')}</span>
                        <button class="cart-remove text-red-500 mt-1" data-index="${index}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
                ${item.requires_prescription === 1 ? '<div class="text-xs text-yellow-600 mt-1"><i class="fas fa-prescription mr-1"></i>Memerlukan resep</div>' : ''}
            </div>
        `;
    });
    $('#cart-items').html(html);
    $('.cart-remove').click(function() {
        const index = $(this).data('index');
        cartItems.splice(index, 1);
        renderCartItems();
        updateCartSummary();
    });
}




// Fungsi untuk menambah produk ke keranjang
function addProductToCart(productData, quantityClicked = 1) { 
    const productId = productData.product_id;
    const stockQtyInBaseUnits = parseFloat(productData.stock_quantity);
    const pricePerBaseUnit = parseFloat(productData.price);
    const baseUnitOfProduct = productData.unit; 

    const existingItemIndex = cartItems.findIndex(item => item.id === productId);

    if (existingItemIndex !== -1) { 
        const cartItem = cartItems[existingItemIndex];
        
        let actualQuantityEquivalentToAdd;
        if (cartItem.unit.toLowerCase() === 'strip' && cartItem.selected_unit.toLowerCase() === 'tablet') {
            actualQuantityEquivalentToAdd = quantityClicked / PCS_PER_STRIP;
        } else {
            actualQuantityEquivalentToAdd = quantityClicked;
        }

        const newPotentialActualQuantity = cartItem.actual_quantity + actualQuantityEquivalentToAdd;

        if (newPotentialActualQuantity > cartItem.stock) {
            Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: `Gagal menambahkan. Sisa stok (unit dasar ${cartItem.unit}): ${cartItem.stock}.`, showConfirmButton: false, timer: 3000 });
            return;
        }

        cartItem.actual_quantity = newPotentialActualQuantity;
        cartItem.display_quantity = parseFloat(cartItem.display_quantity) + quantityClicked; 
        cartItem.total = roundUpToNearestThousand(cartItem.price * cartItem.actual_quantity);

    } else { 
        if (stockQtyInBaseUnits <= 0) {
            Swal.fire({ icon: 'error', title: 'Stok Habis', text: `Stok produk "${productData.product_name}" sudah habis.`, showConfirmButton: false, timer: 1500 });
            return;
        }

        let actualQtyForNewItem = quantityClicked; 
        if (actualQtyForNewItem > stockQtyInBaseUnits) {
            Swal.fire('Info', `Stok terbatas untuk "${productData.product_name}". Menambahkan ${stockQtyInBaseUnits} ${baseUnitOfProduct}.`, 'info');
            actualQtyForNewItem = stockQtyInBaseUnits;
        }

        const newItem = {
            id: productId,
            name: productData.product_name,
            kode_item: productData.kode_item || '',
            price: pricePerBaseUnit,      
            unit: baseUnitOfProduct,       
            selected_unit: baseUnitOfProduct, 
            actual_quantity: actualQtyForNewItem, 
            display_quantity: actualQtyForNewItem, 
            stock: stockQtyInBaseUnits,    
            requires_prescription: productData.requires_prescription == 1 ? 1 : 0,
            total: roundUpToNearestThousand(pricePerBaseUnit * actualQtyForNewItem)
        };
        cartItems.push(newItem);
    }

    renderCartItems();
    updateCartSummary();
    showAddedToCartNotification(productData.product_name);
}


// Show notification when product is added to cart
function showAddedToCartNotification(productName) {
    $('#cart-notification').remove();
    $('body').append(`
        <div id="cart-notification" 
             class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg opacity-0 transition-all duration-300 transform -translate-y-8 z-50">
             <i class="fas fa-check-circle mr-1"></i> ${productName} ditambahkan ke keranjang
        </div>
    `);
    let notification = $('#cart-notification');
    setTimeout(() => {
        notification.removeClass('opacity-0 -translate-y-8');
    }, 10);
    setTimeout(() => {
        notification.addClass('opacity-0 translate-y-0');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Load products by category
function loadProductsByCategory(categoryId) {
    $.ajax({
        url: 'ajax_get_products.php',
        type: 'GET',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            renderProducts(response);
        },
        error: function(xhr, status, error) {
            console.error('Error loading products:', error);
        }
    });
}

// Realtime search products
let searchTimeout;
function searchProducts(keyword, isBarcode = false) {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (keyword.length === 0) {
        $('.category-btn[data-id="0"]').click(); 
        return;
    }
    if (keyword.length < 2 && !isBarcode) return; 
    const delay = isBarcode ? 0 : 300; 
    searchTimeout = setTimeout(function() {
            $.ajax({
                url: 'ajax_search_products.php',
                type: 'GET',
                data: { keyword: keyword, is_barcode: isBarcode ? 1 : 0 },
                dataType: 'json',
                success: function(response) {
                    const searchInput = $('#product-search');
                    const dropdownResultsContainer = $('#search-results-container'); // Untuk dropdown
                    // const gridProductsContainer = $('#products-container'); // Untuk grid produk umum di bawah

                    if (Array.isArray(response)) {
                        if (isBarcode && response.length === 1) {
                            addProductToCart(response[0], 1);
                            searchInput.val('').focus();
                            dropdownResultsContainer.empty().addClass('hidden'); // Kosongkan dropdown
                        } else if (isBarcode && response.length !== 1) {
                            Swal.fire({ /* ... */ });
                            searchInput.val(keyword).focus().select();
                            dropdownResultsContainer.empty().addClass('hidden'); // Kosongkan dropdown
                        } else if (!isBarcode) { 
                            // PENCARIAN MANUAL: Render ke dropdown container
                            renderManualSearchResults(response); 
                            // JANGAN render ke gridProductsContainer di sini untuk hasil search manual
                        }
                    } else {
                        // Error handling
                        if (!isBarcode) { dropdownResultsContainer.empty().addClass('hidden'); }
                    }
                },
                error: function(xhr, status, error) {
                    // Error handling
                    if (!isBarcode) { $('#search-results-container').empty().addClass('hidden'); }
                }
            });
        }, delay);
}

// Render products in grid
function renderProducts(products) {
    let html = '';
    if (products.length === 0) {
        html = '<div class="col-span-full text-center py-8 text-gray-500">Tidak ada produk yang ditemukan</div>';
    } else {
        products.forEach(product => {
            const stockQtyNum = parseFloat(product.stock_quantity);
            const minStockNum = parseFloat(product.minimum_stock); 
            
            let stockDisplayHtml = '';
            if (stockQtyNum <= 0) {
                stockDisplayHtml = `<span class="bg-red-200 text-gray-900 px-2 py-1 rounded text-sm">Stok Habis</span>`;
            } else if (stockQtyNum < (minStockNum * 2)) { 
                stockDisplayHtml = `<span class="bg-yellow-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`;
            } else {
                stockDisplayHtml = `<span class="bg-green-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`;
            }

            html += `
                <div class="product-card bg-white rounded-lg shadow-md p-3 cursor-pointer hover:shadow-lg transition"
                     data-product='${JSON.stringify(product)}'>
                    <div class="h-24 bg-gray-200 rounded-md mb-2 flex items-center justify-center relative">
                        <i class="fas fa-pills text-gray-400 text-3xl"></i>
                        ${product.requires_prescription == 1 ? 
                        '<span class="absolute top-0 right-0 bg-yellow-500 text-white text-xs px-1 rounded">Resep</span>' : ''}
                    </div>
                    <h3 class="font-medium">${product.product_name}</h3>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-sm text-gray-500">${product.kode_item || ''}</p>
                        <span class="bg-blue-100 text-blue-900 px-2 py-1 rounded text-sm">${product.posisi}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-blue-600 font-bold">Rp ${parseInt(product.price).toLocaleString('id-ID')}</p>
                        ${stockDisplayHtml}
                    </div>
                </div>
            `;
        });
    }
    $('#products-container').html(html);
    $('.product-card').click(function() {
        const productData = $(this).data('product');
        addProductToCart(productData, 1); 
    });
}

// Save transaction
function saveTransaction() {
    const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
    if (needsPrescription) {
        const doctorId = $('#doctor-id').val();
        const prescriptionNumber = $('#prescription-number').val();
        if (!doctorId || !prescriptionNumber) {
            Swal.fire({ icon: 'warning', title: 'Informasi Resep Diperlukan', text: 'Informasi resep dokter diperlukan. Mohon lengkapi.', confirmButtonText: 'OK'});
            return false;
        }
    }
    let subtotal = 0;
    cartItems.forEach(item => { subtotal += item.total; });
    const taxRate = 0;
    const discountAmount = parseInt($('#discount-amount').val()) || 0;
    const taxAmount = subtotal * taxRate;
    const grandTotal = subtotal + taxAmount - discountAmount;
    const transactionData = {
        customer_name: $('#customer-name').val(),
        doctor_id: needsPrescription ? $('#doctor-id').val() : null,
        prescription_number: needsPrescription ? $('#prescription-number').val() : null,
        user_id: 1, 
        subtotal: subtotal,
        tax_amount: taxAmount,
        discount_amount: discountAmount,
        total_amount: grandTotal,
        payment_method_id: selectedPaymentMethod,
        payment_status: 'paid', 
        notes: '',
        items: cartItems.map(item => ({
            product_id: item.id,
            quantity: item.actual_quantity, 
            display_quantity: item.display_quantity, 
            unit_price: item.price, 
            selected_unit: item.selected_unit || item.unit,
            total_price_rounded: item.total,
            discount_percent: 0 
        }))
        
    };
    $.ajax({
        url: 'save_transaction.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(transactionData),
        success: function(response) {
            if (response.success) {
                lastTransactionId = response.sale_id;
                lastInvoiceNumber = response.invoice_number;
                $('#success-invoice').text(response.invoice_number); 
                $('#success-modal').removeClass('hidden');
                cartItems = [];
                renderCartItems();
                updateCartSummary();
                $('#customer-name').val('');
                $('#doctor-id').val('');
                $('#prescription-number').val('');
                $('#discount-amount').val('0');
                $('#cash-amount').val('');
                $('#change-amount').text('Rp 0');
                $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
                selectedPaymentMethod = null;
                $('#cash-payment-fields').addClass('hidden');
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Gagal menyimpan transaksi.', confirmButtonText: 'OK' });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving transaction:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menyimpan transaksi. Coba lagi.', confirmButtonText: 'OK' });
        }
    });
    return true;
}

function togglePaymentMethod(methodId, methodName) {
    selectedPaymentMethod = methodId;
    $('#cash-amount').val('');
    $('#change-amount').text('Rp 0');
    if (methodName.toLowerCase().includes('tunai')) {
        $('#cash-payment-fields').removeClass('hidden');
    } else {
        $('#cash-payment-fields').addClass('hidden');
    }
    
    // Aktifkan tombol pembayaran jika ada item di keranjang dan metode pembayaran dipilih
    if (cartItems.length > 0 && selectedPaymentMethod !== null) {
        $('#process-payment-btn').prop('disabled', false);
    } else {
        $('#process-payment-btn').prop('disabled', true);
    }
}

function updatePaymentButtonState() {
    const hasItems = cartItems.length > 0;
    const hasPaymentMethod = selectedPaymentMethod !== null;
    let isCashPaymentValid = true;

    if ($('#cash-payment-fields').is(':visible')) {
        const totalAmountText = $('#grand-total').text() || $('#sticky-grand-total-display').text(); 
        const totalAmount = parseInt(totalAmountText.replace(/[^0-9.-]+/g, '')) || 0;
        const cashAmount = parseInt($('#cash-amount').val().replace(/\D/g, '')) || 0;
        isCashPaymentValid = cashAmount >= totalAmount;
    }

    if (hasItems && hasPaymentMethod && isCashPaymentValid) {
        $('#process-payment-btn').prop('disabled', false);
    } else {
        $('#process-payment-btn').prop('disabled', true);
    }
}


function calculateChange() {
    const grandTotalText = $('#grand-total').text().trim() || $('#sticky-grand-total-display').text().trim(); 
    const totalAmount = parseInt(grandTotalText.replace(/Rp\s?/, '').replace(/\./g, '')) || 0;
    
    if (!$('#cash-amount').val().trim()) {
        $('#change-amount').text('Rp 0');
        updatePaymentButtonState(); 
        return { totalAmount: totalAmount, cashAmount: 0, changeAmount: 0 };
    }
    
    const cashAmountText = $('#cash-amount').val().trim();
    const cashAmount = parseInt(cashAmountText.replace(/\D/g, '')) || 0;
    const changeAmount = Math.max(0, cashAmount - totalAmount);
    $('#change-amount').text(formatCurrency(changeAmount));
    updatePaymentButtonState(); 
    return { totalAmount, cashAmount, changeAmount };
}

function formatCashInput(input) {
    const cursorPos = input.selectionStart;
    const rawValue = input.value.replace(/\D/g, '');
    const formattedValue = rawValue === '' ? '' : new Intl.NumberFormat('id-ID').format(rawValue);
    const originalValue = input.value;
    let separatorCountBefore = 0;
    for (let i = 0; i < cursorPos; i++) {
        if (originalValue[i] === '.') separatorCountBefore++;
    }
    input.value = formattedValue;
    const newSeparatorCountBefore = formattedValue.substr(0, cursorPos + (formattedValue.length - originalValue.length)).split('.').length - 1;
    const newCursorPos = cursorPos + (newSeparatorCountBefore - separatorCountBefore);
    
    setTimeout(() => {
        if (input.setSelectionRange) {
            input.setSelectionRange(newCursorPos, newCursorPos);
        }
    }, 0);
}


function detectBarcodeScanner(e) {
    const currentTime = new Date().getTime();
    if (lastKeyTime > 0) {
        const interval = currentTime - lastKeyTime;
        if (keyCount > 0) avgKeyInterval = ((avgKeyInterval * keyCount) + interval) / (keyCount + 1);
        else avgKeyInterval = interval;
        keyCount++;
    }
    lastKeyTime = currentTime;
    clearTimeout(barcodeTimeout);
    barcodeTimeout = setTimeout(function() {
        if (barcodeBuffer.length >= 8 && avgKeyInterval < 50) { 
            searchProducts(barcodeBuffer, true);
        }
        barcodeBuffer = ''; keyCount = 0; avgKeyInterval = 0; isBarcodeScanning = false;
    }, 500); 
}

// Document ready
$(document).ready(function() {
    // Event handler untuk mengubah kuantitas di keranjang (ketik manual)
    $('#cart-items').on('input', '.cart-quantity-input', function() {
        const index = parseInt($(this).data('index'));
        const item = cartItems[index];
        if (!item) return;

        let newDisplayQuantity = parseFloat($(this).val());
        if (isNaN(newDisplayQuantity) || newDisplayQuantity < 0) {
            newDisplayQuantity = 0; 
        }
        
        item.display_quantity = newDisplayQuantity; 

        let newActualQuantity;
        if (item.unit.toLowerCase() === 'strip' && item.selected_unit.toLowerCase() === 'tablet') {
            newActualQuantity = item.display_quantity / PCS_PER_STRIP;
        } else { 
            newActualQuantity = item.display_quantity;
        }

        if (newActualQuantity > item.stock) {
            Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: `Stok (unit dasar ${item.unit}) hanya ${item.stock}. Kuantitas disesuaikan.`, timer: 3000, showConfirmButton: false });
            item.actual_quantity = item.stock; 
            if (item.unit.toLowerCase() === 'strip' && item.selected_unit.toLowerCase() === 'tablet') {
                item.display_quantity = item.actual_quantity * PCS_PER_STRIP;
            } else {
                item.display_quantity = item.actual_quantity;
            }
        } else {
            item.actual_quantity = newActualQuantity;
        }
        
        item.total = roundUpToNearestThousand(item.price * item.actual_quantity);
        
        renderCartItems(); 
        updateCartSummary();
    });

    // Event handler untuk tombol +/- kuantitas
    $('#cart-items').on('click', '.cart-qty-btn', function() {
        const index = parseInt($(this).data('index'));
        const item = cartItems[index];
        if (!item) return;

        const qtyInput = $(`#qty-${index}`);
        let currentDisplayQty = parseFloat(qtyInput.val());
        if (isNaN(currentDisplayQty)) currentDisplayQty = 0; // Default jika input tidak valid

        if ($(this).hasClass('cart-qty-plus-btn')) {
            currentDisplayQty += 1;
        } else if ($(this).hasClass('cart-qty-minus-btn')) {
            currentDisplayQty -= 1;
        }

        if (currentDisplayQty < 0) { // Batasi minimal 0 atau 1 sesuai kebutuhan
            currentDisplayQty = 0; 
        }
        
        qtyInput.val(currentDisplayQty);
        qtyInput.trigger('input'); // PENTING: Picu event input agar logika update lainnya berjalan
    });


    // Event handler untuk mengubah unit di keranjang
    $('#cart-items').on('change', '.unit-radio', function() {
        const index = parseInt($(this).data('item-index'));
        const item = cartItems[index];
        if (!item) return;

        const newSelectedUnit = $(this).val();
        const currentDisplayQtyFromInput = parseFloat($(`#qty-${index}`).val()); 
        const displayQtyToPreserve = isNaN(currentDisplayQtyFromInput) ? parseFloat(item.display_quantity) : currentDisplayQtyFromInput;

        item.selected_unit = newSelectedUnit; 

        let newActualQuantityBasedOnDisplay;
        if (item.unit.toLowerCase() === 'strip' && item.selected_unit.toLowerCase() === 'tablet') {
            newActualQuantityBasedOnDisplay = displayQtyToPreserve / PCS_PER_STRIP;
        } else { 
            newActualQuantityBasedOnDisplay = displayQtyToPreserve;
        }

        if (newActualQuantityBasedOnDisplay > item.stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok Tidak Cukup Untuk Unit Baru',
                text: `Stok (unit dasar ${item.unit}) hanya ${item.stock}. Kuantitas tampilan akan disesuaikan dengan stok maksimum pada unit ${newSelectedUnit}.`,
                timer: 4000,
                showConfirmButton: false
            });
            item.actual_quantity = item.stock; 
            if (item.unit.toLowerCase() === 'strip' && item.selected_unit.toLowerCase() === 'tablet') {
                item.display_quantity = item.actual_quantity * PCS_PER_STRIP;
            } else {
                item.display_quantity = item.actual_quantity;
            }
        } else {
            item.actual_quantity = newActualQuantityBasedOnDisplay;
            item.display_quantity = displayQtyToPreserve; 
        }
        
        item.total = roundUpToNearestThousand(item.price * item.actual_quantity);

        renderCartItems(); 
        updateCartSummary(); 
    });
    
    $(document).on('keypress', function(e) {
        if ($('input:focus, textarea:focus').length > 0) {
            if (e.which === 13 && $('#product-search').is(':focus')) { 
                e.preventDefault();
                const keyword = $('#product-search').val().trim();
                if (keyword.length > 0) {
                    searchProducts(keyword, true); 
                }
            }
            return; 
        }

        if (e.which === 13 && barcodeBuffer.length > 0) { 
            e.preventDefault();
            if (barcodeBuffer.length >= 3 && avgKeyInterval < 100) { 
                searchProducts(barcodeBuffer, true);
            }
            barcodeBuffer = ''; keyCount = 0; avgKeyInterval = 0; isBarcodeScanning = false;
            return;
        }

        if (e.which < 32 || e.which > 126) return; 
        barcodeBuffer += String.fromCharCode(e.which);
        detectBarcodeScanner(e); 

        if (avgKeyInterval < 50 && barcodeBuffer.length > 3 && !isBarcodeScanning) {
            isBarcodeScanning = true; 
        }
    });
    
    $('.category-btn').click(function() {
        $('.category-btn').removeClass('bg-blue-600 text-white').addClass('bg-white hover:bg-gray-100');
        $(this).removeClass('bg-white hover:bg-gray-100').addClass('bg-blue-600 text-white');
        loadProductsByCategory($(this).data('id'));
    });
    
    $('#product-search').on('input', function() {
        const keyword = $(this).val().trim();
        const resultsContainer = $('#search-results-container'); // TARGET UNTUK DROPDOWN

        if (keyword.length === 0) {
            resultsContainer.empty().addClass('hidden'); 
            $('#clear-search').hide();
            // JANGAN panggil fungsi yang mengisi #products-container (grid bawah) di sini
            return;
        }

        if (keyword.length < 2) { 
            resultsContainer.empty().addClass('hidden'); // Atau tampilkan pesan "ketik lagi"
            $('#clear-search').show();
            return;
        }
        
        $('#clear-search').show();
        searchProducts(keyword, false); // isBarcode = false untuk pencarian manual
    });
    
    $('#clear-search').click(function() {
        $('#product-search').val('').focus(); 
        $(this).hide(); 
        $('.category-btn[data-id="0"]').click(); 
    });
    
    $('.payment-method-btn').click(function() {
        $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-white hover:bg-blue-400 text-blue-600'); 
        $(this).removeClass('bg-white hover:bg-blue-400 text-blue-600').addClass('bg-blue-500 text-white');
        togglePaymentMethod($(this).data('id'), $(this).text().trim());
    });
    
    $('#cash-amount').on('input', function() {
        formatCashInput(this); 
        calculateChange();
    });
    
    $('#cash-amount').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); 
            if (!$('#process-payment-btn').is(':disabled')) {
                 $('#process-payment-btn').click();
            }
        }
    });
    
    $('#process-payment-btn').click(function() {
        if (cartItems.length === 0) {
            Swal.fire({ icon: 'error', title: 'Keranjang kosong', text:'Tidak ada item di keranjang.', showConfirmButton: false, timer: 1500 }); return;
        }
        if (selectedPaymentMethod === null) {
            Swal.fire({ icon: 'info', title: 'Metode Pembayaran', text: 'Mohon pilih metode pembayaran.', showConfirmButton: false, timer: 1500 }); return;
        }
        const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
        if (needsPrescription) {
            const doctorId = $('#doctor-id').val();
            const prescriptionNumber = $('#prescription-number').val().trim();
            if (!doctorId || !prescriptionNumber) {
                Swal.fire({ icon: 'warning', title: 'Resep Dokter Diperlukan', text: `Ada item yang memerlukan resep. Mohon lengkapi informasi dokter dan nomor resep.`, showConfirmButton: false, timer: 2500 }); return;
            }
        }
        if ($('#cash-payment-fields').is(':visible')) {
            const grandTotalText = $('#grand-total').text() || $('#sticky-grand-total-display').text();
            const totalAmount = parseInt(grandTotalText.replace(/[^0-9.-]+/g,'')) || 0;
            const cashAmount = parseInt($('#cash-amount').val().replace(/\D/g, '')) || 0;
            /*if (!$('#cash-amount').val().trim() || cashAmount < totalAmount) {
                Swal.fire({ icon: 'error', title: 'Pembayaran Kurang', text: 'Jumlah pembayaran tunai kurang atau kosong.', confirmButtonText: 'OK' }).then(() => $('#cash-amount').focus()); return;
            }*/
        }
        saveTransaction();
    });
    
    $('#discount-amount').on('input', function() { 
        this.value = this.value.replace(/\D/g, '');
        updateCartSummary(); 
        calculateChange(); 
    });
    
    $('#success-modal').on('click', '#success-print', function() { 
        if (lastTransactionId) {
            window.open('print_receipt.php?id=' + lastTransactionId, '_blank');
        }
    });
    
    $('#success-modal').on('click', '#success-new', function() { 
        $('#success-modal').addClass('hidden'); 
        if (typeof clearUIForHeldOrNewTransaction_inline === 'function') {
            clearUIForHeldOrNewTransaction_inline(); 
        } else {
            cartItems = []; renderCartItems(); updateCartSummary();
            $('#customer-name').val(''); $('#doctor-id').val(''); $('#prescription-number').val('');
            $('#discount-amount').val('0'); $('#cash-amount').val(''); $('#change-amount').text('Rp 0');
            $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
            selectedPaymentMethod = null; $('#cash-payment-fields').addClass('hidden');
        }
         $('#product-search').focus();
         location.reload();
    });
    
    renderCartItems();
    updateCartSummary();
    $('.category-btn[data-id="0"]').click(); 
    $('#product-search').focus();
});
