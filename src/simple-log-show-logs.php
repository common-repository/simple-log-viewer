<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    function slvpl_get_latest_errors($request) {
        $num_linhas = $request->get_param('num_linhas'); // Get number of lines the request
        $log_file = SLVPL_UPLOADS_LOGS_DIR . 'logs-viewer.log';
        $logs = file_get_contents($log_file);
        $logs = explode("\n", $logs);
        $logs = array_filter($logs, function ($log) {
            return trim($log) !== '';
        });
        $logs = array_slice($logs, -$num_linhas); // Trims logs with a selected base number of lines
        return $logs;
    }

    // Add REST endpoint for the obtain logs
    add_action('rest_api_init', function () {
        register_rest_route('simplelogviewer/v1', '/errors', array(
            'methods' => 'GET',
            'callback' => 'slvpl_get_latest_errors',
            'permission_callback' => 'slvpl_check_logged_in_and_admin_user' // Check if admin user has logged
        ));
    });

    // Callback function for check if the user has logged and is a admin
    function slvpl_check_logged_in_and_admin_user() {
        return is_user_logged_in() && current_user_can('manage_options'); // Return true if user has logged and is a admin
    } 

    // Add custom css of errors lines log file
    function slvpl_log_viewer_admin_styles() {
        wp_register_style('admin-css', false);
        wp_enqueue_style('admin-css');

        $custom_css = "
        #slv-log-viewer {
            overflow: auto;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 1rem;
        }
        #dashboard-widgets #slvpl_log_viewer_dashboard_widget {
            width: 100% !important;
            height: auto !important;
            color: red;
            font-weight: 600;
        }";
        wp_add_inline_style('admin-css', $custom_css);
    }
    add_action('admin_enqueue_scripts', 'slvpl_log_viewer_admin_styles');
    
    function slvpl_log_viewer_dashboard_widget_function() {
        if (!defined('WP_DEBUG') || WP_DEBUG === false) {
            echo '<div class="notice notice-warning settings-error is-dismissible"><p><strong>' . esc_html__('Enable WP_DEBUG for error resolution purposes only. WordPress WP_DEBUG is located in the wp-config.php file, activate it to see errors', 'simple-log-viewer') . '</strong>' . ' <a href="' . esc_url(admin_url('admin.php?page=slvpl-log-viewer-settings')) . '">' . '  ' . esc_html__('know more', 'simple-log-viewer') .'</a>.</p></div>';
        }
    
        $num_linhas = isset($_POST['num_linhas']) ? absint($_POST['num_linhas']) : 1000;
        $num_linhas = in_array($num_linhas, array(1, 5, 250, 500, 1000, 1500)) ? $num_linhas : 1000;
    
        ?>
        <form method="post" action="">
            <label for="num_linhas_select" style="color: #000000!important"><?php echo esc_html__('Select the number of lines:', 'simple-log-viewer'); ?></label><br/>
            <select style="width: 100%; margin-bottom: 12px;" id="num_linhas_select" name="num_linhas" onchange="this.form.submit()">
                <?php
                $options = array(1, 5, 250, 500, 1000, 1500);
                foreach ($options as $option) {
                    ?>
                    <option value="<?php echo esc_attr($option); ?> "<?php echo selected($num_linhas, $option, false); ?>><?php echo esc_html($option); ?></option>
                    <?php
                }
                ?>
            </select>
        </form>
    
        <?php
    
        // Example for the log file path
        $log_file = SLVPL_UPLOADS_LOGS_DIR . 'logs-viewer.log';
    
        // Certify the log file exists before read
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
    
            // Check the log contents has been security before show
            $log_content = esc_html($log_content);
    
            // Check if empty log file
            if (empty($log_content)) {
                ?>
                <p><?php echo esc_html__('There are no errors in the log.', 'simple-log-viewer'); ?></p>
                <?php
            } else {
                // Show log content with selected a base number of lines
                $logs = explode("\n", $log_content);
                $logs = array_filter($logs, function ($log) {
                    return trim($log) !== '';
                });
                $logs = array_slice($logs, -$num_linhas); // Trims logs with a selected base number of lines
                
                ?>

                <p id="slv-success-message" style="color: green;"></p>
                
                <div id="slv-log-viewer" style="margin-bottom: 20px;"><?php echo wp_kses_post(implode("<br /><br />", $logs)); ?></div>

                <?php
    
                submit_button(esc_html__('Check Logs', 'simple-log-viewer'), 'primary', 'check-logs-button', false);
            }
        } else {
            ?>
                <p><?php echo esc_html__('Log file not found.', 'simple-log-viewer'); ?></p>
            <?php
        }
    }
    

    // Add a endpoint for the check manually logs
    function slvpl_manual_log_check() {
        check_ajax_referer('slvpl-nonce', 'nonce');

        $num_linhas = isset($_POST['num_linhas']) ? absint($_POST['num_linhas']) : 1000; // Define the number of lines wanted

        // Get latest manual logs
        $logs = slvpl_get_latest_errors_manual($num_linhas);

        // Example: Return menssage indicating that manual verification is complete
        $response = array('message' => __('Manual log check completed.', 'simple-log-viewer'), 'logs' => $logs);

        wp_send_json_success($response);
    }
    add_action('wp_ajax_slvpl_manual_log_check', 'slvpl_manual_log_check');

    // Get function that obtained manual logs
    function slvpl_get_latest_errors_manual($num_linhas) {
        $log_file = SLVPL_UPLOADS_LOGS_DIR . 'logs-viewer.log';
        $logs = file_get_contents($log_file);
        $logs = explode("\n", $logs);
        $logs = array_filter($logs, function ($log) {
            return trim($log) !== '';
        });
        $logs = array_slice($logs, -$num_linhas); // Trims logs with a selected base number of lines
        return $logs;
    }

