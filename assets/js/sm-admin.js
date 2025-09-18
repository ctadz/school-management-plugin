jQuery(document).ready(function ($) {
    // Logo uploader
    let frame;

    $('#sm_logo_upload').on('click', function (e) {
        e.preventDefault();

        // If media frame already exists, reopen it
        if (frame) {
            frame.open();
            return;
        }

        // Create media frame
        frame = wp.media({
            title: sm_i18n.selectLogo || 'Select a logo',
            button: {
                text: sm_i18n.selectLogo || 'Select a logo',
            },
            multiple: false
        });

        // On select
        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            $('#sm_logo').val(attachment.url);
            $('#sm_logo_preview').attr('src', attachment.url).show();

            alert(sm_i18n.uploadSuccess || 'Upload successful!');
        });

        frame.open();
    });

    // Handle errors (if any AJAX/validation in future)
    $(document).on('sm_upload_error', function () {
        alert(sm_i18n.uploadError || 'Upload failed. Please try again.');
    });
});
