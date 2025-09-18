jQuery(document).ready(function ($) {
    let frame;

    $('.sm-upload-logo').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

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

    $(document).on('sm_upload_error', function () {
        alert(sm_i18n.uploadError || 'Upload failed. Please try again.');
    });
});
