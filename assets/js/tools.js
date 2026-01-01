/**
 * BuildErp Tools JavaScript
 *
 * Handles interactivity for admin tools page
 *
 * @package BuildErp
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Schema Validator Tool
     */
    const SchemaValidator = {
        init: function() {
            $('#berp-run-validator').on('click', this.runValidation.bind(this));
            $(document).on('click', '.berp-quick-fix-btn', this.applyQuickFix.bind(this));
        },

        runValidation: function() {
            const $button = $('#berp-run-validator');
            const $results = $('#berp-validator-results');
            const $loading = $('#berp-validator-loading');

            // Show loading
            $button.prop('disabled', true);
            $results.hide();
            $loading.show();

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_run_validator',
                    nonce: berpTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SchemaValidator.displayResults(response.data);
                    } else {
                        alert(berpTools.strings.error);
                    }
                },
                error: function() {
                    alert(berpTools.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        },

        displayResults: function(data) {
            const $results = $('#berp-validator-results');
            const $scoreBar = $('.berp-score-bar');
            const $scoreText = $('.berp-score-text');
            const $scoreDescription = $('.berp-score-description');
            const $issuesList = $('#berp-issues-list');

            // Update score
            $scoreBar.css('width', data.score + '%');
            $scoreBar.attr('data-score', Math.floor(data.score / 10) * 10);
            $scoreText.text(data.score + '%');
            $scoreDescription.text(data.summary);

            // Display issues
            if (data.issues.length > 0) {
                let issuesHtml = '';
                data.issues.forEach(function(issue) {
                    issuesHtml += SchemaValidator.buildIssueHtml(issue);
                });
                $issuesList.html(issuesHtml);
            } else {
                $issuesList.html('<div class="berp-no-issues"><span class="dashicons dashicons-yes-alt"></span><p>No issues found! Your schema is fully compliant.</p></div>');
            }

            $results.show();
        },

        buildIssueHtml: function(issue) {
            let html = '<div class="berp-issue-item ' + issue.severity + '">';
            html += '<div class="berp-issue-header">';
            html += '<span class="berp-issue-title">' + issue.title + '</span>';
            html += '<span class="berp-issue-severity ' + issue.severity + '">' + issue.severity + '</span>';
            html += '</div>';
            html += '<div class="berp-issue-description">' + issue.description + '</div>';

            if (issue.fixable) {
                html += '<div class="berp-issue-actions">';
                html += '<button type="button" class="button button-small berp-quick-fix-btn" data-issue-type="' + issue.type + '" data-issue-data=\'' + JSON.stringify(issue.data) + '\'>';
                html += '<span class="dashicons dashicons-admin-tools"></span> Quick Fix';
                html += '</button>';
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        applyQuickFix: function(e) {
            const $button = $(e.currentTarget);
            const issueType = $button.data('issue-type');
            const issueData = $button.data('issue-data');

            if (!confirm('Apply quick fix for this issue?')) {
                return;
            }

            $button.prop('disabled', true).text(berpTools.strings.fixing);

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_quick_fix',
                    nonce: berpTools.nonce,
                    issue_type: issueType,
                    issue_data: issueData
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('.berp-issue-item').fadeOut();
                        alert(berpTools.strings.success);
                    } else {
                        alert(response.data.message || berpTools.strings.error);
                    }
                },
                error: function() {
                    alert(berpTools.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Quick Fix');
                }
            });
        }
    };

    /**
     * System Info Tool
     */
    const SystemInfo = {
        init: function() {
            $('#berp-export-system-info').on('click', this.exportReport.bind(this));
            $('#berp-copy-system-info').on('click', this.copyToClipboard.bind(this));
        },

        exportReport: function() {
            const $button = $('#berp-export-system-info');
            $button.prop('disabled', true);

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_export_system_info',
                    nonce: berpTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SystemInfo.downloadReport(response.data.report);
                    } else {
                        alert(berpTools.strings.error);
                    }
                },
                error: function() {
                    alert(berpTools.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        downloadReport: function(content) {
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'builderp-system-report-' + new Date().getTime() + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },

        copyToClipboard: function() {
            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_export_system_info',
                    nonce: berpTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const textarea = document.createElement('textarea');
                        textarea.value = response.data.report;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        alert('System info copied to clipboard!');
                    } else {
                        alert(berpTools.strings.error);
                    }
                },
                error: function() {
                    alert(berpTools.strings.error);
                }
            });
        }
    };

    /**
     * Schema Browser Tool
     */
    const SchemaBrowser = {
        init: function() {
            $('#berp-meta-search').on('input', this.filterMetaKeys.bind(this));
            $('#berp-clear-search').on('click', this.clearSearch.bind(this));
            $(document).on('click', '.berp-find-usage', this.findUsage.bind(this));
            $(document).on('click', '.berp-view-samples', this.viewSamples.bind(this));
        },

        filterMetaKeys: function() {
            const searchTerm = $('#berp-meta-search').val().toLowerCase();
            $('.berp-meta-card').each(function() {
                const metaKey = $(this).data('meta-key').toLowerCase();
                if (metaKey.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        clearSearch: function() {
            $('#berp-meta-search').val('');
            $('.berp-meta-card').show();
        },

        findUsage: function(e) {
            const $button = $(e.currentTarget);
            const metaKey = $button.data('meta-key');
            const $usageDiv = $button.closest('.berp-meta-card').find('.berp-usage-details');

            if ($usageDiv.is(':visible')) {
                $usageDiv.slideUp();
                return;
            }

            $usageDiv.html('<div class="berp-loading"><span class="spinner is-active"></span> Loading...</div>').slideDown();

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_search_meta_usage',
                    nonce: berpTools.nonce,
                    meta_key: metaKey
                },
                success: function(response) {
                    if (response.success) {
                        $usageDiv.html(SchemaBrowser.buildUsageHtml(response.data.usage));
                    } else {
                        $usageDiv.html('<p>Error loading usage data</p>');
                    }
                },
                error: function() {
                    $usageDiv.html('<p>Error loading usage data</p>');
                }
            });
        },

        viewSamples: function(e) {
            const $button = $(e.currentTarget);
            const metaKey = $button.data('meta-key');
            const $usageDiv = $button.closest('.berp-meta-card').find('.berp-usage-details');

            if ($usageDiv.is(':visible')) {
                $usageDiv.slideUp();
                return;
            }

            $usageDiv.html('<div class="berp-loading"><span class="spinner is-active"></span> Loading...</div>').slideDown();

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_search_meta_usage',
                    nonce: berpTools.nonce,
                    meta_key: metaKey
                },
                success: function(response) {
                    if (response.success && response.data.usage.samples) {
                        let html = '<h5>Sample Values:</h5><ul class="berp-usage-list">';
                        response.data.usage.samples.forEach(function(sample) {
                            html += '<li class="berp-usage-item"><div class="berp-sample-value">' + escapeHtml(sample) + '</div></li>';
                        });
                        html += '</ul>';
                        $usageDiv.html(html);
                    } else {
                        $usageDiv.html('<p>No samples available</p>');
                    }
                },
                error: function() {
                    $usageDiv.html('<p>Error loading samples</p>');
                }
            });
        },

        buildUsageHtml: function(usage) {
            let html = '<h5>Database Usage:</h5>';

            if (usage.database && usage.database.length > 0) {
                html += '<ul class="berp-usage-list">';
                usage.database.forEach(function(item) {
                    html += '<li class="berp-usage-item">';
                    html += '<strong>' + item.post_type + '</strong>: ';
                    html += '<a href="' + item.edit_link + '" target="_blank">' + item.post_title + '</a>';
                    html += '</li>';
                });
                html += '</ul>';
            } else {
                html += '<p>No database usage found</p>';
            }

            if (usage.files && usage.files.length > 0) {
                html += '<h5>File Usage:</h5>';
                html += '<ul class="berp-usage-list">';
                usage.files.forEach(function(file) {
                    html += '<li class="berp-usage-item"><code>' + file.file + '</code></li>';
                });
                html += '</ul>';
            }

            return html;
        }
    };

    /**
     * Helper Generator Tool
     */
    const HelperGenerator = {
        init: function() {
            $('#berp-generate-helper').on('click', this.generateHelper.bind(this));
            $('#berp-copy-code').on('click', this.copyCode.bind(this));
            $('#berp-download-code').on('click', this.downloadCode.bind(this));
        },

        generateHelper: function() {
            const metaKey = $('#berp-meta-key').val().trim();
            const dataType = $('#berp-data-type').val();
            const postType = $('#berp-post-type').val();

            if (!metaKey) {
                alert('Please enter a meta key');
                return;
            }

            if (!metaKey.startsWith('_berp_')) {
                alert('Meta key must start with _berp_');
                return;
            }

            const $button = $('#berp-generate-helper');
            $button.prop('disabled', true).text(berpTools.strings.generating);

            $.ajax({
                url: berpTools.ajaxurl,
                type: 'POST',
                data: {
                    action: 'berp_generate_helper',
                    nonce: berpTools.nonce,
                    meta_key: metaKey,
                    data_type: dataType,
                    post_type: postType
                },
                success: function(response) {
                    if (response.success) {
                        $('#berp-code-output code').text(response.data.code);
                        $('#berp-generated-code').slideDown();

                        // Scroll to generated code
                        $('html, body').animate({
                            scrollTop: $('#berp-generated-code').offset().top - 50
                        }, 500);
                    } else {
                        alert(berpTools.strings.error);
                    }
                },
                error: function() {
                    alert(berpTools.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-editor-code"></span> Generate Helper Functions');
                }
            });
        },

        copyCode: function() {
            const code = $('#berp-code-output code').text();
            const textarea = document.createElement('textarea');
            textarea.value = code;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Code copied to clipboard!');
        },

        downloadCode: function() {
            const code = $('#berp-code-output code').text();
            const metaKey = $('#berp-meta-key').val().trim().replace('_berp_', '');
            const blob = new Blob([code], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'berp-helper-' + metaKey + '.php';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    };

    /**
     * Utility function to escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Initialize all tools
     */
    $(document).ready(function() {
        SchemaValidator.init();
        SystemInfo.init();
        SchemaBrowser.init();
        HelperGenerator.init();
    });

})(jQuery);
