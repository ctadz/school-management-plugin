<?php
/**
 * Family Discount Tools Page (Super Admin Only)
 * Provides bulk recalculation tools for family discounts
 *
 * @package SchoolManagement
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Family_Discount_Tools_Page {

    /**
     * Render the Family Discount Tools page
     */
    public static function render_page() {
        // Security check - super admin only
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

        global $wpdb;

        // Handle bulk recalculation request
        if ( isset( $_POST['bulk_recalculate'] ) && check_admin_referer( 'sm_bulk_recalculate_family_discounts' ) ) {
            self::handle_bulk_recalculation();
        }

        // Get statistics
        $students_table = $wpdb->prefix . 'sm_students';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $schedules_table = $wpdb->prefix . 'sm_payment_schedules';

        // Count students with parent phone
        $students_with_phone = $wpdb->get_var(
            "SELECT COUNT(*) FROM $students_table WHERE parent_phone IS NOT NULL AND parent_phone != ''"
        );

        // Get families (unique parent phones with multiple students)
        // Group by normalized phone to handle different formats
        $families = $wpdb->get_results(
            "SELECT
                MIN(parent_phone) as parent_phone,
                MIN(parent_name) as parent_name,
                COUNT(*) as student_count,
                REGEXP_REPLACE(parent_phone, '[^0-9]', '') as normalized_phone
            FROM $students_table
            WHERE parent_phone IS NOT NULL AND parent_phone != ''
            GROUP BY normalized_phone
            HAVING student_count >= 2
            ORDER BY student_count DESC, parent_name ASC"
        );

        $total_families = count( $families );

        // Count payment schedules that could be affected
        $schedules_count = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM $schedules_table ps
            INNER JOIN $enrollments_table e ON ps.enrollment_id = e.id
            INNER JOIN $students_table s ON e.student_id = s.id
            WHERE ps.status IN ('pending', 'unpaid')
            AND ps.due_date >= CURDATE()
            AND s.parent_phone IS NOT NULL
            AND s.parent_phone != ''"
        );

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-groups" style="font-size: 32px; vertical-align: middle; color: #8e44ad;"></span>
                <?php esc_html_e( 'Family Discount Tools', 'CTADZ-school-management' ); ?>
            </h1>
            <p class="description">
                <?php esc_html_e( 'This page allows super administrators to bulk recalculate family discounts for all families. Use with caution.', 'CTADZ-school-management' ); ?>
            </p>

            <hr style="margin: 30px 0;">

            <!-- Statistics Overview -->
            <div class="sm-family-tools-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">

                <div class="sm-stat-card">
                    <div class="sm-stat-icon" style="background: #8e44ad;">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="sm-stat-content">
                        <h3><?php echo intval( $total_families ); ?></h3>
                        <p><?php esc_html_e( 'Families Detected', 'CTADZ-school-management' ); ?></p>
                        <span class="sm-stat-label"><?php esc_html_e( '(2+ students with same parent phone)', 'CTADZ-school-management' ); ?></span>
                    </div>
                </div>

                <div class="sm-stat-card">
                    <div class="sm-stat-icon" style="background: #0073aa;">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="sm-stat-content">
                        <h3><?php echo intval( $students_with_phone ); ?></h3>
                        <p><?php esc_html_e( 'Students with Parent Phone', 'CTADZ-school-management' ); ?></p>
                        <span class="sm-stat-label"><?php esc_html_e( '(eligible for family discounts)', 'CTADZ-school-management' ); ?></span>
                    </div>
                </div>

                <div class="sm-stat-card">
                    <div class="sm-stat-icon" style="background: #27ae60;">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="sm-stat-content">
                        <h3><?php echo intval( $schedules_count ); ?></h3>
                        <p><?php esc_html_e( 'Pending Payment Schedules', 'CTADZ-school-management' ); ?></p>
                        <span class="sm-stat-label"><?php esc_html_e( '(future payments that will be updated)', 'CTADZ-school-management' ); ?></span>
                    </div>
                </div>

            </div>

            <!-- Feature Status -->
            <div class="sm-info-box" style="margin-bottom: 30px;">
                <h3><?php esc_html_e( 'Feature Status', 'CTADZ-school-management' ); ?></h3>
                <?php if ( SM_Family_Discount::is_enabled() ) : ?>
                    <p>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <strong style="color: #46b450;"><?php esc_html_e( 'Family Discounts Enabled', 'CTADZ-school-management' ); ?></strong>
                    </p>
                    <p><?php esc_html_e( 'Discount tiers:', 'CTADZ-school-management' ); ?></p>
                    <ul>
                        <?php foreach ( SM_Family_Discount::get_discount_tiers() as $tier ) : ?>
                            <li>
                                <?php
                                printf(
                                    esc_html__( '%d students = %s%% discount', 'CTADZ-school-management' ),
                                    intval( $tier['students'] ),
                                    number_format( floatval( $tier['discount'] ), 1 )
                                );
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>
                        <span class="dashicons dashicons-warning" style="color: #f0ad4e;"></span>
                        <strong style="color: #f0ad4e;"><?php esc_html_e( 'Family Discounts Disabled', 'CTADZ-school-management' ); ?></strong>
                    </p>
                    <p>
                        <?php
                        printf(
                            esc_html__( 'Enable family discounts in %s to use this tool.', 'CTADZ-school-management' ),
                            '<a href="?page=school-management-settings">' . esc_html__( 'Settings', 'CTADZ-school-management' ) . '</a>'
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ( $total_families > 0 ) : ?>
                <!-- Families List -->
                <div class="sm-families-section" style="margin-bottom: 30px;">
                    <h2><?php esc_html_e( 'Detected Families', 'CTADZ-school-management' ); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Parent Name', 'CTADZ-school-management' ); ?></th>
                                <th><?php esc_html_e( 'Parent Phone', 'CTADZ-school-management' ); ?></th>
                                <th><?php esc_html_e( 'Number of Students', 'CTADZ-school-management' ); ?></th>
                                <th><?php esc_html_e( 'Applicable Discount', 'CTADZ-school-management' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $families as $family ) : ?>
                                <?php
                                // Calculate what discount would apply
                                $discount_percentage = 0;
                                if ( SM_Family_Discount::is_enabled() ) {
                                    $tiers = SM_Family_Discount::get_discount_tiers();
                                    foreach ( $tiers as $tier ) {
                                        if ( intval( $family->student_count ) >= intval( $tier['students'] ) ) {
                                            $discount_percentage = floatval( $tier['discount'] );
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $family->parent_name ?: __( '(No name)', 'CTADZ-school-management' ) ); ?></strong></td>
                                    <td><?php echo esc_html( $family->parent_phone ); ?></td>
                                    <td>
                                        <span class="sm-badge" style="background: #8e44ad; color: white; padding: 4px 10px; border-radius: 4px;">
                                            <?php echo intval( $family->student_count ); ?> <?php esc_html_e( 'students', 'CTADZ-school-management' ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ( $discount_percentage > 0 ) : ?>
                                            <span style="color: #46b450; font-weight: bold;">
                                                <?php echo number_format( $discount_percentage, 1 ); ?>%
                                            </span>
                                        <?php else : ?>
                                            <span style="color: #999;">
                                                <?php esc_html_e( 'No discount', 'CTADZ-school-management' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e( 'No families detected. A family is defined as 2 or more students with the same parent phone number.', 'CTADZ-school-management' ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Bulk Recalculation Tool -->
            <?php if ( SM_Family_Discount::is_enabled() && $total_families > 0 ) : ?>
                <div class="sm-bulk-recalc-section">
                    <h2><?php esc_html_e( 'Bulk Recalculation', 'CTADZ-school-management' ); ?></h2>

                    <div class="sm-warning-box">
                        <h4>
                            <span class="dashicons dashicons-warning" style="color: #f0ad4e;"></span>
                            <?php esc_html_e( 'Warning: Use with Caution', 'CTADZ-school-management' ); ?>
                        </h4>
                        <p><?php esc_html_e( 'This action will recalculate family discounts for ALL families and update their pending payment schedules. This operation:', 'CTADZ-school-management' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'Will update ALL pending and unpaid payment schedules with future due dates', 'CTADZ-school-management' ); ?></li>
                            <li><?php esc_html_e( 'Will NOT affect already paid or past payments', 'CTADZ-school-management' ); ?></li>
                            <li><?php esc_html_e( 'Will recalculate discounts based on current family composition', 'CTADZ-school-management' ); ?></li>
                            <li><?php esc_html_e( 'Cannot be undone (except by running it again)', 'CTADZ-school-management' ); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e( 'Tip:', 'CTADZ-school-management' ); ?></strong> <?php esc_html_e( 'In most cases, you should use the automatic recalculation (when parent phone changes) or the manual per-student recalculation button. Use this bulk tool only when necessary.', 'CTADZ-school-management' ); ?></p>
                    </div>

                    <form method="post" id="sm-bulk-recalc-form" style="margin-top: 20px;">
                        <?php wp_nonce_field( 'sm_bulk_recalculate_family_discounts' ); ?>

                        <p>
                            <label>
                                <input type="checkbox" id="confirm-bulk-recalc" required>
                                <strong><?php esc_html_e( 'I understand that this will update all pending payment schedules for all families', 'CTADZ-school-management' ); ?></strong>
                            </label>
                        </p>

                        <p>
                            <button type="submit" name="bulk_recalculate" class="button button-primary button-large" id="bulk-recalc-btn" disabled>
                                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                <?php esc_html_e( 'Recalculate All Family Discounts', 'CTADZ-school-management' ); ?>
                            </button>
                        </p>
                    </form>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const checkbox = document.getElementById('confirm-bulk-recalc');
                        const button = document.getElementById('bulk-recalc-btn');
                        const form = document.getElementById('sm-bulk-recalc-form');

                        checkbox.addEventListener('change', function() {
                            button.disabled = !this.checked;
                        });

                        form.addEventListener('submit', function(e) {
                            if (!confirm('<?php echo esc_js( __( 'Are you absolutely sure you want to recalculate family discounts for ALL families? This will affect ' . intval( $schedules_count ) . ' payment schedules.', 'CTADZ-school-management' ) ); ?>')) {
                                e.preventDefault();
                            }
                        });
                    });
                    </script>
                </div>
            <?php endif; ?>

        </div>

        <style>
        /* Stat cards */
        .sm-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sm-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sm-stat-icon .dashicons {
            font-size: 30px;
            color: white;
            width: 30px;
            height: 30px;
        }

        .sm-stat-content h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
            color: #23282d;
        }

        .sm-stat-content p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #50575e;
            font-weight: 600;
        }

        .sm-stat-label {
            font-size: 12px;
            color: #999;
        }

        /* Info box */
        .sm-info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #0073aa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sm-info-box h3 {
            margin-top: 0;
        }

        .sm-info-box ul {
            margin-left: 20px;
        }

        /* Warning box */
        .sm-warning-box {
            background: #fff3cd;
            border-left: 4px solid #f0ad4e;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .sm-warning-box h4 {
            margin-top: 0;
            color: #856404;
        }

        .sm-warning-box p,
        .sm-warning-box ul {
            color: #856404;
        }

        .sm-warning-box ul {
            margin-left: 20px;
        }

        /* Badge */
        .sm-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .sm-stat-card,
            .sm-info-box {
                background: #1a1a1a;
                border-color: #3a3a3a;
            }

            .sm-stat-content h3,
            .sm-stat-content p {
                color: #e0e0e0;
            }

            .sm-info-box h3,
            .sm-info-box p,
            .sm-info-box li {
                color: #e0e0e0;
            }

            .sm-warning-box {
                background: #2d2400;
                border-left-color: #f0ad4e;
            }

            .sm-warning-box h4,
            .sm-warning-box p,
            .sm-warning-box ul,
            .sm-warning-box li {
                color: #ffd54f;
            }
        }
        </style>
        <?php
    }

    /**
     * Handle bulk recalculation of family discounts
     */
    private static function handle_bulk_recalculation() {
        global $wpdb;

        $students_table = $wpdb->prefix . 'sm_students';

        // Get all unique normalized parent phones (to avoid processing same family multiple times with different formats)
        $parent_phones = $wpdb->get_col(
            "SELECT DISTINCT REGEXP_REPLACE(parent_phone, '[^0-9]', '') as normalized_phone
            FROM $students_table
            WHERE parent_phone IS NOT NULL AND parent_phone != ''"
        );

        if ( empty( $parent_phones ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'No students with parent phone numbers found.', 'CTADZ-school-management' ) . '</p></div>';
            return;
        }

        $total_updated = 0;
        $families_processed = 0;

        foreach ( $parent_phones as $normalized_phone ) {
            // Get one representative phone from this family for recalculation
            $representative_phone = $wpdb->get_var( $wpdb->prepare(
                "SELECT parent_phone
                FROM $students_table
                WHERE REGEXP_REPLACE(parent_phone, '[^0-9]', '') = %s
                LIMIT 1",
                $normalized_phone
            ) );

            if ( $representative_phone ) {
                $updated = SM_Family_Discount::recalculate_family_discounts( $representative_phone );
                $total_updated += $updated;
                if ( $updated > 0 ) {
                    $families_processed++;
                }
            }
        }

        if ( $total_updated > 0 ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            printf(
                esc_html__( 'Successfully recalculated family discounts! %1$d payment schedules updated across %2$d families.', 'CTADZ-school-management' ),
                intval( $total_updated ),
                intval( $families_processed )
            );
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>' . esc_html__( 'No payment schedules needed recalculation. All discounts are already up to date.', 'CTADZ-school-management' ) . '</p></div>';
        }
    }
}
