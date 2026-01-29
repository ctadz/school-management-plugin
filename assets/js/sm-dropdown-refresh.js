/**
 * School Management - Dropdown Refresh Feature
 *
 * Allows refreshing dependent dropdowns via AJAX without page reload.
 * Preserves current form data while fetching latest options.
 *
 * @package SchoolManagement
 * @since 0.6.0
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Handle refresh button click
     */
    $(document).on('click', '.sm-refresh-dropdown', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var entity = $btn.data('entity');
        var targetId = $btn.data('target');
        var $dropdown = $('#' + targetId);
        var currentValue = $dropdown.val(); // Preserve current selection

        // Prevent double-clicks while loading
        if ($btn.hasClass('spinning')) {
            return;
        }

        $btn.addClass('spinning');
        $btn.prop('disabled', true);

        $.ajax({
            url: smAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sm_refresh_dropdown',
                nonce: smAjax.refreshNonce,
                entity: entity
            },
            success: function(response) {
                if (response.success && response.data.options) {
                    // Store the first "Select..." option
                    var $firstOption = $dropdown.find('option:first').clone();

                    // Clear dropdown and restore first option
                    $dropdown.empty().append($firstOption);

                    // Add fresh options from server
                    $.each(response.data.options, function(i, opt) {
                        var $option = $('<option>', {
                            value: opt.id,
                            text: opt.label
                        });

                        // Add any extra data attributes if present
                        if (opt.extra) {
                            $.each(opt.extra, function(key, val) {
                                $option.data(key, val);
                            });
                        }

                        $dropdown.append($option);
                    });

                    // Restore previous selection if it still exists
                    if (currentValue && $dropdown.find('option[value="' + currentValue + '"]').length) {
                        $dropdown.val(currentValue);
                    }

                    // Show brief success indication
                    $btn.addClass('success');
                    setTimeout(function() {
                        $btn.removeClass('success');
                    }, 1500);

                    // Trigger change event in case other scripts need to react
                    $dropdown.trigger('change');
                } else {
                    // Show error state
                    $btn.addClass('error');
                    setTimeout(function() {
                        $btn.removeClass('error');
                    }, 2000);

                    if (response.data && response.data.message) {
                        console.error('SM Dropdown Refresh Error:', response.data.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('SM Dropdown Refresh AJAX Error:', error);
                $btn.addClass('error');
                setTimeout(function() {
                    $btn.removeClass('error');
                }, 2000);
            },
            complete: function() {
                $btn.removeClass('spinning');
                $btn.prop('disabled', false);
            }
        });
    });
});
