/**
 * Quotation/Invoice Admin JavaScript
 *
 * Handles repeaters, calculations, and tab switching for quotations and invoices.
 *
 * @package BuildERP
 * @since 1.0.0
 */

jQuery(document).ready(function($) {

	// Debug mode - only log when explicitly enabled
	var berpDebug = typeof berpQuotation !== 'undefined' && berpQuotation.debug === true;

	// Note: Tab switching is handled by admin.js global handler

	// Line Items Repeater
	var itemIndex = $('#berp-items-repeater .berp-item-row').length;

	$('#berp-add-item').on('click', function(e) {
		e.preventDefault();
		var template = $('#berp-item-row-template').html();
		if (!template) {
			if (berpDebug) {
				console.error('Line item template not found!');
			}
			return;
		}
		var newRow = template.replace(/{{INDEX}}/g, itemIndex);
		$('#berp-items-repeater .berp-repeater-items').append(newRow);
		itemIndex++;
		recalculateLineItem(itemIndex - 1);
	});

	$(document).on('click', '.berp-remove-item', function() {
		$(this).closest('.berp-item-row').remove();
		recalculateTotals();
	});

	// Auto-calculate line item amount on quantity/rate change
	$(document).on('input', '.berp-item-quantity, .berp-item-rate', function() {
		var row = $(this).closest('.berp-item-row');
		var index = row.data('index');
		recalculateLineItem(index);
	});

	// Recalculate totals on tax/discount change
	$(document).on('input', '#berp_tax_rate, #berp_discount_value', recalculateTotals);
	$(document).on('change', 'input[name="berp_discount_type"]', recalculateTotals);

	/**
	 * Recalculate single line item amount
	 *
	 * @param {number} index Line item index
	 */
	function recalculateLineItem(index) {
		var row = $('.berp-item-row[data-index="' + index + '"]');
		var quantity = parseFloat(row.find('.berp-item-quantity').val()) || 0;
		var rate = parseFloat(row.find('.berp-item-rate').val()) || 0;
		var amount = quantity * rate;
		row.find('.berp-item-amount').val(amount.toFixed(2));
		recalculateTotals();
	}

	/**
	 * Recalculate all totals (subtotal, tax, discount, grand total)
	 */
	function recalculateTotals() {
		var subtotal = 0;

		// Calculate subtotal from all line items
		$('.berp-item-row').each(function() {
			var amount = parseFloat($(this).find('.berp-item-amount').val()) || 0;
			subtotal += amount;
		});

		// Get tax and discount values
		var taxRate = parseFloat($('#berp_tax_rate').val()) || 0;
		var discountType = $('input[name="berp_discount_type"]:checked').val();
		var discountValue = parseFloat($('#berp_discount_value').val()) || 0;

		// Calculate discount amount
		var discountAmount = 0;
		if (discountType === 'percentage') {
			discountAmount = subtotal * (discountValue / 100);
		} else {
			discountAmount = discountValue;
		}

		// Calculate tax on (subtotal - discount)
		var taxableAmount = subtotal - discountAmount;
		var taxAmount = taxableAmount * (taxRate / 100);

		// Calculate grand total
		var grandTotal = taxableAmount + taxAmount;

		// Update display fields
		$('#berp_subtotal').val(subtotal.toFixed(2));
		$('#berp_discount_amount').val(discountAmount.toFixed(2));
		$('#berp_tax_amount').val(taxAmount.toFixed(2));
		$('#berp_grand_total').val(grandTotal.toFixed(2));
	}

	// Attachment Repeater
	var attachmentIndex = $('#berp-attachments-repeater .berp-attachment-row').length;

	$('#berp-add-attachment').on('click', function() {
		var template = $('#berp-attachment-row-template').html();
		var newRow = template.replace(/{{INDEX}}/g, attachmentIndex);
		$('#berp-attachments-repeater .berp-repeater-items').append(newRow);
		attachmentIndex++;
	});

	$(document).on('click', '.berp-remove-attachment', function() {
		$(this).closest('.berp-attachment-row').remove();
	});

	// Media Uploader for Attachments
	$(document).on('click', '.berp-upload-attachment', function(e) {
		e.preventDefault();

		if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
			alert('Media uploader not available');
			return;
		}

		var button = $(this);
		var inputField = button.siblings('.berp-attachment-url');

		// Create media frame
		var frame = wp.media({
			title: 'Select or Upload Attachment',
			button: {
				text: 'Use this file'
			},
			multiple: false
		});

		// When file is selected, populate the URL field
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			inputField.val(attachment.url);
		});

		// Open media frame
		frame.open();
	});

	// Initialize calculations on page load
	if ($('.berp-item-row').length > 0) {
		recalculateTotals();
	}

	// =========================================
	// Invoice Payment Repeater
	// =========================================
	var paymentIndex = $('#berp-payments-repeater .berp-payment-row').length;

	$('#berp-add-payment').on('click', function(e) {
		e.preventDefault();
		var template = $('#berp-payment-row-template').html();
		if (!template) {
			if (berpDebug) {
				console.error('Payment template not found!');
			}
			return;
		}
		var newRow = template.replace(/{{INDEX}}/g, paymentIndex);
		$('#berp-payments-repeater .berp-repeater-items').append(newRow);
		paymentIndex++;
		recalculatePayments();
	});

	$(document).on('click', '.berp-remove-payment', function(e) {
		e.preventDefault();
		$(this).closest('.berp-payment-row').remove();
		recalculatePayments();
	});

	// Auto-recalculate payments on amount change
	$(document).on('input', '.berp-payment-amount', recalculatePayments);

	/**
	 * Recalculate payment totals for invoices
	 */
	function recalculatePayments() {
		var totalPaid = 0;

		$('.berp-payment-row').each(function() {
			var amount = parseFloat($(this).find('.berp-payment-amount').val()) || 0;
			totalPaid += amount;
		});

		// Update summary if elements exist (invoice page only)
		var grandTotal = parseFloat($('#berp_grand_total').val()) || 0;
		var amountDue = grandTotal - totalPaid;

		// These elements exist only on invoice page, not quotation
		if ($('.berp-payment-summary').length > 0) {
			// Find the amount paid and due display elements and update them
			// The summary is rendered server-side, so we can add hidden fields for JS updates
		}
	}

	// =========================================
	// Client-Site Filtering
	// =========================================
	var $clientSelect = $('#berp_client_id');
	var $siteSelect = $('#berp_site_id');

	if ($clientSelect.length && $siteSelect.length) {
		// Store all site options for filtering
		var allSiteOptions = [];
		$siteSelect.find('option').each(function() {
			var $opt = $(this);
			allSiteOptions.push({
				value: $opt.val(),
				text: $opt.text(),
				clientId: $opt.data('client-id') || ''
			});
		});

		/**
		 * Filter sites based on selected client
		 */
		function filterSitesByClient() {
			var selectedClientId = $clientSelect.val();
			var currentSiteValue = $siteSelect.val();

			// Clear and rebuild site options
			$siteSelect.empty();

			// Add empty option
			$siteSelect.append('<option value="">-- Select Site (Optional) --</option>');

			// Add filtered options
			allSiteOptions.forEach(function(opt) {
				if (opt.value === '') {
					return; // Skip empty option (already added)
				}

				// Show site if no client selected, or if site belongs to selected client, or site has no client assigned
				var siteClientId = String(opt.clientId || '');
				var showSite = !selectedClientId || 
					siteClientId === '' || 
					siteClientId === String(selectedClientId);

				if (showSite) {
					var $newOpt = $('<option></option>')
						.val(opt.value)
						.text(opt.text)
						.attr('data-client-id', opt.clientId);

					// Restore previous selection if still valid
					if (opt.value === currentSiteValue) {
						$newOpt.prop('selected', true);
					}

					$siteSelect.append($newOpt);
				}
			});

			// If current site is not in filtered list, reset to empty
			if (currentSiteValue && $siteSelect.find('option[value="' + currentSiteValue + '"]').length === 0) {
				$siteSelect.val('');
			}
		}

		// Filter on client change
		$clientSelect.on('change', filterSitesByClient);

		// Initial filter on page load
		filterSitesByClient();
	}

});
