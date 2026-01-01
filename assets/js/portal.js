jQuery(document).ready(function($) {
    
    // Debug mode - only log when explicitly enabled
    var berpDebug = typeof berpPortal !== 'undefined' && berpPortal.debug === true;
    
    /**
     * Debounce helper function
     * 
     * @param {function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {function} Debounced function
     */
    var debounce = function(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    };
    
    // =============================================
    // Mobile Menu Toggle
    // =============================================
    
    var $menuToggle = $('.berp-mobile-menu-toggle');
    var $sidebar = $('.berp-portal-sidebar');
    
    $menuToggle.on('click', function() {
        var isExpanded = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', !isExpanded);
        $sidebar.toggleClass('berp-menu-open');
    });
    
    // Close menu when clicking on a nav link (mobile)
    $('.berp-portal-nav a').on('click', function() {
        if ($(window).width() <= 991) {
            $menuToggle.attr('aria-expanded', 'false');
            $sidebar.removeClass('berp-menu-open');
        }
    });
    
    // Close menu when clicking outside
    $(document).on('click', function(e) {
        if ($(window).width() <= 991) {
            if (!$(e.target).closest('.berp-portal-sidebar, .berp-mobile-menu-toggle').length) {
                $menuToggle.attr('aria-expanded', 'false');
                $sidebar.removeClass('berp-menu-open');
            }
        }
    });
    
    // Handle window resize - reset menu state when switching to desktop
    $(window).on('resize', debounce(function() {
        if ($(window).width() > 991) {
            $menuToggle.attr('aria-expanded', 'false');
            $sidebar.removeClass('berp-menu-open');
        }
    }, 250));
    
    // =============================================
    // Timekeeper Log Attendance Functions
    // =============================================

    var siteSelections = (typeof berpPortal !== 'undefined' && berpPortal.siteSelections) ? berpPortal.siteSelections : {};
    
    // Check attendance status for selected date
    function checkAttendanceStatus() {
        var date = $('#attendance_date').val();
        if (!date) return;
        
        // Clear all status indicators first
        $('.status-indicator').removeClass('logged').text('');
        
        $.ajax({
            url: berpPortal.ajaxurl,
            type: 'POST',
            data: {
                action: 'berp_portal_check_attendance_status',
                nonce: berpPortal.nonce,
                date: date
            },
            success: function(response) {
                if (response.success && response.data.logged) {
                    var logged = response.data.logged;
                    $('.status-indicator').each(function() {
                        var empId = $(this).data('employee-id');
                        if (logged[empId]) {
                            $(this).addClass('logged').text('✓ Logged');
                            // Also update overtime field with existing value
                            var $row = $(this).closest('tr');
                            if (logged[empId].overtime > 0) {
                                $row.find('.overtime-input').val(logged[empId].overtime);
                            }
                        }
                    });
                }
            }
        });
    }
    
    // Check status on page load
    if ($('#attendance_date').length) {
        checkAttendanceStatus();
    }
    
    // Check status when date changes
    $('#attendance_date').on('change', function() {
        checkAttendanceStatus();
    });
    
    // Select All button
    $('#select_all').on('click', function() {
        $('.employee-check').prop('checked', true);
        updateSelectedCount();
        sortEmployeeRows();
    });
    
    // Deselect All button
    $('#deselect_all').on('click', function() {
        $('.employee-check').prop('checked', false);
        updateSelectedCount();
        sortEmployeeRows();
    });
    
    // Header checkbox toggle
    $('#check_all_toggle').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.employee-check').prop('checked', isChecked);
        updateSelectedCount();
        sortEmployeeRows();
    });
    
    // Individual checkbox change
    $(document).on('change', '.employee-check', function() {
        updateSelectedCount();
        // Update header checkbox state
        var total = $('.employee-check').length;
        var checked = $('.employee-check:checked').length;
        $('#check_all_toggle').prop('checked', total === checked);
        sortEmployeeRows();
    });
    
    // Update selected count display
    function updateSelectedCount() {
        var count = $('.employee-check:checked').length;
        $('#selected_count').text(count);
    }

    // Preserve original row order for stable sorting (checked first, then original order).
    $('.employee-row').each(function(idx) {
        $(this).data('berpOrder', idx);
    });

    function sortEmployeeRows() {
        var $tbody = $('#employee_list_body');
        if (! $tbody.length) return;

        var $rows = $tbody.find('tr.employee-row').detach();

        $rows.sort(function(a, b) {
            var $a = $(a);
            var $b = $(b);

            var aChecked = $a.find('input.employee-check').is(':checked') ? 1 : 0;
            var bChecked = $b.find('input.employee-check').is(':checked') ? 1 : 0;
            if (aChecked !== bChecked) {
                return bChecked - aChecked;
            }

            var aOrder = parseInt($a.data('berpOrder'), 10) || 0;
            var bOrder = parseInt($b.data('berpOrder'), 10) || 0;
            return aOrder - bOrder;
        });

        $tbody.append($rows);
    }

    function applySiteSelection(siteId) {
        var key = String(parseInt(siteId, 10) || 0);
        if (! key || key === '0') return false;

        var remembered = siteSelections[key];
        if (! Array.isArray(remembered) || ! remembered.length) return false;

        var allowed = {};
        for (var i = 0; i < remembered.length; i++) {
            var id = parseInt(remembered[i], 10);
            if (id > 0) allowed[id] = true;
        }

        var matched = 0;
        $('.employee-row').each(function() {
            var $row = $(this);
            var id = parseInt($row.data('employee-id'), 10) || 0;
            var shouldCheck = !!allowed[id];
            $row.find('input.employee-check').prop('checked', shouldCheck);
            if (shouldCheck) matched++;
        });

        if (! matched) {
            return false;
        }

        updateSelectedCount();
        sortEmployeeRows();
        return true;
    }
    
    // Employee search filter with debouncing
    var filterEmployees = function() {
        var searchText = $('#employee_search').val().toLowerCase();
        $('.employee-row').each(function() {
            var name = $(this).data('name') || $(this).find('.employee-name').text().toLowerCase();
            if (name.indexOf(searchText) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };
    
    $('#employee_search').on('keyup', debounce(filterEmployees, 300));

    // Site change restores last used employee selection for that site (fallback = select all).
    $('#site_id').on('change', function() {
        if (! applySiteSelection($(this).val())) {
            $('.employee-check').prop('checked', true);
            updateSelectedCount();
            sortEmployeeRows();
        }
    });

    // Initial: prefer last site if available, then apply selection and sorting.
    if ($('#site_id').length && typeof berpPortal !== 'undefined' && berpPortal.lastSite) {
        $('#site_id').val(String(berpPortal.lastSite)).trigger('change');
    } else {
        sortEmployeeRows();
    }
    
    // Apply default overtime to all visible employees
    $('#default_overtime').on('change', function() {
        var defaultOT = $(this).val();
        if (defaultOT) {
            $('.employee-row:visible .overtime-input').val(defaultOT);
        }
    });
    
    // Portal Attendance Form Submission (Timekeeper bulk)
    $('#berp-portal-attendance-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#submit_attendance');
        var originalText = $submitBtn.text();
        
        // Get form data
        var date = $('#attendance_date').val();
        var siteId = $('#site_id').val();
        
        if (!date || !siteId) {
            alert('Please select date and site.');
            return;
        }
        
        // Collect checked employees
        var employees = [];
        $('.employee-check:checked').each(function() {
            var $row = $(this).closest('tr');
            var empId = $row.data('employee-id');
            var overtime = $row.find('.overtime-input').val() || 0;
            employees.push({
                id: empId,
                overtime: overtime
            });
        });
        
        if (employees.length === 0) {
            alert('Please select at least one employee.');
            return;
        }
        
        // Disable button
        $submitBtn.prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: berpPortal.ajaxurl,
            type: 'POST',
            data: {
                action: 'berp_portal_bulk_attendance',
                nonce: berpPortal.nonce,
                date: date,
                site_id: siteId,
                employees: JSON.stringify(employees)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Attendance submitted successfully!');
                    if (siteId && employees.length) {
                        siteSelections[String(parseInt(siteId, 10) || 0)] = employees.map(function(item) {
                            return parseInt(item.id, 10);
                        }).filter(function(id) { return id > 0; });
                    }
                    // Refresh attendance status to show updated indicators
                    checkAttendanceStatus();
                } else {
                    alert(response.data.message || 'Error submitting attendance.');
                }
            },
            error: function(xhr, status, error) {
                if (berpDebug) {
                    console.log('AJAX Error:', xhr, status, error);
                }
                alert('Server error. Please try again. (' + status + ')');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // =============================================
    // Employee Self-Service Attendance Functions
    // =============================================
    // NOTE: Self-service attendance submission is now handled via bulk attendance API
    // Individual attendance submission removed - no backend handler implemented

    // Simple client-side validation or UI enhancements can go here

    // Auto-fill date with today if empty
    if ($('#date').length && !$('#date').val()) {
        var today = new Date().toISOString().split('T')[0];
        $('#date').val(today);
    }

    // Toggle check-in/out fields based on status (if form exists)
    $('#status').on('change', function() {
        var status = $(this).val();
        if (status === 'present' || status === 'late' || status === 'half_day') {
            $('.berp-time-fields').slideDown();
        } else {
            $('.berp-time-fields').slideUp();
        }
    }).trigger('change');

});
