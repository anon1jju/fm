document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('purchaseForm');
    if (!form) {
        console.warn('Form pembelian (purchaseForm) tidak ditemukan.');
        return;
    }

    const localStorageKey = 'draftPembelianData';

    // --- Helper: Price Formatting ---
    function formatPriceForDraft(input) {
        if (!input) return;
        let start = input.selectionStart;
        let end = input.selectionEnd;
        let oldValue = input.value;

        let numericValue = input.value.replace(/[^\d]/g, '');
        input.dataset.numericValue = numericValue; // Simpan nilai numerik mentah

        if (numericValue) {
            input.value = parseFloat(numericValue).toLocaleString('id-ID');
        } else {
            input.value = '';
        }

        // Restore cursor position if input is active
        if (document.activeElement === input) {
            let newLength = input.value.length;
            let oldLength = oldValue.length;
            try {
                input.setSelectionRange(start + (newLength - oldLength), end + (newLength - oldLength));
            } catch (e) { /* ignore */ }
        }
    }

    function getNumericValueFromDraft(input) {
        if (input && input.dataset && input.dataset.numericValue !== undefined) {
            return input.dataset.numericValue;
        }
        if (input && input.value) {
            return input.value.replace(/[^\d]/g, '');
        }
        return '';
    }

    // --- Field selectors ---
    const mainFields = {
        invoice_number_supplier: form.querySelector('#invoice_number_supplier'),
        supplier_id: form.querySelector('#supplier_id'),
        purchase_date: form.querySelector('#purchase_date'),
        due_days: form.querySelector('#due_days'),
        due_date: form.querySelector('#due_date'),
        payment_status: form.querySelector('#payment_status'),
        notes: form.querySelector('#notes')
    };

    const itemRowFieldNames = [
        'product_search_display', 'product_id', 'quantity',
        'purchase_price', 'sell_price', 'batch_number', 'expiry_date'
    ];
    const itemsContainer = document.getElementById('itemsContainer');

    const initialInstallmentSection = document.getElementById('initial-installment-section');
    const cicilInfoPlaceholder = document.getElementById('cicil-info-placeholder');
    const installmentFields = {
        initial_payment_date: form.querySelector('#initial_payment_date'),
        initial_amount_paid: form.querySelector('#initial_amount_paid'),
        initial_payment_method: form.querySelector('#initial_payment_method'),
        initial_payment_reference: form.querySelector('#initial_payment_reference')
    };

    // --- Save Draft Data ---
    function saveDraftData() {
        if (!form) return;
        const draft = {
            main: {},
            items: [],
            installment: {}
        };

        for (const key in mainFields) {
            if (mainFields[key]) draft.main[key] = mainFields[key].value;
        }

        if (itemsContainer) {
            itemsContainer.querySelectorAll('.item-row').forEach(row => {
                const itemData = {};
                let rowHasData = false;
                itemRowFieldNames.forEach(fieldName => {
                    const input = row.querySelector(`[name^="${fieldName}"]`); // Use starts-with for array names
                    if (input) {
                        if (fieldName === 'purchase_price' || fieldName === 'sell_price') {
                            itemData[fieldName] = getNumericValueFromDraft(input) || input.value;
                        } else {
                            itemData[fieldName] = input.value;
                        }
                        if (input.value && input.value.trim() !== '') rowHasData = true;
                    }
                });
                if (rowHasData) draft.items.push(itemData);
            });
        }

        if (initialInstallmentSection && !initialInstallmentSection.classList.contains('hidden')) {
            for (const key in installmentFields) {
                if (installmentFields[key]) {
                    if (key === 'initial_amount_paid') {
                        draft.installment[key] = getNumericValueFromDraft(installmentFields[key]) || installmentFields[key].value;
                    } else {
                        draft.installment[key] = installmentFields[key].value;
                    }
                }
            }
        }
        localStorage.setItem(localStorageKey, JSON.stringify(draft));
    }

    // --- Load Draft Data ---
    function loadDraftData() {
        if (!form) return;
        const savedDraftJSON = localStorage.getItem(localStorageKey);
        if (!savedDraftJSON) {
            initializePluginsForPage(false); // Initialize for default state
            return;
        }

        const draft = JSON.parse(savedDraftJSON);

        if (draft.main) {
            for (const key in mainFields) {
                if (mainFields[key] && draft.main[key] !== undefined) {
                    mainFields[key].value = draft.main[key];
                }
            }
        }

        if (itemsContainer) {
            while (itemsContainer.rows.length > 0) { // Clear existing rows
                const rowToClear = itemsContainer.rows[0];
                if (typeof window.destroyFlatpickrInstance === 'function') { // Assuming you might make a helper
                     const expiryInput = rowToClear.querySelector('input[name="expiry_date[]"]');
                     if (expiryInput && expiryInput._flatpickr) expiryInput._flatpickr.destroy();
                }
                itemsContainer.deleteRow(0);
            }

            if (draft.items && draft.items.length > 0) {
                draft.items.forEach(itemData => {
                    if (typeof window.addItemRow === 'function') {
                        window.addItemRow(); // Adds a new row, plugins initialized by wrapped addItemRow
                        const newRow = itemsContainer.rows[itemsContainer.rows.length - 1];
                        if (newRow) {
                            itemRowFieldNames.forEach(fieldName => {
                                const input = newRow.querySelector(`[name^="${fieldName}"]`);
                                if (input && itemData[fieldName] !== undefined) {
                                    input.value = itemData[fieldName];
                                    // If it's a price field, format it after setting value
                                    if (fieldName === 'purchase_price' || fieldName === 'sell_price') {
                                        formatPriceForDraft(input);
                                    }
                                }
                            });
                            // Manually trigger update for item total as values are set programmatically
                             if (typeof window.updateItemTotal === 'function') window.updateItemTotal(newRow);
                        }
                    }
                });
            } else if (typeof window.addItemRow === 'function' && itemsContainer.rows.length === 0) {
                 window.addItemRow(); // Ensure at least one row if draft items is empty
            }
        }


        if (mainFields.payment_status) { // Trigger change to show/hide installment section
             mainFields.payment_status.dispatchEvent(new Event('change'));
             // Now load installment data if applicable
            if (draft.installment && mainFields.payment_status.value === 'cicil') {
                if (initialInstallmentSection) initialInstallmentSection.classList.remove('hidden');
                if (cicilInfoPlaceholder) cicilInfoPlaceholder.classList.add('hidden');
                for (const key in installmentFields) {
                    if (installmentFields[key] && draft.installment[key] !== undefined) {
                        installmentFields[key].value = draft.installment[key];
                        if (key === 'initial_amount_paid') {
                            formatPriceForDraft(installmentFields[key]);
                        }
                    }
                }
            } else {
                if (initialInstallmentSection) initialInstallmentSection.classList.add('hidden');
                if (cicilInfoPlaceholder) cicilInfoPlaceholder.classList.remove('hidden');
            }
        }
        
        initializePluginsForPage(true, draft); // Re-initialize all plugins after loading data

        if (typeof window.updateGrandTotal === 'function') {
            window.updateGrandTotal(); // Recalculate grand total
        }
    }
    
    function initializePluginsForPage(afterLoad = false, loadedDraft = null) {
        // Initialize Select2 for supplier
        if (mainFields.supplier_id && $.fn.select2) {
            $(mainFields.supplier_id).select2({ placeholder: "Pilih Supplier", allowClear: true });
            if (afterLoad && loadedDraft && loadedDraft.main && loadedDraft.main.supplier_id) {
                $(mainFields.supplier_id).val(loadedDraft.main.supplier_id).trigger('change.select2');
            }
        }

        // Initialize Flatpickr for main date fields
        [mainFields.purchase_date, mainFields.due_date, installmentFields.initial_payment_date].forEach(dateInput => {
            if (dateInput && dateInput.classList.contains('flatpickr-date') && typeof flatpickr === "function") {
                if (dateInput._flatpickr) dateInput._flatpickr.destroy(); // Destroy if exists
                let config = { dateFormat: "Y-m-d", altInput: true, altFormat: "d-m-Y", allowInput: true };
                if (dateInput.id === 'purchase_date' && typeof window.calculateDueDate === 'function') {
                     config.onClose = [window.calculateDueDate]; // Add existing onClose handler
                }
                flatpickr(dateInput, config);
                if (afterLoad && loadedDraft && loadedDraft.main && loadedDraft.main[dateInput.name]) {
                     if(dateInput._flatpickr) dateInput._flatpickr.setDate(loadedDraft.main[dateInput.name], true);
                } else if (afterLoad && loadedDraft && loadedDraft.installment && loadedDraft.installment[dateInput.name]) {
                     if(dateInput._flatpickr) dateInput._flatpickr.setDate(loadedDraft.installment[dateInput.name], true);
                }
            }
        });
        
        // Plugins for item rows are handled by the wrapped addItemRow or by iterating rows if needed
        if (itemsContainer && afterLoad) { // If after load, ensure all rows have plugins initialized
            itemsContainer.querySelectorAll('.item-row').forEach(row => {
                if (typeof window.initializePluginsForRow === 'function') { // Expect beli.php to have this
                    window.initializePluginsForRow(row);
                } else {
                    // Fallback minimal init for expiry date
                    const expiryInput = row.querySelector('input[name="expiry_date[]"]');
                    if (expiryInput && typeof flatpickr === "function") {
                        if (expiryInput._flatpickr) expiryInput._flatpickr.destroy();
                        flatpickr(expiryInput, { dateFormat: "Y-m-d", altInput: true, altFormat: "d-m-Y", allowInput: true });
                    }
                }
                // Apply price formatting for loaded data
                row.querySelectorAll('input[name="purchase_price[]"], input[name="sell_price[]"]').forEach(priceInput => {
                    if(priceInput.value) formatPriceForDraft(priceInput);
                });
            });
        }
        if (installmentFields.initial_amount_paid && installmentFields.initial_amount_paid.value) {
            formatPriceForDraft(installmentFields.initial_amount_paid);
        }
    }


    // --- Load draft data on page load ---
    loadDraftData();

    // --- Event Listeners to Save Data ---
    form.addEventListener('input', function(event) {
        const target = event.target;
        if (target.type === 'file' || !target.name) return;

        if (target.name.includes('purchase_price') || target.name.includes('sell_price') || target.id === 'initial_amount_paid') {
            formatPriceForDraft(target);
        }
        saveDraftData();
    });

    form.addEventListener('change', function(event) { // For select, date pickers after close
        saveDraftData();
    });

    // Clear localStorage on successful form submission
    form.addEventListener('submit', function() {
        form.querySelectorAll('input[data-numeric-value]').forEach(input => {
            input.value = input.dataset.numericValue || ''; // Submit raw numeric value
        });
        localStorage.removeItem(localStorageKey);
    });

    // Clear localStorage on "Batal" button click
    const cancelButton = form.querySelector('a[href="beli.php"].ti-btn-light');
    if (cancelButton) {
        cancelButton.addEventListener('click', function(e) {
            if (confirm('Apakah Anda yakin ingin membatalkan dan menghapus draf pembelian ini?')) {
                localStorage.removeItem(localStorageKey);
            } else {
                e.preventDefault();
            }
        });
    }

    // --- Wrap existing global functions from beli.php ---
    if (typeof window.addItemRow === 'function') {
        const originalAddItemRow = window.addItemRow;
        window.addItemRow = function() {
            originalAddItemRow.apply(this, arguments); // Call original
            const newRow = itemsContainer ? itemsContainer.rows[itemsContainer.rows.length - 1] : null;
            if (newRow) {
                // Ensure plugins and listeners are attached by original addItemRow or here
                // For price formatting on new row inputs:
                newRow.querySelectorAll('input[name="purchase_price[]"], input[name="sell_price[]"]').forEach(priceInput => {
                    priceInput.addEventListener('input', () => formatPriceForDraft(priceInput));
                });
                // Assuming original addItemRow handles flatpickr, product search init
            }
            saveDraftData(); // Save after adding
        };
    } else {
        console.warn('Global function addItemRow not found for wrapping.');
    }

    // Save data after an item is removed
    if (itemsContainer) {
        const observer = new MutationObserver(function(mutationsList) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.removedNodes.length > 0) {
                   // Check if a row was actually removed (not just content change)
                   let rowRemoved = false;
                   mutation.removedNodes.forEach(node => {
                       if(node.classList && node.classList.contains('item-row')){
                           rowRemoved = true;
                       }
                   });
                   if(rowRemoved) saveDraftData();
                   break; 
                }
            }
        });
        observer.observe(itemsContainer, { childList: true, subtree: false }); // Observe direct children of itemsContainer
    }
    
    // Save data when payment status changes (as it affects installment section)
    if(mainFields.payment_status) {
        mainFields.payment_status.addEventListener('change', saveDraftData);
    }
});
