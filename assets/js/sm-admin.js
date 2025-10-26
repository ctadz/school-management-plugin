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
            const previewHtml = 
                '<div style="padding: 15px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 10px;">' +
                    '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">' +
                        '<span class="dashicons dashicons-media-document" style="font-size: 24px; color: #d63638;"></span>' +
                        '<div style="flex: 1;">' +
                            '<strong>' + fileName + '</strong>' +
                            '<p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">PDF Document</p>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display: flex; gap: 5px;">' +
                        '<a href="' + fileUrl + '" target="_blank" class="button button-small">' +
                            '<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span> View' +
                        '</a>' +
                        '<a href="' + fileUrl + '" download class="button button-small">' +
                            '<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Download' +
                        '</a>' +
                        '<button type="button" class="button button-small sm-remove-file" data-target="' + targetField + '" style="color: #d63638;">' +
                            '<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Remove' +
                        '</button>' +
                    '</div>' +
                '</div>';
            
            const replaceButtonHtml = 
                '<button type="button" class="button sm-upload-file" data-target="' + targetField + '">' +
                    '<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Replace PDF' +
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
        if (!confirm('Are you sure you want to remove this file?')) {
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
        const uploadButtonHtml = 
            '<button type="button" class="button sm-upload-file" data-target="' + targetField + '">' +
                '<span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> Upload PDF' +
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
});