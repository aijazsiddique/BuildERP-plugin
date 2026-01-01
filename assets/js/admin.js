/**
 * BuildErp Admin Scripts
 *
 * @package BuildErp
 * @since   1.0.0
 */

(function ($) {
    'use strict';

    // Debug mode - only log when WP_DEBUG is enabled
    var berpDebug = typeof berpAdmin !== 'undefined' && berpAdmin.debug === true;

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

    /**
     * BuildErp Admin Object
     */
    const BerpAdmin = {
        /**
         * Initialize
         */
        init: function () {
            this.bindEvents();
            this.initRepeaters();
            this.initSettingsTabs();
            this.initSubTabs();
            this.initMediaUploads();
            this.initWorkingDaysToggle();
            this.initColorPickers();
            this.initMetaTabs();
            this.initPortalAccess();
            this.initAttendance();
            this.initFormulaBuilder();
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            // Add repeater item.
            $(document).on('click', '.berp-repeater-add', this.addRepeaterItem);

            // Remove repeater item.
            $(document).on('click', '.berp-repeater-remove', this.removeRepeaterItem);

            // Confirm delete actions.
            $(document).on('click', '.berp-confirm-delete', this.confirmDelete);

            // Metabox tabs.
            $(document).on('click', '.berp-metabox-tab', this.handleMetaTabClick);
        },

        /**
         * Initialize repeater fields
         */
        initRepeaters: function () {
            $('.berp-repeater').each(
                function () {
                    const $rep     = $(this);
                    const $next    = $rep.find('.berp-repeater-next').first();
                    const explicit = parseInt($next.val(), 10);
                    const existing = $rep.find('.berp-repeater-item').not('.berp-repeater-template').length;
                    const start    = Number.isInteger(explicit) && explicit >= 0 ? explicit : existing;
                    $rep.data('nextIndex', start);
                }
            );
        },

        /**
         * Initialize settings tabs (vertical navigation).
         */
        initSettingsTabs: function () {
            const $navLinks  = $('.berp-settings-nav a');
            const $panels    = $('.berp-tab-panel');
            const $hiddenTab = $('input[name="berp_tab"]');

            if (! $navLinks.length || ! $panels.length) {
                return;
            }

            const params  = new URLSearchParams(window.location.search);
            const hashTab = window.location.hash ? window.location.hash.replace('#', '') : '';
            const initial = params.get('berp_tab') || hashTab || ($hiddenTab.length ? $hiddenTab.val() : '') || $navLinks.first().data('tab-target');

            const activate = function (target) {
                $navLinks.removeClass('is-active');
                $panels.removeClass('is-active');
                $navLinks.filter('[data-tab-target="' + target + '"]').addClass('is-active');
                $panels.filter('[data-tab-panel="' + target + '"]').addClass('is-active');
                params.set('berp_tab', target);
                if ($hiddenTab.length) {
                    $hiddenTab.val(target);
                }
                const newUrl = window.location.pathname + '?' + params.toString() + (window.location.hash ? window.location.hash : '');
                history.replaceState(null, '', newUrl);
            };

            activate(initial);

            $navLinks.on(
                'click',
                function (e) {
                    e.preventDefault();
                    const target = $(this).data('tab-target');
                    activate(target);
                }
            );
        },

        /**
         * Initialize horizontal sub tabs inside panels.
         */
        initSubTabs: function () {
            $('.berp-subtabs').each(
                function () {
                    const $container = $(this);
                    const $links     = $container.find('.berp-subtab-nav a');
                    const $panels    = $container.find('.berp-subtab-panel');
                    const $hiddenSub = $('input[name="berp_subtab"]');

                    if (! $links.length || ! $panels.length) {
                            return;
                    }

                    const params     = new URLSearchParams(window.location.search);
                    const hashSubTab = params.get('berp_subtab') || ($hiddenSub.length ? $hiddenSub.val() : '');

                    const activate = function (target) {
                        $links.removeClass('is-active');
                        $panels.removeClass('is-active');
                        $links.filter('[data-subtab-target="' + target + '"]').addClass('is-active');
                        $panels.filter('[data-subtab-panel="' + target + '"]').addClass('is-active');
                        params.set('berp_subtab', target);
                        if ($hiddenSub.length) {
                            $hiddenSub.val(target);
                        }
                        const newUrl = window.location.pathname + '?' + params.toString() + (window.location.hash ? window.location.hash : '');
                        history.replaceState(null, '', newUrl);
                    };

                    const initial = hashSubTab && $links.filter('[data-subtab-target="' + hashSubTab + '"]').length ? hashSubTab : $links.first().data('subtab-target');
                    activate(initial);

                    $links.on(
                        'click',
                        function (e) {
                             e.preventDefault();
                             activate($(this).data('subtab-target'));
                        }
                    );
                }
            );
        },

        /**
         * Initialize media uploader buttons.
         */
        initMediaUploads: function () {
            $(document).on(
                'click',
                '.berp-media-upload',
                function (e) {
                    e.preventDefault();

                    const $button        = $(this);
                    const targetSelector = $button.data('target');
                    let $target          = targetSelector ? $button.closest('.berp-repeater-item, .berp-media-field').find(targetSelector).first() : $();

                    if (! $target.length) {
                            $target = $button.closest('.berp-media-field').find('input.berp-media-target').first();
                    }

                    if (! $target.length || typeof wp === 'undefined' || ! wp.media) {
                        return;
                    }

                    const frame = wp.media(
                        {
                            title: $button.text(),
                            button: { text: $button.text() },
                            multiple: false
                        }
                    );

                    frame.on(
                        'select',
                        function () {
                             const attachment = frame.state().get('selection').first().toJSON();
                             $target.val(attachment.url);
                             // Update preview if present or create one.
                             let $preview = $button.closest('.berp-media-field').next('.berp-media-preview');
                            if (! $preview.length) {
                                $preview = $('<div class="berp-media-preview" />').insertAfter($button.closest('.berp-media-field'));
                            }
                            $preview.html('<img src="' + attachment.url + '" alt="Logo preview" />');
                        }
                    );

                    frame.open();
                }
            );

            // Preserve active tab/subtab on save by updating referer.
            $(document).on(
                'submit',
                '.berp-settings-form',
                function () {
                    const params        = new URLSearchParams(window.location.search);
                    const $activeTab    = $('.berp-settings-nav a.is-active').data('tab-target');
                    const $activeSubtab = $('.berp-subtab-nav a.is-active').data('subtab-target');
                    if ($activeTab) {
                            params.set('berp_tab', $activeTab);
                    }
                    if ($activeSubtab) {
                        params.set('berp_subtab', $activeSubtab);
                    }
                    const referer       = window.location.pathname + '?' + params.toString();
                    const $refererInput = $(this).find('input[name=\"_wp_http_referer\"]');
                    if ($refererInput.length) {
                        $refererInput.val(referer);
                    }
                }
            );
        },

        /**
         * Handle payroll working days mode toggle.
         */
        initWorkingDaysToggle: function () {
            const $modeInputs = $('input[name="berp_settings[payroll][working_days_mode]"]');
            const $fixedWrap  = $('.berp-working-days-fixed');

            if (! $modeInputs.length || ! $fixedWrap.length) {
                return;
            }

            const toggleFixed = function () {
                const mode = $modeInputs.filter(':checked').val();
                $fixedWrap.toggle(mode === 'fixed');
            };

            toggleFixed();
            $modeInputs.on('change', toggleFixed);
        },

        /**
         * Initialize employee metabox tabs.
         */
        initMetaTabs: function () {
            $('.berp-metabox-layout').each(
                function () {
                    const $layout = $(this);
                    const $tabs   = $layout.find('.berp-metabox-tab');
                    const $panels = $layout.find('.berp-metabox-panel');

                    if (! $tabs.length || ! $panels.length) {
                            return;
                    }

                    if (! $tabs.filter('.is-active').length) {
                        $tabs.first().addClass('is-active');
                    }
                    if (! $panels.filter('.is-active').length) {
                        $panels.first().addClass('is-active');
                    }
                }
            );
        },

        /**
         * Handle meta tab click.
         */
        handleMetaTabClick: function (e) {
            e.preventDefault();

            const $tab       = $(this);
            const target     = $tab.data('tab-target');
            const $container = $tab.closest('.berp-metabox-layout');
            const $tabs      = $container.find('.berp-metabox-tab');
            const $panels    = $container.find('.berp-metabox-panel');

            $tabs.removeClass('is-active');
            $tab.addClass('is-active');
            $panels.removeClass('is-active');
            $panels.filter('[data-tab-panel="' + target + '"]').addClass('is-active');
        },

        /**
         * Initialize WordPress color pickers.
         */
        initColorPickers: function () {
            if (typeof $.fn.wpColorPicker !== 'function') {
                return;
            }
            $('.berp-color-picker').wpColorPicker();
        },

        /**
         * Add repeater item
         */
        addRepeaterItem: function (e) {
            e.preventDefault();

            const $button   = $(this);
            const $repeater = $button.closest('.berp-repeater');
            const $template = $repeater.find('.berp-repeater-template');
            const nextIndex = $repeater.data('nextIndex') || 0;

            if ($template.length) {
                const $newItem = $template.clone();
                $newItem.removeClass('berp-repeater-template berp-hidden');
                $newItem.addClass('berp-repeater-item');
                $newItem.removeAttr('style').removeAttr('aria-hidden');
                $newItem.html($newItem.html().replace(/__INDEX__/g, nextIndex));
                $newItem.find(':input').prop('disabled', false);
                $repeater.data('nextIndex', nextIndex + 1);
                $button.before($newItem);
            }
        },

        /**
         * Remove repeater item
         */
        removeRepeaterItem: function (e) {
            e.preventDefault();

            if (confirm(berpAdmin.strings.confirm_delete)) {
                $(this).closest('.berp-repeater-item').fadeOut(
                    300,
                    function () {
                        $(this).remove();
                    }
                );
            }
        },

        /**
         * Confirm delete action
         */
        confirmDelete: function (e) {
            if (! confirm(berpAdmin.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * AJAX Helper - Make AJAX request
         *
         * @param {string} action - WordPress AJAX action
         * @param {object} data - Data to send
         * @param {function} successCallback - Success callback
         * @param {function} errorCallback - Error callback
         */
        ajax: function (action, data, successCallback, errorCallback) {
            data.action = action;
            data.nonce  = berpAdmin.nonce;

            $.ajax(
                {
                    url: berpAdmin.ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            if (typeof successCallback === 'function') {
                                successCallback(response.data);
                            }
                        } else {
                            if (typeof errorCallback === 'function') {
                                    errorCallback(response.data);
                            } else {
                                alert(response.data.message || berpAdmin.strings.error);
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        if (typeof errorCallback === 'function') {
                            errorCallback({message: error});
                        } else {
                            alert(berpAdmin.strings.error);
                        }
                    }
                }
            );
        },

        /**
         * Show loading spinner
         *
         * @param {jQuery} $element - Element to show spinner on
         */
        showSpinner: function ($element) {
            $element.addClass('berp-loading');
            $element.append('<span class="berp-spinner"></span>');
        },

        /**
         * Hide loading spinner
         *
         * @param {jQuery} $element - Element to hide spinner on
         */
        hideSpinner: function ($element) {
            $element.removeClass('berp-loading');
            $element.find('.berp-spinner').remove();
        },

        /**
         * Show notice
         *
         * @param {string} message - Notice message
         * @param {string} type - Notice type (success, error, warning, info)
         */
        showNotice: function (message, type) {
            type = type || 'info';

            const noticeClass = 'berp-notice berp-notice-' + type;
            const $notice     = $('<div class="' + noticeClass + '">' + message + '</div>');

            $('.wrap h1').after($notice);

            // Auto-remove after 5 seconds.
            setTimeout(
                function () {
                    $notice.fadeOut(
                        300,
                        function () {
                             $(this).remove();
                        }
                    );
                },
                5000
            );
        },

        /**
         * Handle portal credential resend button.
         */
        initPortalAccess: function () {
            $(document).on(
                'click',
                '.berp-resend-credentials',
                function (e) {
                    e.preventDefault();

                    const $button  = $(this);
                    const $actions = $button.closest('.berp-portal-actions');
                    const $spinner = $actions.find('.spinner');
                    const $status  = $actions.find('.berp-credentials-status');
                    const empId    = $button.data('employee-id');

                    if (! empId) {
                        alert(berpAdmin.strings.error || 'Invalid employee ID');
                        return;
                    }

                    // Disable button and show spinner.
                    $button.prop('disabled', true);
                    $spinner.addClass('is-active');
                    $status.html('');

                    BerpAdmin.ajax(
                        'berp_resend_credentials',
                        { employee_id: empId },
                        function (data) {
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                            $status.html('<span class="berp-success">' + data.message + '</span>');
                            setTimeout(
                                function () {
                                    $status.fadeOut(
                                        300,
                                        function () {
                                            $(this).html('').show();
                                        }
                                    );
                                },
                                5000
                            );
                        },
                        function (data) {
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                            $status.html('<span class="berp-error">' + data.message + '</span>');
                        }
                    );
                }
            );

            // Handle client document repeater
            let clientDocIndex = $('#berp-client-documents-wrapper .berp-document-row').length;

            $(document).on('click', '#berp-add-client-document', function(e) {
                e.preventDefault();
                const template = $('#berp-client-document-template').html();
                const newRow = template.replace(/\{\{INDEX\}\}/g, clientDocIndex);
                $('#berp-client-documents-wrapper').append(newRow);
                clientDocIndex++;
            });

            $(document).on('click', '.berp-remove-document', function(e) {
                e.preventDefault();
                if (confirm(berpAdmin.strings.confirm_delete || 'Are you sure you want to remove this document?')) {
                    $(this).closest('.berp-document-row').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        },

        /**
         * Attendance page logic (bulk + quick entry).
         */
        initAttendance: function () {
            if (typeof window.berpAttendance === 'undefined') {
                return;
            }

            const $page        = $('.berp-attendance-page');
            if (! $page.length) {
                return;
            }

            const restBase     = (window.berpAttendance.restUrl || '').replace(/\/$/, '');
            const restNonce    = window.berpAttendance.nonce || '';
            const settings     = window.berpAttendance.settings || {};
            const $bulkForm    = $('#berp-bulk-attendance-form');
            const $quickForm   = $('#berp-quick-attendance-form');
            const $quickInput  = $('#berp-quick-employee-input');
            const $quickId     = $('#berp-quick-employee-id');
            const $defaultOT   = $bulkForm.find('input[name="default_overtime"]');
            const $viewForm    = $('#berp-attendance-view');
            const $viewEmployee = $('#berp-view-employee');
            const $viewMonth    = $('#berp-view-month');
            const $viewLoad     = $('#berp-view-load');
            const $viewSpinner  = $('#berp-view-spinner');
            const $viewBody     = $('#berp-view-body');
            const $list        = $('#berp-attendance-list');
            const $progress    = $('#berp-selection-progress');
            const $search      = $('#berp-employee-search');
            const $selectAll   = $('#berp-select-all');
            const $deselectAll = $('#berp-deselect-all');
            const $navTabs     = $('.nav-tab-wrapper .nav-tab');
            const $bulkSite    = $bulkForm.find('select[name="site_id"]');

            const siteSelections = window.berpAttendance.siteSelections = window.berpAttendance.siteSelections || {};

            // Preserve original row order for stable sorting.
            $list.find('.berp-attendance-row').each(function (idx) {
                $(this).data('berpOrder', idx);
            });

            const headers = {
                'X-WP-Nonce': restNonce,
                'Content-Type': 'application/json'
            };

            const resolveQuickEmployee = function () {
                const val = ($quickInput.val() || '').trim();
                if (! val) {
                    $quickId.val('');
                    return 0;
                }

                let matchId = 0;
                const $opt = $('#berp-quick-employee-list option').filter(function () {
                    return this.value === val;
                }).first();

                if ($opt.length && $opt.data('id')) {
                    matchId = parseInt($opt.data('id'), 10) || 0;
                }

                if (! matchId && Array.isArray(window.berpAttendance.employees)) {
                    const lower = val.toLowerCase();
                    const found = window.berpAttendance.employees.find(function (emp) {
                        const label = (emp.code + ' - ' + emp.name).toLowerCase();
                        return label.indexOf(lower) !== -1;
                    });
                    matchId = found ? parseInt(found.id, 10) : 0;
                }

                $quickId.val(matchId || '');
                return matchId;
            };

            const renderViewRecords = function (records, start, end) {
                if (! $viewBody.length) {
                    return;
                }
                $viewBody.empty();
                if (! records || ! records.length) {
                    $viewBody.append('<tr><td colspan="6">' + (window.berpAttendance.strings && window.berpAttendance.strings.no_records ? window.berpAttendance.strings.no_records : 'No attendance found for the selected range.') + '</td></tr>');
                    return;
                }

                records.forEach(function (item) {
                    const editBtn = '<button type="button" class="button button-small berp-edit-attendance" data-id="' + (item.id || 0) + '" data-date="' + (item.date || '') + '" data-employee-id="' + (item.employee_id || 0) + '" data-site-id="' + (item.site_id || 0) + '" data-overtime="' + (item.overtime || 0) + '" data-notes="' + (item.notes || '') + '">Edit</button>';
                    const deleteBtn = '<button type="button" class="button button-small button-link-delete berp-delete-attendance" data-id="' + (item.id || 0) + '">Delete</button>';

                    const row = '<tr data-attendance-id="' + (item.id || 0) + '">' +
                        '<td>' + (item.date || '') + '</td>' +
                        '<td>' + (item.employee_code ? item.employee_code + ' - ' : '') + (item.employee_name || '') + '</td>' +
                        '<td>' + (item.site || '') + '</td>' +
                        '<td>' + (item.overtime || 0) + '</td>' +
                        '<td>' + (item.notes || '') + '</td>' +
                        '<td>' + editBtn + ' ' + deleteBtn + '</td>' +
                        '</tr>';
                    $viewBody.append(row);
                });
            };

            const loadViewRecords = function () {
                if (! $viewLoad.length) {
                    return;
                }

                const employeeId = parseInt($viewEmployee.val(), 10) || 0;
                const month      = ($viewMonth.val() || '').trim();

                $viewSpinner.addClass('is-active');
                $viewBody.html('<tr><td colspan="6">Loading...</td></tr>');

                fetch(restBase + '/list', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ employee_id: employeeId, month: month })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        $viewSpinner.removeClass('is-active');
                        if (data && data.success) {
                            renderViewRecords(data.records, data.start, data.end);

                            // Populate totals when a single employee is selected.
                            try {
                                const totals = data.totals || null;
                                if (employeeId > 0 && totals) {
                                    $('#berp-view-total-overtime').text(totals.overtime || 0);
                                    $('#berp-view-total-days').text(totals.days || 0);
                                    $('#berp-view-total-holidays').text(totals.paid_holidays || 0);
                                    $('#berp-view-totals').addClass('is-visible');
                                } else {
                                    // Hide totals for "All employees" view.
                                    $('#berp-view-totals').removeClass('is-visible');
                                    $('#berp-view-total-overtime').text(0);
                                    $('#berp-view-total-days').text(0);
                                    $('#berp-view-total-holidays').text(0);
                                }
                            } catch (e) {
                                // Silent fail: ensure UI doesn't break.
                                $('#berp-view-totals').removeClass('is-visible');
                            }
                        } else {
                            $viewBody.html('<tr><td colspan="6">' + (data && data.message ? data.message : 'Unable to load attendance.') + '</td></tr>');
                            $('#berp-view-totals').removeClass('is-visible');
                        }
                    })
                    .catch(function () {
                        $viewSpinner.removeClass('is-active');
                        $viewBody.html('<tr><td colspan="6">Error loading attendance.</td></tr>');
                    });
            };

            const getSelectedEmployees = function () {
                const selected = [];
                $list.find('input[type="checkbox"]:checked').each(function () {
                    selected.push(parseInt($(this).val(), 10));
                });
                return selected;
            };

            const updateProgress = function () {
                const total    = $list.find('.berp-attendance-row').length;
                const selected = getSelectedEmployees().length;
                if ($progress.length) {
                    $progress.text(selected + '/' + total + ' ' + (window.berpAttendance.strings ? window.berpAttendance.strings.selected || '' : 'selected'));
                }
            };

            const sortAttendanceRows = function () {
                const $rows = $list.find('.berp-attendance-row').detach();

                $rows.sort(function (a, b) {
                    const $a = $(a);
                    const $b = $(b);

                    const aChecked = $a.find('input[type="checkbox"]').is(':checked') ? 1 : 0;
                    const bChecked = $b.find('input[type="checkbox"]').is(':checked') ? 1 : 0;
                    if (aChecked !== bChecked) {
                        return bChecked - aChecked; // checked first
                    }

                    const aOrder = parseInt($a.data('berpOrder'), 10) || 0;
                    const bOrder = parseInt($b.data('berpOrder'), 10) || 0;
                    return aOrder - bOrder;
                });

                $list.append($rows);
            };

            // Track and propagate default OT to rows that haven't been manually overridden.
            const $rowOTInputs = $list.find('.berp-attendance-row input[type="number"]');
            $rowOTInputs.on('input', function () {
                $(this).data('userSet', true);
            });

            const applyDefaultOvertime = function () {
                const newDefault = parseFloat($defaultOT.val());
                const useDefault = Number.isNaN(newDefault) ? 0 : newDefault;

                $rowOTInputs.each(function () {
                    const $input     = $(this);
                    const userSet    = $input.data('userSet');
                    const currentVal = parseFloat($input.val());

                    // Update rows only if not manually changed.
                    if (! userSet || Number.isNaN(currentVal)) {
                        $input.val(useDefault);
                    }
                });
            };

            // Initial sync and on default change.
            applyDefaultOvertime();
            $defaultOT.on('change keyup', applyDefaultOvertime);

            const filterList = function () {
                const term = ($search.val() || '').toLowerCase();
                $list.find('.berp-attendance-row').each(function () {
                    const $row  = $(this);
                    const match = $row.data('employee-name').toLowerCase().indexOf(term) !== -1;
                    $row.toggle(match);
                });
                updateProgress();
            };

            const applySiteSelection = function (siteId) {
                const key = String(parseInt(siteId, 10) || 0);
                if (! key || key === '0') {
                    return false;
                }

                const remembered = siteSelections[key];
                if (! Array.isArray(remembered) || ! remembered.length) {
                    return false;
                }

                const allowed = new Set(remembered.map(function (id) {
                    return parseInt(id, 10);
                }).filter(function (id) {
                    return id > 0;
                }));

                if (! allowed.size) {
                    return false;
                }

                let matched = 0;
                $list.find('.berp-attendance-row').each(function () {
                    const $row = $(this);
                    const id   = parseInt($row.data('employee-id'), 10);
                    const shouldCheck = allowed.has(id);
                    $row.find('input[type="checkbox"]').prop('checked', shouldCheck);
                    if (shouldCheck) {
                        matched++;
                    }
                });

                if (! matched) {
                    $list.find('input[type="checkbox"]').prop('checked', true);
                    updateProgress();
                    return false;
                }

                updateProgress();
                sortAttendanceRows();
                return true;
            };

            const markDuplicates = function () {
                const date       = $bulkForm.find('input[name="date"]').val();
                const employees  = $list.find('.berp-attendance-row').map(function () {
                    return $(this).data('employee-id');
                }).get();

                if (! date || ! employees.length) {
                    return;
                }

                fetch(restBase + '/check-existing', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ date: date, employees: employees })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        $list.find('.berp-duplicate-flag').text('');
                        $list.find('.berp-attendance-row').removeClass('berp-has-duplicate');
                        if (data && data.success && Array.isArray(data.existing)) {
                            data.existing.forEach(function (empId) {
                                const $row = $list.find('.berp-attendance-row[data-employee-id="' + empId + '"]');
                                $row.addClass('berp-has-duplicate');
                                $row.find('.berp-duplicate-flag').text('• ' + (window.berpAttendance.strings ? window.berpAttendance.strings.duplicate || 'Already logged' : 'Already logged'));
                            });
                        }
                    })
                    .catch(function () {
                        // Silent fail to avoid blocking UI.
                    });
            };

            const submitBulk = function () {
                const $spinner = $bulkForm.find('.spinner');
                const $message = $bulkForm.find('.berp-response-message');
                $spinner.addClass('is-active');
                $message.text('');

                const date      = $bulkForm.find('input[name="date"]').val();
                const siteId    = parseInt($bulkForm.find('select[name="site_id"]').val(), 10) || 0;
                const defaultOT = parseFloat($bulkForm.find('input[name="default_overtime"]').val()) || 0;
                const override  = $bulkForm.find('input[name="override_duplicates"]').is(':checked');
                const selected  = getSelectedEmployees();

                if (! date) {
                    alert(window.berpAttendance.strings.select_date || 'Select a date');
                    $spinner.removeClass('is-active');
                    return;
                }
                if (settings.require_site && ! siteId) {
                    alert(window.berpAttendance.strings.select_site || 'Select a site');
                    $spinner.removeClass('is-active');
                    return;
                }
                if (! selected.length) {
                    alert(window.berpAttendance.strings.select_employee || 'Select at least one employee');
                    $spinner.removeClass('is-active');
                    return;
                }

                const payload = {
                    date: date,
                    site_id: siteId,
                    override_duplicates: override,
                    attendance: []
                };

                $list.find('.berp-attendance-row').each(function () {
                    const $row        = $(this);
                    const employeeId  = parseInt($row.data('employee-id'), 10);
                    const checked     = $row.find('input[type="checkbox"]').is(':checked');
                    const overtimeVal = parseFloat($row.find('input[type="number"]').val());
                    const overtime    = Number.isNaN(overtimeVal) ? defaultOT : overtimeVal;
                    if (checked) {
                        payload.attendance.push({
                            employee_id: employeeId,
                            overtime: overtime,
                            notes: ''
                        });
                    }
                });

                fetch(restBase + '/bulk-log', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify(payload)
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            $message.text(data.message || window.berpAttendance.strings.save_success);
                            if (siteId && selected.length) {
                                siteSelections[String(siteId)] = selected;
                            }
                        } else {
                            $message.text((data && data.message) || window.berpAttendance.strings.save_error);
                        }
                        $spinner.removeClass('is-active');
                        markDuplicates();
                    })
                    .catch(function () {
                        $spinner.removeClass('is-active');
                        $message.text(window.berpAttendance.strings.save_error || 'Error');
                    });
            };

            const submitQuick = function () {
                const $spinner = $quickForm.find('.spinner');
                const $message = $quickForm.find('.berp-response-message');
                $spinner.addClass('is-active');
                $message.text('');

                const date      = $quickForm.find('input[name="date"]').val();
                const siteId    = parseInt($quickForm.find('select[name="site_id"]').val(), 10) || 0;
                const employee  = resolveQuickEmployee();
                const overtime  = parseFloat($quickForm.find('input[name="overtime"]').val()) || 0;
                const notes     = $quickForm.find('textarea[name="notes"]').val() || '';

                if (! date) {
                    alert(window.berpAttendance.strings.select_date || 'Select a date');
                    $spinner.removeClass('is-active');
                    return;
                }
                if (settings.require_site && ! siteId) {
                    alert(window.berpAttendance.strings.select_site || 'Select a site');
                    $spinner.removeClass('is-active');
                    return;
                }
                if (! employee) {
                    alert(window.berpAttendance.strings.no_match || window.berpAttendance.strings.select_employee_q || 'Select an employee');
                    $spinner.removeClass('is-active');
                    return;
                }

                const payload = {
                    date: date,
                    site_id: siteId,
                    employee_id: employee,
                    overtime: overtime,
                    notes: notes
                };

                // Check if an attendance already exists; if yes, edit; else log.
                fetch(restBase + '/check-existing', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ date: date, employees: [employee] })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (check) {
                        const records     = check && check.records ? check.records : {};
                        const attendanceId = records[String(employee)] || records[employee] || 0;
                        const endpoint    = attendanceId ? '/edit' : '/log';
                        const body        = attendanceId ? Object.assign({}, payload, { attendance_id: attendanceId }) : payload;

                        return fetch(restBase + endpoint, {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify(body)
                        }).then(function (response) { return response.json(); });
                    })
                    .then(function (data) {
                        if (data && data.success) {
                            $message.text(data.message || window.berpAttendance.strings.save_success);
                            $quickInput.val('');
                            $quickId.val('');
                            $quickForm.find('textarea[name="notes"]').val('');
                        } else {
                            $message.text((data && data.message) || window.berpAttendance.strings.save_error);
                        }
                        $spinner.removeClass('is-active');
                    })
                    .catch(function () {
                        $spinner.removeClass('is-active');
                        $message.text(window.berpAttendance.strings.save_error || 'Error');
                    });
            };

            // Tabs.
            $navTabs.on('click', function (e) {
                e.preventDefault();
                const target = $(this).data('berp-tab');
                $navTabs.removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.berp-tab-panel').removeClass('is-active');
                $('#berp-attendance-' + target).addClass('is-active');

                if (target === 'view') {
                    loadViewRecords();
                }
            });

            // Select / deselect.
            $selectAll.on('click', function (e) {
                e.preventDefault();
                $list.find('input[type="checkbox"]').prop('checked', true);
                updateProgress();
                sortAttendanceRows();
            });
            $deselectAll.on('click', function (e) {
                e.preventDefault();
                $list.find('input[type="checkbox"]').prop('checked', false);
                updateProgress();
                sortAttendanceRows();
            });

            // Checkbox change updates progress.
            $list.on('change', 'input[type="checkbox"]', function () {
                updateProgress();
                sortAttendanceRows();
            });

            // Search filter with debouncing.
            $search.on('keyup change', debounce(filterList, 300));

            // Date change triggers duplicate check.
            $bulkForm.on('change', 'input[name="date"]', markDuplicates);

            // Site change can restore last used employee selection for that site.
            $bulkSite.on('change', function () {
                if (! applySiteSelection($(this).val())) {
                    $list.find('input[type="checkbox"]').prop('checked', true);
                    updateProgress();
                    sortAttendanceRows();
                }
            });

            // Submit handlers.
            $bulkForm.on('submit', function (e) {
                e.preventDefault();
                submitBulk();
            });

            $quickForm.on('submit', function (e) {
                e.preventDefault();
                submitQuick();
            });

            $quickInput.on('change keyup', resolveQuickEmployee);

            // View tab.
            $viewLoad.on('click', function (e) {
                e.preventDefault();
                loadViewRecords();
            });

            // Edit attendance button.
            $(document).on('click', '.berp-edit-attendance', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const attendanceId = parseInt($btn.data('id'), 10);
                const date = $btn.data('date');
                const employeeId = parseInt($btn.data('employee-id'), 10);
                const siteId = parseInt($btn.data('site-id'), 10) || 0;
                const overtime = parseFloat($btn.data('overtime')) || 0;
                const notes = $btn.data('notes') || '';

                // Prompt for new values (simple approach - could be enhanced with modal).
                const newOvertimeStr = prompt('Edit Overtime Hours:', overtime);
                if (newOvertimeStr === null) {
                    return; // User cancelled.
                }
                const newOvertime = parseFloat(newOvertimeStr) || 0;

                const newNotes = prompt('Edit Notes:', notes);
                if (newNotes === null) {
                    return; // User cancelled.
                }

                // Send edit request.
                fetch(restBase + '/edit', {
                    method: 'PUT',
                    headers: headers,
                    body: JSON.stringify({
                        attendance_id: attendanceId,
                        date: date,
                        site_id: siteId,
                        overtime: newOvertime,
                        notes: newNotes
                    })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            alert(data.message || 'Attendance updated successfully.');
                            loadViewRecords(); // Reload the table.
                        } else {
                            alert((data && data.message) || 'Failed to update attendance.');
                        }
                    })
                    .catch(function () {
                        alert('Error updating attendance.');
                    });
            });

            // Delete attendance button.
            $(document).on('click', '.berp-delete-attendance', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const attendanceId = parseInt($btn.data('id'), 10);

                if (!confirm('Are you sure you want to delete this attendance record?')) {
                    return;
                }

                // Send delete request.
                fetch(restBase + '/delete?attendance_id=' + attendanceId, {
                    method: 'DELETE',
                    headers: headers
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            alert(data.message || 'Attendance deleted successfully.');
                            loadViewRecords(); // Reload the table.
                        } else {
                            alert((data && data.message) || 'Failed to delete attendance.');
                        }
                    })
                    .catch(function () {
                        alert('Error deleting attendance.');
                    });
            });

            // Initial.
            updateProgress();
            markDuplicates();
            if (window.berpAttendance.lastSite) {
                applySiteSelection(window.berpAttendance.lastSite);
            } else {
                sortAttendanceRows();
            }
        }
        ,

        /**
         * Salary formula builder interactions.
         */
        initFormulaBuilder: function () {
            if (typeof window.berpFormulaBuilder === 'undefined') {
                return;
            }

            const $page      = $('.berp-formula-builder');
            const $variables = $('#berp-formula-variables');
            const $template  = $('#berp-variable-row-template');
            const $formula   = $('textarea[name="berp_formula[formula]"]');
            const $preview   = $('#berp-formula-preview');

            if (! $page.length) {
                return;
            }

            let nextIndex = $variables.find('.berp-variable-row').length;

            $page.on('click', '.berp-add-variable', function (e) {
                e.preventDefault();
                if (! $template.length) {
                    return;
                }
                const row = $template.html().replace(/\\{\\{index\\}\\}/g, nextIndex);
                $variables.append(row);
                nextIndex++;
            });

            $page.on('click', '.berp-remove-variable', function (e) {
                e.preventDefault();
                const $row = $(this).closest('tr');
                if ($variables.find('.berp-variable-row').length <= 1) {
                    alert('At least one variable is required.');
                    return;
                }
                $row.remove();
            });

            $page.on('click', '.berp-formula-template', function (e) {
                e.preventDefault();
                const tplFormula = $(this).data('formula') || '';
                if (tplFormula) {
                    $formula.val(tplFormula);
                }
            });

            $page.on('click', '.berp-test-formula', function (e) {
                e.preventDefault();
                if (! $formula.length) {
                    return;
                }

                const formula = ($formula.val() || '').trim();
                if (! formula) {
                    alert('Enter a formula to test.');
                    return;
                }

                const variables = {};
                $variables.find('.berp-variable-row').each(function () {
                    const $row    = $(this);
                    const key     = ($row.find('input[name*=\"[key]\"]').val() || '').trim();
                    const sample  = parseFloat($row.find('input[name*=\"[sample]\"]').val());
                    if (key) {
                        variables[key] = Number.isNaN(sample) ? 0 : sample;
                    }
                });

                $preview.removeClass('is-error').text('Testing...');

                $.ajax(
                    {
                        url: berpFormulaBuilder.ajaxurl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'berp_preview_formula',
                            nonce: berpFormulaBuilder.nonce,
                            formula: formula,
                            variables: JSON.stringify(variables)
                        },
                        success: function (response) {
                            if (response && response.success) {
                                // Check for Infinity (Bug #3 safety net)
                                if (response.data.result === Infinity ||
                                    response.data.result === 'Infinity' ||
                                    response.data.result === -Infinity ||
                                    response.data.result === '-Infinity') {
                                    $preview.addClass('is-error').text('Error: Division by zero detected.');
                                    return;
                                }

                                $preview.removeClass('is-error').text(
                                    (berpFormulaBuilder.strings && berpFormulaBuilder.strings.preview_success
                                        ? berpFormulaBuilder.strings.preview_success + ': '
                                        : '') + response.data.result
                                );
                            } else {
                                const msg = response && response.data && response.data.message
                                    ? response.data.message
                                    : (berpFormulaBuilder.strings
                                        ? berpFormulaBuilder.strings.preview_failed
                                        : 'Formula test failed');
                                $preview.addClass('is-error').text(msg);
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            // Enhanced diagnostic logging (only in debug mode)
                            if (berpDebug) {
                                console.error('AJAX Preview Error:', {
                                    status: jqXHR.status,
                                    statusText: jqXHR.statusText,
                                    responseText: jqXHR.responseText.substring(0, 200),
                                    textStatus: textStatus,
                                    errorThrown: errorThrown,
                                    url: berpFormulaBuilder.ajaxurl,
                                    hasNonce: !!berpFormulaBuilder.nonce
                                });
                            }

                            let msg = berpFormulaBuilder.strings
                                ? berpFormulaBuilder.strings.preview_failed
                                : 'Formula test failed';

                            // Specific error messages
                            if (jqXHR.status === 403) {
                                msg += ' - Permission denied. Your session may have expired. Please refresh the page.';
                            } else if (jqXHR.status === 0) {
                                msg += ' - Network error. Check browser console for details.';
                            } else if (jqXHR.status === 500) {
                                msg += ' - Server error. Check PHP error logs.';
                            } else if (jqXHR.status === 400) {
                                msg += ' - Invalid request. Check formula syntax.';
                            }

                            $preview.addClass('is-error').text(msg);
                        }
                    }
                );
            });
        }
    };

    /**
     * Document ready
     */
    $(document).ready(
        function () {
            BerpAdmin.init();
        }
    );

    // Make BerpAdmin globally accessible.
    window.BerpAdmin = BerpAdmin;

})(jQuery);




