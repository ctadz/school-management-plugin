jQuery(document).ready(function($) {
    let frame;

    // Get localized strings with fallbacks
    var getStr = function(key, fallback) {
        return (typeof sm_i18n !== 'undefined' && sm_i18n[key]) ? sm_i18n[key] : fallback;
    };

    $('#sm_student_picture_box').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: getStr('selectStudentPicture', 'Select or Upload Student Picture'),
            button: { text: getStr('usePicture', 'Use this picture') },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#sm_student_picture').val(attachment.url);
            $('#sm_student_picture_preview')
                .attr('src', attachment.url)
                .show();
            $('#sm_student_picture_box span').hide();
        });

        frame.open();
    });
});
