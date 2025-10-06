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
    $('.sm-upload-file').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const targetField = button.data('target');
        const inputField = $('#' + targetField);

        if (frame) { frame.open(); return; }

        frame = wp.media({
            title: sm_i18n.selectFile || 'Select a file',
            button: { text: sm_i18n.useFile || 'Use this file' },
            multiple: false,
            library: {
                type: ['application/pdf']
            }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            alert(sm_i18n.uploadSuccess || 'Upload successful!');
        });

        frame.open();
    });

    // Handle errors
    $(document).on('sm_upload_error', function () {
        alert(sm_i18n.uploadError || 'Upload failed. Please try again.');
    });
});