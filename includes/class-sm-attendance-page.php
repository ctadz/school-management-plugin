<?php
/**
 * Teacher Attendance Page
 *
 * @package SchoolManagement
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Attendance_Page {

    /**
     * Initialize the attendance page
     */
    public static function init() {
        // Handle AJAX requests
        add_action( 'wp_ajax_sm_get_classes_for_date', array( __CLASS__, 'ajax_get_classes_for_date' ) );
        add_action( 'wp_ajax_sm_get_class_students', array( __CLASS__, 'ajax_get_class_students' ) );
        add_action( 'wp_ajax_sm_save_attendance', array( __CLASS__, 'ajax_save_attendance' ) );
        add_action( 'wp_ajax_sm_get_attendance_history', array( __CLASS__, 'ajax_get_attendance_history' ) );
    }

    /**
     * Render attendance page
     */
    public static function render_attendance_page() {
        global $wpdb;

        // Check if user has permission to view attendance
        $current_user = wp_get_current_user();
        $is_teacher = in_array( 'school_teacher', $current_user->roles );
        $is_admin = current_user_can( 'manage_attendance' );

        if ( ! current_user_can( 'view_attendance' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'CTADZ-school-management' ) );
        }

        // Get teacher ID if current user is a teacher
        $teacher_id = null;
        if ( $is_teacher ) {
            $teachers_table = $wpdb->prefix . 'sm_teachers';
            $teacher = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM $teachers_table WHERE user_id = %d",
                $current_user->ID
            ) );
            if ( $teacher ) {
                $teacher_id = $teacher->id;
            }
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e( 'Attendance Management', 'CTADZ-school-management' ); ?>
            </h1>
            <hr class="wp-header-end">

            <div class="sm-attendance-container">
                <div class="sm-attendance-tabs">
                    <button class="sm-tab-button active" data-tab="mark-attendance">
                        <?php esc_html_e( 'Mark Attendance', 'CTADZ-school-management' ); ?>
                    </button>
                    <button class="sm-tab-button" data-tab="attendance-history">
                        <?php esc_html_e( 'Attendance History', 'CTADZ-school-management' ); ?>
                    </button>
                </div>

                <!-- Mark Attendance Tab -->
                <div id="mark-attendance-tab" class="sm-tab-content active">
                    <?php self::render_mark_attendance_section( $teacher_id, $is_admin ); ?>
                </div>

                <!-- Attendance History Tab -->
                <div id="attendance-history-tab" class="sm-tab-content">
                    <?php self::render_attendance_history_section( $teacher_id, $is_admin ); ?>
                </div>
            </div>
        </div>

        <!-- Modal for attendance (shared between both tabs) -->
        <div id="sm-attendance-modal" class="sm-modal" style="display: none;">
            <div class="sm-modal-content">
                <span class="sm-modal-close">&times;</span>
                <div id="sm-modal-body"></div>
            </div>
        </div>

        <style>
            .sm-attendance-container {
                background: #fff;
                margin-top: 20px;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .sm-attendance-tabs {
                display: flex;
                border-bottom: 1px solid #ccd0d4;
                background: #f0f0f1;
            }
            .sm-tab-button {
                padding: 12px 24px;
                background: transparent;
                border: none;
                border-bottom: 2px solid transparent;
                cursor: pointer;
                font-weight: 500;
                color: #50575e;
            }
            .sm-tab-button:hover {
                color: #2271b1;
            }
            .sm-tab-button.active {
                border-bottom-color: #2271b1;
                color: #2271b1;
                background: #fff;
            }
            .sm-tab-content {
                display: none;
                padding: 20px;
            }
            .sm-tab-content.active {
                display: block;
            }
            .sm-class-card {
                background: #f6f7f7;
                padding: 15px;
                margin-bottom: 15px;
                border-left: 4px solid #2271b1;
                border-radius: 3px;
                cursor: pointer;
                transition: all 0.3s;
            }
            .sm-class-card:hover {
                background: #e5e5e5;
                border-left-color: #135e96;
            }
            .sm-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .sm-card-header h4 {
                margin: 0;
                color: #2271b1;
                font-size: 16px;
                font-weight: 600;
            }
            .sm-card-date {
                background: #2271b1;
                color: #fff;
                padding: 4px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .sm-attendance-marked-badge {
                display: inline-block;
                background: #46b450;
                color: #fff;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 500;
                margin-left: 8px;
            }
            .sm-card-marked {
                border-left-color: #46b450;
            }
            .sm-class-card .sm-class-meta {
                display: flex;
                gap: 20px;
                color: #50575e;
                font-size: 13px;
            }
            .sm-students-list {
                margin-top: 20px;
            }
            .sm-student-row {
                display: grid;
                grid-template-columns: 1fr 250px 1fr auto;
                gap: 15px;
                padding: 12px;
                border-bottom: 1px solid #dcdcde;
                align-items: center;
            }
            .sm-student-row:hover {
                background: #f6f7f7;
            }
            .sm-student-name {
                font-weight: 500;
            }
            .sm-attendance-status {
                display: flex;
                gap: 10px;
            }
            .sm-status-btn {
                padding: 6px 16px;
                border: 2px solid #dcdcde;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                transition: all 0.2s;
            }
            .sm-status-btn:hover {
                border-color: #2271b1;
            }
            .sm-status-btn.active {
                border-color: #2271b1;
                background: #2271b1;
                color: #fff;
            }
            .sm-status-btn.present.active {
                background: #46b450;
                border-color: #46b450;
            }
            .sm-status-btn.absent.active {
                background: #dc3232;
                border-color: #dc3232;
            }
            .sm-status-btn.late.active {
                background: #f0b849;
                border-color: #f0b849;
            }
            .sm-status-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            .sm-notes-input {
                padding: 6px 10px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                width: 100%;
            }
            .sm-notes-input[readonly] {
                background: #f6f7f7;
                cursor: not-allowed;
            }
            .sm-save-attendance {
                margin-top: 20px;
                padding: 10px 24px;
            }
            .sm-no-data {
                padding: 40px 20px;
                text-align: center;
                color: #50575e;
            }
            .sm-date-filter {
                margin-bottom: 20px;
                display: flex;
                gap: 15px;
                align-items: center;
            }
            .sm-history-table {
                width: 100%;
                border-collapse: collapse;
            }
            .sm-history-table th,
            .sm-history-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #dcdcde;
            }
            .sm-history-table th {
                background: #f6f7f7;
                font-weight: 600;
            }
            .sm-status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .sm-status-badge.present {
                background: #ecf7ed;
                color: #46b450;
            }
            .sm-status-badge.absent {
                background: #fdeaea;
                color: #dc3232;
            }
            .sm-status-badge.late {
                background: #fff9e6;
                color: #f0b849;
            }
            /* Modal styles */
            .sm-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.6);
            }
            .sm-modal-content {
                background-color: #fff;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #ccd0d4;
                width: 90%;
                max-width: 1200px;
                border-radius: 4px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                max-height: 85vh;
                overflow-y: auto;
            }
            .sm-modal-close {
                color: #50575e;
                float: right;
                font-size: 32px;
                font-weight: bold;
                padding: 10px 20px;
                cursor: pointer;
            }
            .sm-modal-close:hover,
            .sm-modal-close:focus {
                color: #2271b1;
            }
            #sm-modal-body {
                padding: 20px;
                clear: both;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Modal setup
            var modal = $('#sm-attendance-modal');

            // Close modal when clicking X or outside
            $('.sm-modal-close').on('click', function() {
                modal.hide();
            });

            $(window).on('click', function(event) {
                if (event.target.id === 'sm-attendance-modal') {
                    modal.hide();
                }
            });

            // Close modal with ESC key
            $(document).on('keydown', function(event) {
                if (event.key === 'Escape' && modal.is(':visible')) {
                    modal.hide();
                }
            });

            // Tab switching
            $('.sm-tab-button').on('click', function() {
                var tab = $(this).data('tab');
                $('.sm-tab-button').removeClass('active');
                $(this).addClass('active');
                $('.sm-tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            });

            // Load class students when clicking on a class card
            $('.sm-class-card').on('click', function() {
                var classId = $(this).data('class-id');
                var courseId = $(this).data('course-id');
                var date = $(this).data('date');

                $('#sm-attendance-form-container').html('<p>Loading students...</p>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_class_students',
                        nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                        schedule_id: classId,
                        course_id: courseId,
                        date: date
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#sm-attendance-form-container').html(response.data.html);
                            initializeAttendanceForm();
                        } else {
                            $('#sm-attendance-form-container').html('<p class="sm-no-data">' + response.data.message + '</p>');
                        }
                    }
                });
            });

            function initializeAttendanceForm(isReadonly) {
                // Status button click (only if not readonly)
                if (!isReadonly) {
                    $('.sm-status-btn').on('click', function() {
                        $(this).siblings().removeClass('active');
                        $(this).addClass('active');
                    });
                }

                // Save attendance
                $('#sm-save-attendance-btn').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true).text('Saving...');

                    var attendanceData = [];
                    $('.sm-student-row').each(function() {
                        var studentId = $(this).data('student-id');
                        var status = $(this).find('.sm-status-btn.active').data('status');
                        var notes = $(this).find('.sm-notes-input').val();

                        if (status) {
                            attendanceData.push({
                                student_id: studentId,
                                status: status,
                                notes: notes
                            });
                        }
                    });

                    var scheduleId = $('#sm-attendance-form').data('schedule-id');
                    var courseId = $('#sm-attendance-form').data('course-id');
                    var date = $('#sm-attendance-form').data('date');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_save_attendance',
                            nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                            schedule_id: scheduleId,
                            course_id: courseId,
                            date: date,
                            attendance: JSON.stringify(attendanceData)
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Attendance saved successfully!');
                                $('#sm-attendance-form-container').html('<p class="sm-no-data">Attendance saved. Select another class to mark attendance.</p>');
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                            button.prop('disabled', false).text('Save Attendance');
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            button.prop('disabled', false).text('Save Attendance');
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render mark attendance section
     */
    private static function render_mark_attendance_section( $teacher_id, $is_admin ) {
        // Debug logging
        error_log( 'SM Attendance: render_mark_attendance_section called' );
        error_log( 'SM Attendance: teacher_id = ' . $teacher_id );
        error_log( 'SM Attendance: is_admin = ' . ( $is_admin ? 'yes' : 'no' ) );

        // Check if calendar plugin is active
        if ( ! class_exists( 'SMC_Schedules_Page' ) ) {
            error_log( 'SM Attendance: Calendar plugin not active' );
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'The School Management Calendar plugin is required to mark attendance for scheduled classes.', 'CTADZ-school-management' ) . '</p></div>';
            return;
        }

        global $wpdb;
        $schedules_table = $wpdb->prefix . 'smc_schedules';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $classrooms_table = $wpdb->prefix . 'sm_classrooms';
        $attendance_table = $wpdb->prefix . 'sm_attendance';

        $current_date = current_time( 'Y-m-d' );
        $where_clause = $is_admin ? '' : $wpdb->prepare( ' AND s.teacher_id = %d', $teacher_id );

        error_log( 'SM Attendance: current_date = ' . $current_date );
        error_log( 'SM Attendance: where_clause = ' . $where_clause );

        // Get all past and today's schedule instances that don't have attendance marked
        // We need to find dates between effective_from and today for each schedule
        $schedules = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, c.name as course_name, cl.name as classroom_name
             FROM $schedules_table s
             LEFT JOIN $courses_table c ON s.course_id = c.id
             LEFT JOIN $classrooms_table cl ON s.classroom_id = cl.id
             WHERE s.is_active = 1
               AND s.effective_from <= %s
               AND (s.effective_until IS NULL OR s.effective_until >= s.effective_from)
             $where_clause
             ORDER BY s.day_of_week ASC, s.start_time ASC",
            $current_date
        ) );

        error_log( 'SM Attendance: schedules found = ' . count( $schedules ) );

        if ( empty( $schedules ) ) {
            error_log( 'SM Attendance: No schedules found, returning' );
            echo '<p class="sm-no-data">' . esc_html__( 'No classes scheduled.', 'CTADZ-school-management' ) . '</p>';
            return;
        }

        // Build list of dates that need attendance
        $classes_needing_attendance = array();

        foreach ( $schedules as $schedule ) {
            // Get the date range for this schedule
            $start_date = $schedule->effective_from;
            $end_date = $schedule->effective_until ?: $current_date;
            if ( $end_date > $current_date ) {
                $end_date = $current_date;
            }

            // Loop through all dates in range and check if they match the day_of_week
            $date = new DateTime( $start_date );
            $end = new DateTime( $end_date );

            while ( $date <= $end ) {
                $date_str = $date->format( 'Y-m-d' );
                $day_of_week = $date->format( 'N' ); // 1=Mon, 7=Sun

                // Check if this date matches the schedule's day of week
                if ( $day_of_week == $schedule->day_of_week ) {
                    // Check if attendance already exists
                    $has_attendance = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM $attendance_table
                         WHERE course_id = %d AND date = %s",
                        $schedule->course_id,
                        $date_str
                    ) );

                    // Only show classes without attendance (both teachers and admins)
                    if ( ! $has_attendance ) {
                        $classes_needing_attendance[] = array(
                            'schedule_id' => $schedule->id,
                            'course_id' => $schedule->course_id,
                            'course_name' => $schedule->course_name,
                            'classroom_name' => $schedule->classroom_name,
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'date' => $date_str
                        );
                    }
                }

                $date->modify( '+1 day' );
            }
        }

        error_log( 'SM Attendance: classes_needing_attendance count = ' . count( $classes_needing_attendance ) );

        if ( empty( $classes_needing_attendance ) ) {
            error_log( 'SM Attendance: No classes needing attendance' );
            echo '<div class="notice notice-success inline"><p>';
            echo '<strong>' . esc_html__( '✓ All caught up!', 'CTADZ-school-management' ) . '</strong> ';
            echo esc_html__( 'All scheduled classes have attendance marked.', 'CTADZ-school-management' );
            echo '</p></div>';
            return;
        }

        // Group by date
        $grouped = array();
        foreach ( $classes_needing_attendance as $class ) {
            $grouped[$class['date']][] = $class;
        }

        // Sort by date descending (most recent first)
        krsort( $grouped );

        echo '<p class="description" style="margin-bottom: 20px;">';
        echo esc_html__( 'Classes below need attendance marked. Click on a class card to mark attendance. To edit existing attendance, use the "Attendance History" tab.', 'CTADZ-school-management' );
        echo '</p>';

        echo '<div class="sm-classes-list">';
        foreach ( $grouped as $date => $classes ) {
            $date_obj = new DateTime( $date );
            $is_today = ( $date === $current_date );
            $date_label = $is_today ? __( 'Today', 'CTADZ-school-management' ) : $date_obj->format( 'l, F j, Y' );

            foreach ( $classes as $class ) {
                ?>
                <div class="sm-class-card"
                     data-class-id="<?php echo esc_attr( $class['schedule_id'] ); ?>"
                     data-course-id="<?php echo esc_attr( $class['course_id'] ); ?>"
                     data-date="<?php echo esc_attr( $class['date'] ); ?>">
                    <div class="sm-card-header">
                        <h4><?php echo esc_html( $class['course_name'] ); ?></h4>
                        <span class="sm-card-date"><?php echo esc_html( $date_label ); ?></span>
                    </div>
                    <div class="sm-class-meta">
                        <span><strong><?php esc_html_e( 'Date:', 'CTADZ-school-management' ); ?></strong>
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $class['date'] ) ) ); ?>
                        </span>
                        <span><strong><?php esc_html_e( 'Time:', 'CTADZ-school-management' ); ?></strong>
                            <?php echo esc_html( date_i18n( 'g:i A', strtotime( $class['start_time'] ) ) ); ?> -
                            <?php echo esc_html( date_i18n( 'g:i A', strtotime( $class['end_time'] ) ) ); ?>
                        </span>
                        <?php if ( $class['classroom_name'] ) : ?>
                            <span><strong><?php esc_html_e( 'Room:', 'CTADZ-school-management' ); ?></strong>
                                <?php echo esc_html( $class['classroom_name'] ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        }
        echo '</div>';
        ?>
        <script>
        jQuery(document).ready(function($) {
            var modal = $('#sm-attendance-modal');

            $('.sm-class-card').on('click', function() {
                var classId = $(this).data('class-id');
                var courseId = $(this).data('course-id');
                var date = $(this).data('date');

                // Open modal and show loading
                modal.show();
                $('#sm-modal-body').html('<p style="text-align: center; padding: 40px;">Loading students...</p>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_class_students',
                        nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                        schedule_id: classId,
                        course_id: courseId,
                        date: date,
                        is_admin: <?php echo $is_admin ? '1' : '0'; ?>
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#sm-modal-body').html(response.data.html);
                            initializeAttendanceForm(response.data.is_readonly);
                        } else {
                            $('#sm-modal-body').html('<p class="sm-no-data">' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#sm-modal-body').html('<p class="sm-no-data">An error occurred. Please try again.</p>');
                    }
                });
            });

            function initializeAttendanceForm(isReadonly) {
                // Status button click (only if not readonly)
                if (!isReadonly) {
                    $('.sm-status-btn').on('click', function() {
                        $(this).siblings().removeClass('active');
                        $(this).addClass('active');
                    });
                }

                // Save attendance
                $('#sm-save-attendance-btn').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true).text('Saving...');

                    var attendanceData = [];
                    $('.sm-student-row').each(function() {
                        var studentId = $(this).data('student-id');
                        var status = $(this).find('.sm-status-btn.active').data('status');
                        var notes = $(this).find('.sm-notes-input').val();

                        if (status) {
                            attendanceData.push({
                                student_id: studentId,
                                status: status,
                                notes: notes
                            });
                        }
                    });

                    var scheduleId = $('#sm-attendance-form').data('schedule-id');
                    var courseId = $('#sm-attendance-form').data('course-id');
                    var date = $('#sm-attendance-form').data('date');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_save_attendance',
                            nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                            schedule_id: scheduleId,
                            course_id: courseId,
                            date: date,
                            attendance: JSON.stringify(attendanceData)
                        },
                        success: function(response) {
                            if (response.success) {
                                // Close modal
                                modal.hide();
                                // Show success message
                                $('<div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p><strong>Success!</strong> Attendance saved successfully.</p></div>')
                                    .insertAfter('.wrap h1')
                                    .delay(3000)
                                    .fadeOut(400, function() { $(this).remove(); });
                                // Reload page to refresh the list
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            } else {
                                alert('Error: ' + response.data.message);
                                button.prop('disabled', false).text('Save Attendance');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            button.prop('disabled', false).text('Save Attendance');
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX: Get classes for a specific date
     */
    public static function ajax_get_classes_for_date() {
        check_ajax_referer( 'sm_attendance_nonce', 'nonce' );

        $date = sanitize_text_field( $_POST['date'] ?? '' );
        $day_of_week = intval( $_POST['day_of_week'] ?? 0 );
        $teacher_id = intval( $_POST['teacher_id'] ?? 0 );
        $is_admin = isset( $_POST['is_admin'] ) && $_POST['is_admin'] == '1';

        if ( ! $date || ! $day_of_week ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date', 'CTADZ-school-management' ) ) );
        }

        // Check if calendar plugin is active
        if ( ! class_exists( 'SMC_Schedule_Page' ) ) {
            wp_send_json_error( array( 'message' => __( 'The School Management Calendar plugin is required to mark attendance for scheduled classes.', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $schedules_table = $wpdb->prefix . 'smc_schedules';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $classrooms_table = $wpdb->prefix . 'sm_classrooms';
        $attendance_table = $wpdb->prefix . 'sm_attendance';

        $where_clause = $is_admin ? '' : $wpdb->prepare( ' AND s.teacher_id = %d', $teacher_id );

        $schedules = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, c.name as course_name, cl.name as classroom_name
             FROM $schedules_table s
             LEFT JOIN $courses_table c ON s.course_id = c.id
             LEFT JOIN $classrooms_table cl ON s.classroom_id = cl.id
             WHERE s.day_of_week = %d
               AND s.is_active = 1
               AND s.effective_from <= %s
               AND (s.effective_until IS NULL OR s.effective_until >= %s)
             $where_clause
             ORDER BY s.start_time ASC",
            $day_of_week,
            $date,
            $date
        ) );

        if ( empty( $schedules ) ) {
            wp_send_json_error( array( 'message' => __( 'No classes scheduled for this date.', 'CTADZ-school-management' ) ) );
        }

        // Build HTML for class cards
        $html = '<div class="sm-classes-list">';
        foreach ( $schedules as $schedule ) {
            // Check if attendance already marked for this class
            $attendance_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $attendance_table
                 WHERE course_id = %d AND date = %s",
                $schedule->course_id,
                $date
            ) );

            $status_badge = '';
            if ( $attendance_count > 0 ) {
                $status_badge = '<span style="display: inline-block; padding: 3px 8px; background: #46b450; color: #fff; border-radius: 3px; font-size: 11px; margin-left: 10px;">✓ Marked</span>';
            }

            $html .= '<div class="sm-class-card"
                     data-class-id="' . esc_attr( $schedule->id ) . '"
                     data-course-id="' . esc_attr( $schedule->course_id ) . '"
                     data-date="' . esc_attr( $date ) . '">';
            $html .= '<h3>' . esc_html( $schedule->course_name ) . $status_badge . '</h3>';
            $html .= '<div class="sm-class-meta">';
            $html .= '<span><strong>' . __( 'Time:', 'CTADZ-school-management' ) . '</strong> ';
            $html .= esc_html( date_i18n( 'g:i A', strtotime( $schedule->start_time ) ) ) . ' - ';
            $html .= esc_html( date_i18n( 'g:i A', strtotime( $schedule->end_time ) ) );
            $html .= '</span>';
            if ( $schedule->classroom_name ) {
                $html .= '<span><strong>' . __( 'Room:', 'CTADZ-school-management' ) . '</strong> ';
                $html .= esc_html( $schedule->classroom_name );
                $html .= '</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Render attendance history section
     */
    private static function render_attendance_history_section( $teacher_id, $is_admin ) {
        echo '<div class="sm-date-filter">';
        echo '<label>' . esc_html__( 'From:', 'CTADZ-school-management' ) . '</label>';
        echo '<input type="date" id="sm-history-from" value="' . esc_attr( date( 'Y-m-d', strtotime( '-7 days' ) ) ) . '" />';
        echo '<label>' . esc_html__( 'To:', 'CTADZ-school-management' ) . '</label>';
        echo '<input type="date" id="sm-history-to" value="' . esc_attr( current_time( 'Y-m-d' ) ) . '" />';
        echo '<label>' . esc_html__( 'Course:', 'CTADZ-school-management' ) . '</label>';
        echo '<select id="sm-history-course">';
        echo '<option value="">' . esc_html__( 'All Courses', 'CTADZ-school-management' ) . '</option>';

        // Get courses for filter
        global $wpdb;
        $courses_table = $wpdb->prefix . 'sm_courses';

        if ( $is_admin ) {
            $courses = $wpdb->get_results( "SELECT id, name FROM $courses_table ORDER BY name ASC" );
        } else {
            // Get only teacher's courses
            $schedules_table = $wpdb->prefix . 'smc_schedules';
            $courses = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT c.id, c.name
                 FROM $courses_table c
                 INNER JOIN $schedules_table s ON c.id = s.course_id
                 WHERE s.teacher_id = %d
                 ORDER BY c.name ASC",
                $teacher_id
            ) );
        }

        foreach ( $courses as $course ) {
            echo '<option value="' . esc_attr( $course->id ) . '">' . esc_html( $course->name ) . '</option>';
        }

        echo '</select>';
        echo '<button type="button" class="button button-primary" id="sm-filter-history">' . esc_html__( 'Load History', 'CTADZ-school-management' ) . '</button>';
        echo '</div>';
        echo '<div id="sm-history-results">';
        echo '<p class="sm-no-data">' . esc_html__( 'Select date range and click "Load History" to view attendance records.', 'CTADZ-school-management' ) . '</p>';
        echo '</div>';

        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#sm-filter-history').on('click', function() {
                var button = $(this);
                var fromDate = $('#sm-history-from').val();
                var toDate = $('#sm-history-to').val();
                var courseId = $('#sm-history-course').val();

                if (!fromDate || !toDate) {
                    alert('Please select both from and to dates.');
                    return;
                }

                button.prop('disabled', true).text('Loading...');
                $('#sm-history-results').html('<p style="text-align: center; padding: 40px;">Loading attendance history...</p>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_attendance_history',
                        nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                        from_date: fromDate,
                        to_date: toDate,
                        course_id: courseId,
                        teacher_id: <?php echo $teacher_id ? $teacher_id : 0; ?>,
                        is_admin: <?php echo $is_admin ? '1' : '0'; ?>
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#sm-history-results').html(response.data.html);
                            // Initialize edit buttons
                            initHistoryEditButtons();
                        } else {
                            $('#sm-history-results').html('<p class="sm-no-data">' + response.data.message + '</p>');
                        }
                        button.prop('disabled', false).text('Load History');
                    },
                    error: function() {
                        $('#sm-history-results').html('<p class="sm-no-data">An error occurred. Please try again.</p>');
                        button.prop('disabled', false).text('Load History');
                    }
                });
            });

            // Initialize edit buttons for attendance history
            function initHistoryEditButtons() {
                $('.sm-edit-attendance-btn').on('click', function() {
                    var scheduleId = $(this).data('schedule-id');
                    var courseId = $(this).data('course-id');
                    var date = $(this).data('date');

                    // Open modal from Mark Attendance tab
                    var modal = $('#sm-attendance-modal');
                    modal.show();
                    $('#sm-modal-body').html('<p style="text-align: center; padding: 40px;">Loading attendance data...</p>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_get_class_students',
                            nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                            schedule_id: scheduleId,
                            course_id: courseId,
                            date: date,
                            is_admin: 1
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#sm-modal-body').html(response.data.html);
                                // Initialize the form with callback to reload history after save
                                initializeAttendanceFormInHistory(response.data.is_readonly);
                            } else {
                                $('#sm-modal-body').html('<p class="sm-no-data">' + response.data.message + '</p>');
                            }
                        },
                        error: function() {
                            $('#sm-modal-body').html('<p class="sm-no-data">An error occurred. Please try again.</p>');
                        }
                    });
                });
            }

            // Initialize form in history mode
            function initializeAttendanceFormInHistory(isReadonly) {
                if (!isReadonly) {
                    $('.sm-status-btn').on('click', function() {
                        $(this).siblings().removeClass('active');
                        $(this).addClass('active');
                    });
                }

                $('#sm-save-attendance-btn').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true).text('Saving...');

                    var attendanceData = [];
                    $('.sm-student-row').each(function() {
                        var studentId = $(this).data('student-id');
                        var status = $(this).find('.sm-status-btn.active').data('status');
                        var notes = $(this).find('.sm-notes-input').val();

                        if (status) {
                            attendanceData.push({
                                student_id: studentId,
                                status: status,
                                notes: notes
                            });
                        }
                    });

                    var scheduleId = $('#sm-attendance-form').data('schedule-id');
                    var courseId = $('#sm-attendance-form').data('course-id');
                    var date = $('#sm-attendance-form').data('date');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_save_attendance',
                            nonce: '<?php echo wp_create_nonce( 'sm_attendance_nonce' ); ?>',
                            schedule_id: scheduleId,
                            course_id: courseId,
                            date: date,
                            attendance: JSON.stringify(attendanceData)
                        },
                        success: function(response) {
                            if (response.success) {
                                var modal = $('#sm-attendance-modal');
                                modal.hide();
                                // Show success message
                                $('<div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p><strong>Success!</strong> Attendance updated successfully.</p></div>')
                                    .insertAfter('.wrap h1')
                                    .delay(3000)
                                    .fadeOut(400, function() { $(this).remove(); });
                                // Reload history
                                $('#sm-filter-history').click();
                            } else {
                                alert('Error: ' + response.data.message);
                                button.prop('disabled', false).text('Save Attendance');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            button.prop('disabled', false).text('Save Attendance');
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX: Get students for a class
     */
    public static function ajax_get_class_students() {
        check_ajax_referer( 'sm_attendance_nonce', 'nonce' );

        $schedule_id = intval( $_POST['schedule_id'] ?? 0 );
        $course_id = intval( $_POST['course_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['date'] ?? '' );
        $is_admin = isset( $_POST['is_admin'] ) && $_POST['is_admin'] == '1';

        if ( ! $course_id || ! $date ) {
            wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $attendance_table = $wpdb->prefix . 'sm_attendance';

        // Get enrolled students
        $students = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.id, s.name, s.student_code,
                    a.status as existing_status, a.notes as existing_notes
             FROM $students_table s
             INNER JOIN $enrollments_table e ON s.id = e.student_id
             LEFT JOIN $attendance_table a ON s.id = a.student_id
                   AND a.course_id = %d AND a.date = %s
             WHERE e.course_id = %d AND e.status = 'active'
             ORDER BY s.name ASC",
            $course_id,
            $date,
            $course_id
        ) );

        if ( empty( $students ) ) {
            wp_send_json_error( array( 'message' => __( 'No students enrolled in this course', 'CTADZ-school-management' ) ) );
        }

        // Check if attendance already exists and if user can edit
        $has_existing_attendance = false;
        foreach ( $students as $student ) {
            if ( ! empty( $student->existing_status ) ) {
                $has_existing_attendance = true;
                break;
            }
        }

        $is_readonly = $has_existing_attendance && ! $is_admin;

        // Generate HTML
        $html = '<div id="sm-attendance-form" data-schedule-id="' . esc_attr( $schedule_id ) . '"
                     data-course-id="' . esc_attr( $course_id ) . '" data-date="' . esc_attr( $date ) . '">';

        if ( $is_readonly ) {
            $html .= '<div class="notice notice-info inline" style="margin: 0 0 15px 0; padding: 10px;"><p>';
            $html .= '<strong>' . __( 'Read-Only Mode:', 'CTADZ-school-management' ) . '</strong> ';
            $html .= __( 'Attendance has already been submitted for this class. Only administrators can edit submitted attendance.', 'CTADZ-school-management' );
            $html .= '</p></div>';
        }

        $html .= '<h2>' . sprintf( __( 'Attendance for %s', 'CTADZ-school-management' ), date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) . '</h2>';
        $html .= '<div class="sm-students-list">';

        foreach ( $students as $student ) {
            $existing_status = $student->existing_status ?: '';
            $existing_notes = $student->existing_notes ?: '';

            $html .= '<div class="sm-student-row" data-student-id="' . esc_attr( $student->id ) . '">';
            $html .= '<div class="sm-student-name">' . esc_html( $student->name );
            if ( $student->student_code ) {
                $html .= ' <span style="color: #999; font-size: 12px;">(' . esc_html( $student->student_code ) . ')</span>';
            }
            $html .= '</div>';

            $html .= '<div class="sm-attendance-status">';
            $disabled = $is_readonly ? ' disabled' : '';
            $html .= '<button type="button" class="sm-status-btn present ' . ( $existing_status === 'present' ? 'active' : '' ) . '" data-status="present"' . $disabled . '>' . __( 'Present', 'CTADZ-school-management' ) . '</button>';
            $html .= '<button type="button" class="sm-status-btn absent ' . ( $existing_status === 'absent' ? 'active' : '' ) . '" data-status="absent"' . $disabled . '>' . __( 'Absent', 'CTADZ-school-management' ) . '</button>';
            $html .= '<button type="button" class="sm-status-btn late ' . ( $existing_status === 'late' ? 'active' : '' ) . '" data-status="late"' . $disabled . '>' . __( 'Late', 'CTADZ-school-management' ) . '</button>';
            $html .= '</div>';

            $html .= '<input type="text" class="sm-notes-input" placeholder="' . esc_attr__( 'Notes (optional)', 'CTADZ-school-management' ) . '" value="' . esc_attr( $existing_notes ) . '"' . ( $is_readonly ? ' readonly' : '' ) . ' />';
            $html .= '<div></div>'; // Empty div for grid layout
            $html .= '</div>';
        }

        $html .= '</div>';

        if ( ! $is_readonly ) {
            $html .= '<button type="button" class="button button-primary sm-save-attendance" id="sm-save-attendance-btn">' . __( 'Save Attendance', 'CTADZ-school-management' ) . '</button>';
        }

        $html .= '</div>';

        wp_send_json_success( array(
            'html' => $html,
            'is_readonly' => $is_readonly
        ) );
    }

    /**
     * AJAX: Save attendance
     */
    public static function ajax_save_attendance() {
        check_ajax_referer( 'sm_attendance_nonce', 'nonce' );

        $schedule_id = intval( $_POST['schedule_id'] ?? 0 );
        $course_id = intval( $_POST['course_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['date'] ?? '' );
        $attendance_json = stripslashes( $_POST['attendance'] ?? '[]' );
        $attendance_data = json_decode( $attendance_json, true );

        if ( ! $course_id || ! $date || empty( $attendance_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid attendance data', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $attendance_table = $wpdb->prefix . 'sm_attendance';
        $current_user_id = get_current_user_id();

        $success_count = 0;
        $is_admin = current_user_can( 'manage_attendance' );

        foreach ( $attendance_data as $record ) {
            $student_id = intval( $record['student_id'] );
            $status = sanitize_text_field( $record['status'] );
            $notes = sanitize_textarea_field( $record['notes'] );

            if ( ! $student_id || ! in_array( $status, array( 'present', 'absent', 'late' ) ) ) {
                continue;
            }

            // Check if attendance already exists for this specific student
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $attendance_table
                 WHERE student_id = %d AND course_id = %d AND date = %s",
                $student_id,
                $course_id,
                $date
            ) );

            if ( $existing ) {
                // If attendance exists and user is not admin, skip this student
                if ( ! $is_admin ) {
                    continue;
                }

                // Update existing attendance (admin only)
                $wpdb->update(
                    $attendance_table,
                    array(
                        'status' => $status,
                        'notes' => $notes,
                        'marked_by' => $current_user_id
                    ),
                    array( 'id' => $existing ),
                    array( '%s', '%s', '%d' ),
                    array( '%d' )
                );
            } else {
                // Insert new attendance (both teachers and admins can do this)
                $wpdb->insert(
                    $attendance_table,
                    array(
                        'student_id' => $student_id,
                        'course_id' => $course_id,
                        'schedule_id' => $schedule_id,
                        'date' => $date,
                        'status' => $status,
                        'notes' => $notes,
                        'marked_by' => $current_user_id
                    ),
                    array( '%d', '%d', '%d', '%s', '%s', '%s', '%d' )
                );
            }

            $success_count++;
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Attendance saved for %d students', 'CTADZ-school-management' ), $success_count )
        ) );
    }

    /**
     * AJAX: Get attendance history
     */
    public static function ajax_get_attendance_history() {
        check_ajax_referer( 'sm_attendance_nonce', 'nonce' );

        $from_date = sanitize_text_field( $_POST['from_date'] ?? '' );
        $to_date = sanitize_text_field( $_POST['to_date'] ?? '' );
        $course_id = intval( $_POST['course_id'] ?? 0 );
        $teacher_id = intval( $_POST['teacher_id'] ?? 0 );
        $is_admin = isset( $_POST['is_admin'] ) && $_POST['is_admin'] == '1';

        if ( ! $from_date || ! $to_date ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date range', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $attendance_table = $wpdb->prefix . 'sm_attendance';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $users_table = $wpdb->prefix . 'users';

        // Build WHERE clause
        $where_clauses = array(
            $wpdb->prepare( 'a.date >= %s', $from_date ),
            $wpdb->prepare( 'a.date <= %s', $to_date )
        );

        if ( $course_id ) {
            $where_clauses[] = $wpdb->prepare( 'a.course_id = %d', $course_id );
        }

        // If teacher, only show their courses
        if ( ! $is_admin && $teacher_id ) {
            $schedules_table = $wpdb->prefix . 'smc_schedules';
            $where_clauses[] = $wpdb->prepare(
                'a.course_id IN (SELECT DISTINCT course_id FROM ' . $schedules_table . ' WHERE teacher_id = %d)',
                $teacher_id
            );
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // Get attendance records
        $records = $wpdb->get_results(
            "SELECT a.*, s.name as student_name, s.student_code,
                    c.name as course_name, u.display_name as marked_by_name
             FROM $attendance_table a
             LEFT JOIN $students_table s ON a.student_id = s.id
             LEFT JOIN $courses_table c ON a.course_id = c.id
             LEFT JOIN $users_table u ON a.marked_by = u.ID
             WHERE $where_sql
             ORDER BY a.date DESC, c.name ASC, s.name ASC"
        );

        if ( empty( $records ) ) {
            wp_send_json_error( array( 'message' => __( 'No attendance records found for the selected criteria.', 'CTADZ-school-management' ) ) );
        }

        // Group by date and course
        $grouped = array();
        foreach ( $records as $record ) {
            $key = $record->date . '|' . $record->course_id;
            if ( ! isset( $grouped[$key] ) ) {
                $grouped[$key] = array(
                    'date' => $record->date,
                    'course_id' => $record->course_id,
                    'course_name' => $record->course_name,
                    'schedule_id' => $record->schedule_id,
                    'students' => array()
                );
            }
            $grouped[$key]['students'][] = $record;
        }

        // Generate HTML
        $html = '<div class="sm-history-container">';

        foreach ( $grouped as $group ) {
            $date_formatted = date_i18n( get_option( 'date_format' ), strtotime( $group['date'] ) );
            $total_students = count( $group['students'] );
            $present_count = count( array_filter( $group['students'], function( $s ) { return $s->status === 'present'; } ) );
            $absent_count = count( array_filter( $group['students'], function( $s ) { return $s->status === 'absent'; } ) );
            $late_count = count( array_filter( $group['students'], function( $s ) { return $s->status === 'late'; } ) );

            $html .= '<div class="sm-history-group">';
            $html .= '<div class="sm-history-header">';
            $html .= '<div>';
            $html .= '<h3>' . esc_html( $group['course_name'] ) . '</h3>';
            $html .= '</div>';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<span class="sm-history-date">' . esc_html( $date_formatted ) . '</span>';
            if ( $is_admin ) {
                $html .= '<button type="button" class="button button-small sm-edit-attendance-btn"
                          data-schedule-id="' . esc_attr( $group['schedule_id'] ) . '"
                          data-course-id="' . esc_attr( $group['course_id'] ) . '"
                          data-date="' . esc_attr( $group['date'] ) . '">' . __( 'Edit', 'CTADZ-school-management' ) . '</button>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="sm-history-summary">';
            $html .= '<span class="sm-summary-item"><strong>' . __( 'Total:', 'CTADZ-school-management' ) . '</strong> ' . $total_students . '</span>';
            $html .= '<span class="sm-summary-item sm-summary-present"><strong>' . __( 'Present:', 'CTADZ-school-management' ) . '</strong> ' . $present_count . '</span>';
            $html .= '<span class="sm-summary-item sm-summary-absent"><strong>' . __( 'Absent:', 'CTADZ-school-management' ) . '</strong> ' . $absent_count . '</span>';
            $html .= '<span class="sm-summary-item sm-summary-late"><strong>' . __( 'Late:', 'CTADZ-school-management' ) . '</strong> ' . $late_count . '</span>';
            $html .= '</div>';

            $html .= '<table class="sm-history-table">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __( 'Student', 'CTADZ-school-management' ) . '</th>';
            $html .= '<th>' . __( 'Code', 'CTADZ-school-management' ) . '</th>';
            $html .= '<th>' . __( 'Status', 'CTADZ-school-management' ) . '</th>';
            $html .= '<th>' . __( 'Notes', 'CTADZ-school-management' ) . '</th>';
            $html .= '<th>' . __( 'Marked By', 'CTADZ-school-management' ) . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ( $group['students'] as $student ) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html( $student->student_name ) . '</td>';
                $html .= '<td>' . esc_html( $student->student_code ?: '-' ) . '</td>';
                $html .= '<td><span class="sm-status-badge ' . esc_attr( $student->status ) . '">' . esc_html( ucfirst( $student->status ) ) . '</span></td>';
                $html .= '<td>' . esc_html( $student->notes ?: '-' ) . '</td>';
                $html .= '<td>' . esc_html( $student->marked_by_name ?: '-' ) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Add CSS for history view
        $html .= '<style>
            .sm-history-container {
                margin-top: 20px;
            }
            .sm-history-group {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 20px;
                overflow: hidden;
            }
            .sm-history-header {
                background: #f6f7f7;
                padding: 15px 20px;
                border-bottom: 1px solid #ccd0d4;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sm-history-header h3 {
                margin: 0;
                font-size: 16px;
                color: #2271b1;
            }
            .sm-history-date {
                background: #2271b1;
                color: #fff;
                padding: 4px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .sm-history-summary {
                padding: 12px 20px;
                background: #f9f9f9;
                border-bottom: 1px solid #ccd0d4;
                display: flex;
                gap: 25px;
                font-size: 13px;
            }
            .sm-summary-item {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .sm-summary-present strong {
                color: #46b450;
            }
            .sm-summary-absent strong {
                color: #dc3232;
            }
            .sm-summary-late strong {
                color: #f0b849;
            }
            .sm-history-table {
                width: 100%;
                border-collapse: collapse;
            }
            .sm-history-table th,
            .sm-history-table td {
                padding: 12px 20px;
                text-align: left;
                border-bottom: 1px solid #f0f0f1;
            }
            .sm-history-table th {
                background: #fff;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                color: #646970;
            }
            .sm-history-table tbody tr:hover {
                background: #f9f9f9;
            }
            .sm-history-table tbody tr:last-child td {
                border-bottom: none;
            }
        </style>';

        wp_send_json_success( array( 'html' => $html ) );
    }
}

SM_Attendance_Page::init();
