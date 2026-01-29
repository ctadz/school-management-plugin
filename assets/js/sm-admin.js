jQuery(document).ready(function ($) {
    let frame;

    // --- School Logo Upload ---
    $('.sm-upload-logo').on('click', function (e) {
        e.preventDefault();

        if (frame) { frame.open(); return; }

        frame = wp.media({
            title: sm_i18n.selectLogo || 'Select a logo',
            button: { text: sm_i18n.selectLogo || 'Select a logo' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#sm_school_logo').val(attachment.url);

            if ($('#sm_school_logo_preview').length === 0) {
                $('<div class="sm-logo-preview" style="margin-top:10px;">' +
                  '<img id="sm_school_logo_preview" src="' + attachment.url + '" style="max-height:80px;" />' +
                  '</div>').insertAfter('#sm_school_logo');
            } else {
                $('#sm_school_logo_preview').attr('src', attachment.url);
            }

            alert(sm_i18n.uploadSuccess || 'Upload successful!');
        });

        frame.open();
    });

    // --- Student/Teacher Picture Upload via Box ---
    $('#sm_student_picture_box').on('click', function(e) {
        e.preventDefault();
        const inputField = $('#sm_student_picture');

        if (frame) { frame.open(); return; }

        frame = wp.media({
            title: sm_i18n.selectPicture || 'Select a picture',
            button: { text: sm_i18n.selectPicture || 'Select a picture' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);

            const img = $('#sm_student_picture_preview');
            img.attr('src', attachment.url).show();

            $('#sm_student_picture_box span').remove();

            alert(sm_i18n.uploadSuccess || 'Upload successful!');
        });

        frame.open();
    });

// --- PDF File Upload (for course descriptions) ---
    $(document).on('click', '.sm-upload-file', function(e) {
        e.preventDefault();
        const button = $(this);
        const targetField = button.data('target');
        const inputField = $('#' + targetField);

        const fileFrame = wp.media({
            title: sm_i18n.selectFile || 'Select a file',
            button: { text: sm_i18n.useFile || 'Use this file' },
            multiple: false,
            library: {
                type: ['application/pdf']
            }
        });

        fileFrame.on('select', function() {
            const attachment = fileFrame.state().get('selection').first().toJSON();
            const fileUrl = attachment.url;
            const fileName = attachment.filename;
            
            // Update the hidden field
            inputField.val(fileUrl);
            
            // Build the preview HTML
            const pdfDocLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.pdfDocument) ? sm_i18n.pdfDocument : 'PDF Document';
            const viewLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.view) ? sm_i18n.view : 'View';
            const downloadLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.download) ? sm_i18n.download : 'Download';
            const removeLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.remove) ? sm_i18n.remove : 'Remove';
            const replacePdfLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.replacePdf) ? sm_i18n.replacePdf : 'Replace PDF';

            const previewHtml =
                '<div style="padding: 15px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 10px;">' +
                    '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">' +
                        '<span class="dashicons dashicons-media-document" style="font-size: 24px; color: #d63638;"></span>' +
                        '<div style="flex: 1;">' +
                            '<strong>' + fileName + '</strong>' +
                            '<p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">' + pdfDocLabel + '</p>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display: flex; gap: 5px;">' +
                        '<a href="' + fileUrl + '" target="_blank" class="button button-small">' +
                            '<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span> ' + viewLabel +
                        '</a>' +
                        '<a href="' + fileUrl + '" download class="button button-small">' +
                            '<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> ' + downloadLabel +
                        '</a>' +
                        '<button type="button" class="button button-small sm-remove-file" data-target="' + targetField + '" style="color: #d63638;">' +
                            '<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> ' + removeLabel +
                        '</button>' +
                    '</div>' +
                '</div>';

            const replaceButtonHtml =
                '<button type="button" class="button sm-upload-file" data-target="' + targetField + '">' +
                    '<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> ' + replacePdfLabel +
                '</button>';
            
            // Get parent container
            const parentTd = button.closest('td');
            
            // Remove existing preview and buttons
            parentTd.find('.sm-upload-file').remove();
            parentTd.find('> div').not('p.description').remove();
            
            // Add new preview and replace button
            inputField.after(previewHtml + replaceButtonHtml);
            
            // Close the modal
            fileFrame.close();
        });

        fileFrame.open();
    });

    // --- Remove PDF File ---
    $(document).on('click', '.sm-remove-file', function(e) {
        e.preventDefault();

        // Confirm removal
        const confirmMsg = (typeof sm_i18n !== 'undefined' && sm_i18n.confirmRemoveFile) ? sm_i18n.confirmRemoveFile : 'Are you sure you want to remove this file?';
        if (!confirm(confirmMsg)) {
            return; // User cancelled
        }

        const button = $(this);
        const targetField = button.data('target');
        const inputField = $('#' + targetField);

        // Clear the field
        inputField.val('');

        // Get parent container
        const parentTd = button.closest('td');

        // Build upload button HTML
        const uploadPdfLabel = (typeof sm_i18n !== 'undefined' && sm_i18n.uploadPdf) ? sm_i18n.uploadPdf : 'Upload PDF';
        const uploadButtonHtml =
            '<button type="button" class="button sm-upload-file" data-target="' + targetField + '">' +
                '<span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> ' + uploadPdfLabel +
            '</button>';
        
        // Remove preview and buttons
        parentTd.find('.sm-upload-file').remove();
        parentTd.find('> div').not('p.description').remove();
        
        // Add upload button
        inputField.after(uploadButtonHtml);
    });

    // Handle errors
    $(document).on('sm_upload_error', function () {
        alert(sm_i18n.uploadError || 'Upload failed. Please try again.');
    });

    // --- Datepicker Initialization ---
    if (typeof $.fn.datepicker !== 'undefined' && typeof sm_i18n !== 'undefined' && sm_i18n.datepicker) {
        var dp = sm_i18n.datepicker;

        // Initialize datepicker on all elements with .sm-datepicker class
        $('.sm-datepicker').datepicker({
            dateFormat: dp.dateFormat || 'dd-mm-yy',
            changeMonth: true,
            changeYear: true,
            yearRange: '-100:+0', // Allow dates from 100 years ago to today
            maxDate: 0, // Cannot select future dates (for date of birth)
            dayNames: dp.dayNames || ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            dayNamesShort: dp.dayNamesShort || ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            dayNamesMin: dp.dayNamesMin || ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: dp.monthNames || ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            monthNamesShort: dp.monthNamesShort || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            prevText: dp.prevText || 'Prev',
            nextText: dp.nextText || 'Next',
            showButtonPanel: false
        });

        // Set placeholder from localized string
        $('.sm-datepicker').attr('placeholder', dp.placeholder || 'dd-mm-yyyy');
    }

    // --- Excel-style Column Filters ---

    // Toggle filter dropdown on trigger click
    $(document).on('click', '.sm-filter-trigger', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var filterType = $(this).data('filter');
        var $dropdown = $('#filter-dropdown-' + filterType);
        var isVisible = $dropdown.is(':visible');

        // Close all other dropdowns first
        $('.sm-filter-dropdown').hide();

        // Toggle current dropdown
        if (!isVisible) {
            $dropdown.show();
            // Focus search input if present
            $dropdown.find('.sm-filter-search-input').focus();
        }
    });

    // Make entire column header clickable for non-sortable filterable columns
    $(document).on('click', '.non-sortable.sm-filterable-column .sm-column-header', function(e) {
        // Don't trigger if clicking directly on the filter button (it has its own handler)
        if ($(e.target).closest('.sm-filter-trigger').length) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        // Find the filter trigger button in this column and get its filter type
        var $trigger = $(this).find('.sm-filter-trigger');
        var filterType = $trigger.data('filter');
        var $dropdown = $('#filter-dropdown-' + filterType);
        var isVisible = $dropdown.is(':visible');

        // Close all other dropdowns first
        $('.sm-filter-dropdown').hide();

        // Toggle current dropdown
        if (!isVisible) {
            $dropdown.show();
            // Focus search input if present
            $dropdown.find('.sm-filter-search-input').focus();
        }
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.sm-filterable-column').length) {
            $('.sm-filter-dropdown').hide();
        }
    });

    // Prevent dropdown from closing when clicking inside it
    $(document).on('click', '.sm-filter-dropdown', function(e) {
        e.stopPropagation();
    });

    // Filter search functionality
    $(document).on('input', '.sm-filter-search-input', function() {
        var searchTerm = $(this).val().toLowerCase();
        var $dropdown = $(this).closest('.sm-filter-dropdown');
        var $options = $dropdown.find('.sm-filter-options li');

        $options.each(function() {
            var $link = $(this).find('a');
            var searchText = $link.data('search') || $link.text().toLowerCase();

            // Always show "All" option
            if ($link.text().indexOf('(All') === 0 || $link.text().indexOf('(Tous') === 0) {
                $(this).removeClass('sm-hidden');
                return;
            }

            if (searchText.indexOf(searchTerm) !== -1) {
                $(this).removeClass('sm-hidden');
            } else {
                $(this).addClass('sm-hidden');
            }
        });
    });

    // Close dropdown on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.sm-filter-dropdown').hide();
        }
    });
});