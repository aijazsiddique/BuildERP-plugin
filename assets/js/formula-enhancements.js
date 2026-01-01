/**
 * Formula Builder Enhancements
 * Additional functionality for formula builder
 */

(function ($) {
    'use strict';

    // Wait for BerpAdmin to be available.
    $(document).ready(function () {
        const $page = $('.berp-formula-builder');
        if (!$page.length) {
            return;
        }

        const $formula = $('textarea[name="berp_formula[formula]"]');
        const $variables = $('#berp-formula-variables');

        // Toggle formula documentation.
        $page.on('click', '#berp-toggle-formula-docs', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $docs = $('#berp-formula-docs-container');

            if ($docs.is(':visible')) {
                $docs.slideUp();
                $btn.text('Show Formula Documentation');
            } else {
                $docs.slideDown();
                $btn.text('Hide Formula Documentation');
            }
        });

        // Update formula explanation on textarea change (debounced).
        let explanationTimeout;
        $formula.on('input', function () {
            clearTimeout(explanationTimeout);
            explanationTimeout = setTimeout(function () {
                const formula = $formula.val() || '';
                const variables = [];

                $variables.find('.berp-variable-row').each(function () {
                    const $row = $(this);
                    const key = $row.find('input[name*="[key]"]').val() || '';
                    const label = $row.find('input[name*="[label]"]').val() || key;
                    if (key) {
                        variables.push({ key: key, label: label });
                    }
                });

                // Simple client-side explanation.
                let explained = formula;
                variables.forEach(function (v) {
                    const regex = new RegExp('\\b' + v.key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'g');
                    explained = explained.replace(regex, '[' + v.label + ']');
                });

                // Replace operators.
                explained = explained.replace(/\//g, ' divided by ')
                    .replace(/\*/g, ' multiplied by ')
                    .replace(/\+/g, ' plus ')
                    .replace(/-/g, ' minus ');

                // Replace functions (case-insensitive).
                explained = explained.replace(/if\s*\(/gi, 'IF (')
                    .replace(/min\s*\(/gi, 'MINIMUM of (')
                    .replace(/max\s*\(/gi, 'MAXIMUM of (')
                    .replace(/round\s*\(/gi, 'ROUND (')
                    .replace(/abs\s*\(/gi, 'ABSOLUTE VALUE of (');

                // Replace comparison operators.
                explained = explained.replace(/==/g, ' equals ')
                    .replace(/!=/g, ' does not equal ')
                    .replace(/>=/g, ' is greater than or equal to ')
                    .replace(/<=/g, ' is less than or equal to ')
                    .replace(/>/g, ' is greater than ')
                    .replace(/</g, ' is less than ')
                    .replace(/&&/g, ' AND ')
                    .replace(/\|\|/g, ' OR ');

                $('#berp-formula-explanation-text').text(explained || 'No formula defined.');
            }, 500);
        });
    });

})(jQuery);
