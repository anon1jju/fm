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
        let unitSelectorHtml = '<div class="unit-selector-group text-xs mt-1" data-index="' + index + '">';
        unitSelectorHtml += '<span class="text-gray-600 mr-1 font-medium">Unit:</span>';
        const baseUnitOriginalCase = item.unit;
        const selectedUnit = item.selected_unit || baseUnitOriginalCase;

        if (baseUnitOriginalCase && baseUnitOriginalCase.toLowerCase() === 'strip') {
            const optionStrip = baseUnitOriginalCase;
            const optionPcs = "Tablet";
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionStrip}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit === optionStrip ? 'checked' : ''}> ${optionStrip}</label>`;
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionPcs}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit === optionPcs ? 'checked' : ''}> ${optionPcs}</label>`;
        } else {
            const displayUnit = baseUnitOriginalCase || 'N/A';
            unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${displayUnit}" class="unit-radio mr-0.5" data-item-index="${index}" checked> ${displayUnit}</label>`;
        }
        unitSelectorHtml += '</div>';

        html += `
            <div class="border-b py-2">
                <div class="flex justify-between mb-1">
                    <span class="font-medium">${item.name}</span>
                    <span class="text-blue-600">Rp ${item.price.toLocaleString('id-ID')} / ${item.unit}</span>
                </div>
                <div class="flex justify-between items-start">
                    <div class="flex-grow">
                        <div class="flex items-center space-x-2">
                            <label for="qty-${index}" class="sr-only">Kuantitas untuk ${item.name}</label>
                            <input type="tel" id="qty-${index}" class="cart-quantity-input w-24 px-2 py-1 text-center border rounded-md" value="${item.display_quantity}" data-index="${index}" step="0.1" min="0.1" data-stock="${item.stock}">
                        </div>
                        ${unitSelectorHtml}
                    </div>
                    <div class="text-right">
                        <span class="block">Rp ${item.total.toLocaleString('id-ID')}</span>
                        <button class="cart-remove text-red-500 mt-1" data-index="${index}">
                            <i class="fas fa-trash"></i>
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

function calculateItemActualQuantityAndTotal(itemIndex) {
    const item = cartItems[itemIndex];
    if (!item) return;

    let displayQty = parseFloat(item.display_quantity);
    if (isNaN(displayQty) || displayQty < 0) displayQty = 0;

    item.actual_quantity = displayQty; // Default

    if (item.unit && item.unit.toLowerCase() === 'strip' && item.selected_unit === 'Tablet') {
        item.actual_quantity = displayQty / PCS_PER_STRIP;
    }
    // Note: item.price is price per item.unit (e.g., per Strip)
    item.total = roundUpToNearestThousand(item.price * item.actual_quantity);
}


// Add product to cart directly
// Add product to cart directly
function addProductToCart(productData, quantity = 1) { // Default quantity tetap 1 untuk kasus umum
    const { product_id, product_name, kode_item, price, stock_quantity, unit, requires_prescription } = productData;
    const productPrice = parseFloat(price);
    const availableStock = parseFloat(stock_quantity); // Pastikan stok adalah float

    const existingItemIndex = cartItems.findIndex(item => item.id === product_id && item.selected_unit === (unit || productData.selected_unit));

    if (availableStock <= 0 && existingItemIndex === -1) {
         Swal.fire({ icon: 'error', title: 'Stok Habis', text: `Stok produk "${product_name}" sudah habis.`, showConfirmButton: false, timer: 1500 });
        return;
    }
    
    let requestedQuantity = parseFloat(quantity); // Kuantitas yang diminta

    // --- MODIFIKASI START ---
    // Jika ini item baru DAN stok yang tersedia kurang dari kuantitas yang diminta secara default (misal 1),
    // maka set kuantitas yang diminta menjadi sisa stok.
    if (existingItemIndex === -1 && availableStock < requestedQuantity && availableStock > 0) {
        requestedQuantity = availableStock;
    }
    // --- MODIFIKASI END ---

    let initial_actual_quantity = requestedQuantity;
    if (unit && unit.toLowerCase() === 'strip' && (productData.selected_unit === 'Tablet')) {
        initial_actual_quantity = requestedQuantity / PCS_PER_STRIP;
    }


    if (existingItemIndex !== -1) {
        const cartItem = cartItems[existingItemIndex];
        // Logika untuk item yang sudah ada di keranjang.
        // Pastikan requestedQuantity (yang mungkin sudah disesuaikan di atas jika ini item baru)
        // ditambahkan dengan benar ke display_quantity yang ada.
        // Untuk item yang sudah ada, kita biasanya ingin menambahkan 'quantity' asli yang di-pass (misal, 1 lagi)
        // bukan 'requestedQuantity' yang mungkin sudah diubah.
        // Jadi, kita perlu membedakan.
        
        let quantityToAddOnClick = parseFloat(quantity); // Ambil 'quantity' asli untuk penambahan ke item yang ada

        let newDisplayQuantity = cartItem.display_quantity + quantityToAddOnClick; 
        let newActualQuantity = cartItem.actual_quantity;

        // Hitung actual quantity baru berdasarkan display quantity baru
        if (cartItem.unit && cartItem.unit.toLowerCase() === 'strip' && cartItem.selected_unit === 'Tablet') {
            newActualQuantity = newDisplayQuantity / PCS_PER_STRIP;
        } else {
            newActualQuantity = newDisplayQuantity;
        }

        if (newActualQuantity > availableStock) {
            Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: `Sisa stok (dlm unit dasar) untuk "${product_name}" adalah ${availableStock}. Anda mencoba menambahkan sehingga total menjadi ${newActualQuantity.toFixed(2)}.`, showConfirmButton: false, timer: 2500 });
            return;
        }
        cartItem.display_quantity = newDisplayQuantity;
        calculateItemActualQuantityAndTotal(existingItemIndex);

    } else { // Item baru
        // 'initial_actual_quantity' sudah dihitung berdasarkan 'requestedQuantity' yang mungkin sudah disesuaikan
        if (initial_actual_quantity > availableStock) { // Periksa lagi untuk keamanan, meskipun seharusnya sudah ditangani
             Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: `Stok (dlm unit dasar) untuk "${product_name}" hanya ${availableStock}. Anda mencoba menambahkan ${initial_actual_quantity.toFixed(2)}.`, showConfirmButton: false, timer: 2500});
            return;
        }
        const newItem = {
            id: product_id,
            name: product_name,
            kode_item: kode_item || '',
            price: productPrice,
            unit: unit,
            selected_unit: unit,
            display_quantity: requestedQuantity, // Gunakan requestedQuantity yang sudah disesuaikan
            actual_quantity: initial_actual_quantity,
            stock: availableStock,
            requires_prescription: requires_prescription == 1 ? 1 : 0
        };
        cartItems.push(newItem);
        calculateItemActualQuantityAndTotal(cartItems.length - 1);
    }

    renderCartItems();
    updateCartSummary();
    showAddedToCartNotification(product_name);
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
                if (Array.isArray(response)) {
                    if (isBarcode && response.length === 1) {
                        addProductToCart(response[0], 1); // Default quantity 1
                    } else {
                        renderProducts(response);
                    }
                } else if (response.error) {
                    console.error('Error from server:', response.error);
                } else {
                    console.error('Invalid response format:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error searching products:', error);
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
            // --- MODIFICATION START ---
            const stockQtyNum = parseFloat(product.stock_quantity);
            // Assuming product.minimum_stock is available and numeric. If it can be non-numeric, add checks.
            const minStockNum = parseFloat(product.minimum_stock); 
            
            let stockDisplayHtml = '';
            if (stockQtyNum <= 0) {
                stockDisplayHtml = `<span class="bg-red-200 text-gray-900 px-2 py-1 rounded text-sm">Stok Habis</span>`;
            } else if (stockQtyNum < (minStockNum * 2)) { // Condition for low stock (yellow warning), adjust if needed
                                                        // For example, if threshold is just minimum_stock, use: stockQtyNum < minStockNum 
                stockDisplayHtml = `<span class="bg-yellow-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`;
            } else {
                stockDisplayHtml = `<span class="bg-green-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`;
            }
            // --- MODIFICATION END ---

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
        addProductToCart(productData, 1); // Default quantity 1
    });
}

// Render products in grid
/*function renderProducts(products) {
    let html = '';
    if (products.length === 0) {
        html = '<div class="col-span-full text-center py-8 text-gray-500">Tidak ada produk yang ditemukan</div>';
    } else {
        products.forEach(product => {
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
                        ${
                            parseInt(product.stock_quantity) === 0
                                ? `<span class="bg-red-200 text-gray-900 px-2 py-1 rounded text-sm">Stok Habis</span>`
                                : parseInt(product.stock_quantity) < product.minimum_stock * 2
                                ? `<span class="bg-yellow-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`
                                : `<span class="bg-green-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`
                        }
                    </div>
                </div>
            `;
        });
    }
    $('#products-container').html(html);
    $('.product-card').click(function() {
        const productData = $(this).data('product');
        addProductToCart(productData, 1); // Default quantity 1
    });
}*/

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
            quantity: item.actual_quantity, // This is actual_quantity in base units
            display_quantity: item.display_quantity, // ADD THIS LINE
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
                $('#payment-modal').addClass('hidden');
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
    updatePaymentButtonState(); // Call this to enable/disable process button
}

function updatePaymentButtonState() {
    const hasItems = cartItems.length > 0;
    const hasPaymentMethod = selectedPaymentMethod !== null;
    let isCashPaymentValid = true;

    if ($('#cash-payment-fields').is(':visible')) {
        const totalAmount = parseInt($('#grand-total').text().replace(/[^0-9.-]+/g, '')) || 0;
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
    if (!$('#cash-amount').val().trim()) {
        $('#change-amount').text('Rp 0');
         updatePaymentButtonState(); // Update button state when cash amount is empty
        return { totalAmount: 0, cashAmount: 0, changeAmount: 0 };
    }
    const grandTotalText = $('#grand-total').text().trim();
    const totalAmount = parseInt(grandTotalText.replace(/Rp\s?/, '').replace(/\./g, '')) || 0;
    const cashAmountText = $('#cash-amount').val().trim();
    const cashAmount = parseInt(cashAmountText.replace(/\D/g, '')) || 0;
    const changeAmount = Math.max(0, cashAmount - totalAmount);
    $('#change-amount').text(formatCurrency(changeAmount));
    updatePaymentButtonState(); // Update button state after calculating change
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
    const newSeparatorCountBefore = formattedValue.substr(0, cursorPos + separatorCountBefore).split('.').length - 1;
    const newCursorPos = cursorPos + (newSeparatorCountBefore - separatorCountBefore);
    setTimeout(() => {
        if (input.setSelectionRange) input.setSelectionRange(newCursorPos, newCursorPos);
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
    $('#cart-items').on('change', '.cart-quantity-input', function() {
        const index = parseInt($(this).data('index'));
        let newDisplayQuantity = parseFloat($(this).val());

        if (isNaN(newDisplayQuantity) || newDisplayQuantity <= 0) {
            Swal.fire({ icon: 'error', title: 'Kuantitas Tidak Valid', text: 'Mohon masukkan kuantitas yang valid (>0).', timer: 2000, showConfirmButton: false });
            $(this).val(cartItems[index].display_quantity); // Revert to old display quantity
            return;
        }
        
        cartItems[index].display_quantity = newDisplayQuantity;
        calculateItemActualQuantityAndTotal(index); // Recalculates actual_quantity and total

        if (cartItems[index].actual_quantity > cartItems[index].stock) {
            Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: `Stok untuk produk ini (dlm unit dasar ${cartItems[index].unit}) adalah ${cartItems[index].stock}. Kuantitas diminta ${cartItems[index].actual_quantity}.`, timer: 2500, showConfirmButton: false });
            // Revert logic might be needed here if strict stock check is enforced before allowing change
            // For now, it will show error, but the value might persist if not handled by reverting display_quantity
            // A better UX might be to cap display_quantity based on stock and selected_unit
            // $(this).val(cartItems[index].display_quantity); // Simple revert if needed
            // calculateItemActualQuantityAndTotal(index); // Recalculate if reverted
        }
        
        renderCartItems(); 
        updateCartSummary();
    });

    $('#cart-items').on('change', '.unit-radio', function() {
        const index = parseInt($(this).data('item-index'));
        const newSelectedUnit = $(this).val();

        if (cartItems[index]) {
            cartItems[index].selected_unit = newSelectedUnit;
            calculateItemActualQuantityAndTotal(index); // Recalculates actual_quantity and total

            if (cartItems[index].actual_quantity > cartItems[index].stock) {
                 Swal.fire({ icon: 'warning', title: 'Stok Mungkin Tidak Cukup', text: `Stok produk (dlm unit dasar ${cartItems[index].unit}) adalah ${cartItems[index].stock}. Kuantitas saat ini menjadi ${cartItems[index].actual_quantity} ${cartItems[index].unit}. Harap sesuaikan kuantitas jika perlu.`, timer: 3500, showConfirmButton: false });
                // Potentially adjust display_quantity here or just warn
            }
            renderCartItems(); 
            updateCartSummary(); 
        }
    });

    $(document).on('keypress', function(e) {
        if ($('input:focus, textarea:focus').length > 0 && !$('#product-search').is(':focus')) {
            if (e.which === 13 && $('#product-search').is(':focus')) {
                e.preventDefault();
                const keyword = $('#product-search').val().trim();
                if (keyword.length > 0) searchProducts(keyword, true); 
            }
            return;
        }
        if (e.which === 13 && barcodeBuffer.length > 0) {
            e.preventDefault();
            if (barcodeBuffer.length >= 8 || avgKeyInterval < 50) {
                searchProducts(barcodeBuffer, true);
                barcodeBuffer = ''; keyCount = 0; avgKeyInterval = 0; isBarcodeScanning = false;
            }
            return;
        }
        if (e.which < 32 || e.which > 126) return;
        barcodeBuffer += String.fromCharCode(e.which);
        detectBarcodeScanner(e);
        if (avgKeyInterval < 50 && barcodeBuffer.length > 3) {
            isBarcodeScanning = true;
            const searchInput = $('#product-search');
            if (!searchInput.is(':focus')) searchInput.focus().val(barcodeBuffer);
            else searchInput.val(barcodeBuffer);
        }
    });
    
    $('.category-btn').click(function() {
        $('.category-btn').removeClass('bg-blue-600 text-white').addClass('bg-white hover:bg-gray-100');
        $(this).removeClass('bg-white hover:bg-gray-100').addClass('bg-blue-600 text-white');
        loadProductsByCategory($(this).data('id'));
    });
    
    $('#product-search').on('input', function() {
        if (!isBarcodeScanning) searchProducts($(this).val().trim(), false);
    });
    
    $('#clear-search').click(function() {
        $('#product-search').val(''); $(this).hide(); $('.category-btn[data-id="0"]').click(); 
    });
    
    $('#product-search').on('input', function() {
        if ($(this).val().length > 0) $('#clear-search').show();
        else $('#clear-search').hide();
    });
    
    $('#product-search').on('keypress', function(e) {
        if (e.which === 13) { 
            e.preventDefault();
            const keyword = $(this).val().trim();
            if (keyword.length > 0) searchProducts(keyword, true);
        }
    });
    
    $('.payment-method-btn').click(function() {
        $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
        $(this).removeClass('bg-blue-100 text-blue-600').addClass('bg-blue-500 text-white');
        togglePaymentMethod($(this).data('id'), $(this).text().trim());
    });
    
    $('#cash-amount').on('input', function() {
        formatCashInput(this); calculateChange();
    });
    
    $('#cash-amount').on('keydown', function(e) {
        if (e.key === 'Enter') {
            if (!$('#process-payment-btn').is(':disabled')) { // Only process if button is not disabled
                 $('#process-payment-btn').click();
            }
            e.preventDefault();
        }
    });
    
    $('#process-payment-btn').click(function() {
        if (cartItems.length === 0) {
            Swal.fire({ icon: 'error', title: 'Keranjang kosong', showConfirmButton: false, timer: 1500 }); return;
        }
        if (selectedPaymentMethod === null) {
            Swal.fire({ icon: 'info', title: 'Metode Pembayaran', text: 'Mohon pilih metode pembayaran.', showConfirmButton: false, timer: 1500 }); return;
        }
        const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
        if (needsPrescription) {
            const doctorId = $('#doctor-id').val();
            const prescriptionNumber = $('#prescription-number').val();
            if (!doctorId || !prescriptionNumber) {
                Swal.fire({ icon: 'warning', title: 'Resep Dokter', text: `Informasi resep dokter diperlukan.`, showConfirmButton: false, timer: 1500 }); return;
            }
        }
        if ($('#cash-payment-fields').is(':visible') && (!$('#cash-amount').val().trim() || (parseInt($('#cash-amount').val().replace(/\D/g, '')) || 0) < (parseInt($('#grand-total').text().replace(/[^0-9.-]+/g, '')) || 0) ) ) {
             Swal.fire({ icon: 'error', title: 'Pembayaran Kurang', text: 'Jumlah pembayaran tunai kurang atau kosong.', confirmButtonText: 'OK' }).then(() => $('#cash-amount').focus()); return;
        }
        saveTransaction();
    });
    
    $('#discount-amount').on('input', function() { updateCartSummary(); });
    
    $('#success-print').click(function() {
        if (lastTransactionId) window.open('print_receipt.php?id=' + lastTransactionId, '_blank');
    });
    
    $('#success-new').click(function() {
        $('#success-modal').addClass('hidden'); location.reload();
    });
    
    $(window).click(function(e) {
        if ($(e.target).is('#payment-modal')) $('#payment-modal').addClass('hidden');
        if ($(e.target).is('#success-modal')) $('#success-modal').addClass('hidden');
    });
    
    renderCartItems();
    updateCartSummary();
    $('.category-btn[data-id="0"]').click();
    $('#product-search').focus();
});
