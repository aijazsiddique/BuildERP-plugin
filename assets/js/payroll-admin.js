/**
 * Payroll Admin JavaScript
 *
 * Handles AJAX operations for payroll management:
 * - Bulk payroll preview and processing
 * - Mark as paid functionality
 * - Email salary slips
 * - Recalculate payroll
 *
 * @package BuildERP
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Calculation mode toggle - Enable/disable salary fields based on mode
        $('input[name="berp_payroll[calculation_mode]"]').on('change', function() {
            const isManual = $(this).val() === 'manual';
            const salaryFields = $('#berp_basic_salary, #berp_gross_salary, #berp_net_salary');

            if (isManual) {
                // Manual mode - make fields editable
                salaryFields.prop('readonly', false).css('background-color', '#fff');
            } else {
                // Auto mode - make fields readonly
                salaryFields.prop('readonly', true).css('background-color', '#f0f0f1');
            }
        });

        // Initialize field state on page load based on current mode
        const currentMode = $('input[name="berp_payroll[calculation_mode]"]:checked').val();
        if (currentMode === 'manual') {
            $('#berp_basic_salary, #berp_gross_salary, #berp_net_salary')
                .prop('readonly', false)
                .css('background-color', '#fff');
        }

        // Employee mode toggle
        $('input[name="employee_mode"]').on('change', function() {
            if ($(this).val() === 'selected') {
                $('#employee-select-container').slideDown();
            } else {
                $('#employee-select-container').slideUp();
            }
        });

        // Preview payroll calculations
        $('#berp-preview-payroll').on('click', function(e) {
            e.preventDefault();

            const button = $(this);
            const spinner = button.siblings('.spinner');
            const month = $('#payroll_month').val();
            const employeeMode = $('input[name="employee_mode"]:checked').val();

            // Validation
            if (!month) {
                alert('Please select a payroll month');
                return;
            }

            // Get employee IDs
            let employeeIds = [];
            if (employeeMode === 'all') {
                $('#employee_ids option').each(function() {
                    employeeIds.push($(this).val());
                });
            } else {
                employeeIds = $('#employee_ids').val();
                if (!employeeIds || employeeIds.length === 0) {
                    alert('Please select at least one employee');
                    return;
                }
            }

            // Disable button and show spinner
            button.prop('disabled', true);
            spinner.addClass('is-active');

            // AJAX request
            $.ajax({
                url: berpPayroll.restUrl + 'payroll/calculate',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', berpPayroll.restNonce);
                },
                data: JSON.stringify({
                    employee_ids: employeeIds,
                    month: month
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        renderPreviewTable(response.data);
                        $('#berp-payroll-preview').slideDown();
                        $('#berp-generate-payroll').prop('disabled', false);
                        $('#berp-payroll-results').slideUp();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to calculate payroll'));
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'An error occurred while calculating payroll';
                    alert('Error: ' + error);
                },
                complete: function() {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            });
        });

        // Generate payroll (with batch processing support)
        $('#berp-generate-payroll').on('click', function(e) {
            e.preventDefault();

            if (!confirm(berpPayroll.strings.confirmGenerate)) {
                return;
            }

            const button = $(this);
            const spinner = button.siblings('.spinner');
            const month = $('#payroll_month').val();
            const employeeMode = $('input[name="employee_mode"]:checked').val();
            const autoPaid = $('#auto_mark_paid').is(':checked');
            const sendEmails = $('#send_emails').is(':checked');

            // Get employee IDs
            let employeeIds = [];
            if (employeeMode === 'all') {
                $('#employee_ids option').each(function() {
                    employeeIds.push($(this).val());
                });
            } else {
                employeeIds = $('#employee_ids').val();
            }

            // Disable buttons and show spinner
            button.prop('disabled', true);
            $('#berp-preview-payroll').prop('disabled', true);
            spinner.addClass('is-active');

            // Show progress
            $('#berp-payroll-results').html('<div class="berp-progress-bar"><div class="berp-progress-fill" style="width: 0%">0%</div></div>').show();

            // Start batch processing
            processBatch(employeeIds, month, autoPaid, sendEmails, 0, button, spinner);
        });

        /**
         * Process payroll in batches (recursive)
         */
        function processBatch(employeeIds, month, autoPaid, sendEmails, batchNumber, button, spinner) {
            $.ajax({
                url: berpPayroll.restUrl + 'payroll/process',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', berpPayroll.restNonce);
                },
                data: JSON.stringify({
                    employee_ids: employeeIds,
                    month: month,
                    batch_number: batchNumber,
                    auto_paid: autoPaid,
                    send_emails: sendEmails
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        // Update progress bar
                        const total = response.total || employeeIds.length;
                        const completed = response.completed || 0;
                        const percentage = Math.round((completed / total) * 100);
                        $('.berp-progress-fill').css('width', percentage + '%').text(percentage + '%');

                        // Check if there are more batches
                        if (response.has_more) {
                            // Process next batch
                            processBatch(employeeIds, month, autoPaid, sendEmails, response.next_batch, button, spinner);
                        } else {
                            // All batches complete - show final results
                            renderFinalResults(response, button, spinner);
                        }
                    } else {
                        alert('Error: ' + (response.message || 'Failed to process payroll'));
                        $('#berp-payroll-results').html('<div class="berp-results-summary error"><strong>Error:</strong> ' + (response.message || 'Failed to process payroll') + '</div>');
                        spinner.removeClass('is-active');
                        $('#berp-preview-payroll').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'An error occurred while processing payroll';
                    alert('Error: ' + error);
                    $('#berp-payroll-results').html('<div class="berp-results-summary error"><strong>Error:</strong> ' + error + '</div>');
                    spinner.removeClass('is-active');
                    $('#berp-preview-payroll').prop('disabled', false);
                }
            });
        }

        /**
         * Render final results after all batches complete
         */
        function renderFinalResults(response, button, spinner) {
            renderResults(response);
            $('#berp-payroll-preview').slideUp();
            button.prop('disabled', true);
            spinner.removeClass('is-active');
            $('#berp-preview-payroll').prop('disabled', false);
        }

        // Recalculate single payroll (on edit screen)
        $('#berp-recalculate-payroll').on('click', function(e) {
            e.preventDefault();

            const button = $(this);
            const spinner = button.siblings('.spinner');
            const employeeId = $('#berp_employee_id').val();
            const month = $('#berp_payroll_month').val();

            if (!employeeId || !month) {
                alert('Please select employee and month');
                return;
            }

            button.prop('disabled', true);
            spinner.addClass('is-active');

            $.ajax({
                url: berpPayroll.restUrl + 'payroll/calculate',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', berpPayroll.restNonce);
                },
                data: JSON.stringify({
                    employee_id: employeeId,
                    month: month
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success && response.data) {
                        populatePayrollFields(response.data);
                        alert('Payroll recalculated successfully. Please save to update the record.');
                    } else {
                        alert('Error: Failed to recalculate payroll');
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'An error occurred while recalculating';
                    alert('Error: ' + error);
                },
                complete: function() {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            });
        });

        // Mark as paid
        $('#berp-mark-paid').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Mark this payroll as paid? This will create an expense record and update employee balance.')) {
                return;
            }

            const button = $(this);
            const spinner = button.siblings('.spinner');
            const payrollId = button.data('payroll-id');

            button.prop('disabled', true);
            spinner.addClass('is-active');

            $.ajax({
                url: berpPayroll.restUrl + 'payroll/mark-paid',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', berpPayroll.restNonce);
                },
                data: JSON.stringify({
                    payroll_id: payrollId
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        alert('Payroll marked as paid successfully. Page will reload.');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to mark as paid'));
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'An error occurred';
                    alert('Error: ' + error);
                    button.prop('disabled', false);
                },
                complete: function() {
                    spinner.removeClass('is-active');
                }
            });
        });

        // Email salary slip
        $('#berp-email-salary-slip').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Send salary slip email to employee?')) {
                return;
            }

            const button = $(this);
            const spinner = button.siblings('.spinner');
            const payrollId = button.data('payroll-id');

            button.prop('disabled', true);
            spinner.addClass('is-active');

            $.ajax({
                url: berpPayroll.restUrl + 'payroll/email-slip',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', berpPayroll.restNonce);
                },
                data: JSON.stringify({
                    payroll_id: payrollId
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        alert('Salary slip emailed successfully.');
                        button.prop('disabled', false);
                    } else {
                        alert('Error: ' + (response.message || 'Failed to send email'));
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'An error occurred';
                    alert('Error: ' + error);
                    button.prop('disabled', false);
                },
                complete: function() {
                    spinner.removeClass('is-active');
                }
            });
        });

        // View salary slip (PDF) - TODO: Implement in Day 4
        $('#berp-view-salary-slip').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const spinner = button.siblings('.spinner');
            const payrollId = button.data('payroll-id');

            if (!payrollId) {
                alert('Invalid payroll record.');
                return;
            }

            spinner.addClass('is-active');

            const url = `${berpPayroll.restUrl}payroll/salary-slip?payroll_id=${payrollId}&_wpnonce=${berpPayroll.restNonce}`;
            window.open(url, '_blank');

            setTimeout(function() {
                spinner.removeClass('is-active');
            }, 600);
        });

        /**
         * Render preview table
         */
        function renderPreviewTable(data) {
            let html = '<table class="berp-preview-table widefat striped">';
            html += '<thead><tr>';
            html += '<th>Employee</th>';
            html += '<th>Employee ID</th>';
            html += '<th>Present Days</th>';
            html += '<th>OT Hours</th>';
            html += '<th>Gross Salary</th>';
            html += '<th>Net Salary</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            data.calculations.forEach(function(calc) {
                html += '<tr>';
                html += '<td>' + escapeHtml(calc.employee_name) + '</td>';
                html += '<td>' + escapeHtml(calc.employee_code || '-') + '</td>';
                html += '<td>' + calc.present_days + '</td>';
                html += '<td>' + calc.overtime_hours + '</td>';
                html += '<td>' + formatCurrency(calc.gross_salary) + '</td>';
                html += '<td>' + formatCurrency(calc.net_salary) + '</td>';
                html += '</tr>';
            });

            // Totals row
            html += '<tr class="total-row">';
            html += '<td colspan="4"><strong>Total (' + data.count + ' employees)</strong></td>';
            html += '<td><strong>' + formatCurrency(data.totals.gross) + '</strong></td>';
            html += '<td><strong>' + formatCurrency(data.totals.net) + '</strong></td>';
            html += '</tr>';

            html += '</tbody></table>';

            // Show errors if any
            if (data.errors && Object.keys(data.errors).length > 0) {
                html += '<div class="notice notice-warning" style="margin-top: 15px;"><p><strong>Warnings:</strong></p><ul>';
                Object.keys(data.errors).forEach(function(empId) {
                    const empName = $('#employee_ids option[value="' + empId + '"]').text();
                    html += '<li>' + escapeHtml(empName) + ': ' + escapeHtml(data.errors[empId]) + '</li>';
                });
                html += '</ul></div>';
            }

            $('#berp-preview-content').html(html);
        }

        /**
         * Render processing results
         */
        function renderResults(response) {
            let html = '';

            // Calculate totals from batch results
            let totalSuccess = 0;
            let totalErrors = 0;
            let totalEmailsSent = response.emails_sent || 0;
            let allResults = [];
            let allErrors = [];

            // Process batch_results if available
            if (response.batch_results && Array.isArray(response.batch_results)) {
                response.batch_results.forEach(function(result) {
                    if (result.success) {
                        totalSuccess++;
                        allResults.push(result);
                    } else {
                        totalErrors++;
                        allErrors.push(result);
                    }
                });
            } else {
                // Fallback to top-level counts
                totalSuccess = response.batch_success || 0;
                totalErrors = response.batch_errors || 0;
            }

            // Summary
            const summaryClass = totalErrors > 0 ? 'error' : 'success';
            html += '<div class="berp-results-summary ' + summaryClass + '">';
            html += '<h3>Processing Complete</h3>';
            html += '<p><strong>Successfully processed:</strong> ' + totalSuccess + ' employees</p>';
            if (totalErrors > 0) {
                html += '<p><strong>Errors:</strong> ' + totalErrors + '</p>';
            }
            if (totalEmailsSent > 0) {
                html += '<p><strong>Emails sent:</strong> ' + totalEmailsSent + '</p>';
            }
            html += '</div>';

            // Created payroll links
            if (allResults.length > 0) {
                html += '<p><strong>Created payroll records:</strong></p>';
                html += '<ul>';
                allResults.forEach(function(result) {
                    if (result.payroll_id) {
                        html += '<li><a href="post.php?post=' + result.payroll_id + '&action=edit">' +
                                escapeHtml(result.employee_name) + ' - Edit Payroll #' + result.payroll_id + '</a></li>';
                    }
                });
                html += '</ul>';
            }

            // Errors details
            if (allErrors.length > 0) {
                html += '<div class="notice notice-error"><p><strong>Errors encountered:</strong></p><ul>';
                allErrors.forEach(function(err) {
                    html += '<li>' + escapeHtml(err.employee_name) + ': ' + escapeHtml(err.error) + '</li>';
                });
                html += '</ul></div>';
            }

            $('#berp-results-content').html(html);
        }

        /**
         * Populate payroll fields from calculated data
         */
        function populatePayrollFields(data) {
            $('#berp_basic_salary').val(data.basic_salary || 0);
            $('#berp_gross_salary').val(data.gross_salary || 0);
            $('#berp_net_salary').val(data.net_salary || 0);
            // Additional fields can be populated here
        }

        /**
         * Format currency with symbol from settings
         */
        function formatCurrency(amount) {
            const symbol = berpPayroll.currencySymbol || '$';
            const position = berpPayroll.currencyPosition || 'before';
            const formatted = parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            
            if (position === 'after') {
                return formatted + ' ' + symbol;
            }
            return symbol + formatted;
        }

        /**
         * Escape HTML
         */
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})(jQuery);
