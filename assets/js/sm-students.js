jQuery(document).ready(function($) {
    let frame;

    $('#sm_student_picture_box').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select or Upload Student Picture',
            button: { text: 'Use this picture' },
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
