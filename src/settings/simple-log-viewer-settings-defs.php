<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Create the settings page plugin
function slvpl_log_viewer_settings_page() {
    if ( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        ?>
        <div id="message" class="updated notice is-dismissible"><p><?php echo esc_html(__('Changes made successfully!', 'simple-log-viewer')); ?></p></div>
        <?php
    }
    ?>
    <div class="wrap">
        <h2><?php echo esc_html(__('Simple Log Viewer Settings', 'simple-log-viewer')); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('slvpl-log-viewer-settings');
            do_settings_sections('slvpl-log-viewer-settings');
            submit_button();
            ?>
            <?php wp_nonce_field( 'slvpl_save_settings', 'slvpl_settings_nonce' ); ?>
        </form>
    </div>
    <?php
}

// Add settings options
add_action('admin_init', 'slvpl_log_viewer_register_settings');

function slvpl_log_viewer_register_settings() {
    // Register the settings with sanitizing
    register_setting('slvpl-log-viewer-settings', 'slvpl_log_viewer_clear_logs');
    add_settings_section('slvpl_log_viewer_main', __('Main Options', 'simple-log-viewer'), 'slvpl_log_viewer_main_section_cb', 'slvpl-log-viewer-settings');
    add_settings_field('slvpl_log_viewer_clear_logs', __('Clear Log File', 'simple-log-viewer'), 'slvpl_log_viewer_clear_logs_field_cb', 'slvpl-log-viewer-settings', 'slvpl_log_viewer_main');
    add_settings_section('slvpl_log_viewer_debug', __('Debug', 'simple-log-viewer'), 'slvpl_log_viewer_debug_section_cb', 'slvpl-log-viewer-settings');
    add_settings_field('slvpl_log_viewer_enable_debug', __('Enable WP_DEBUG', 'simple-log-viewer'), 'slvpl_log_viewer_enable_debug_callback', 'slvpl-log-viewer-settings', 'slvpl_log_viewer_debug');
}

// Callback function for the main section
function slvpl_log_viewer_main_section_cb() {
    ?>
        <p><?php echo esc_html__('Configure the main options of Simple Log Viewer.', 'simple-log-viewer'); ?></p>
    <?php
}

function slvpl_log_viewer_admin_notice() {
    //$clear_logs = get_option('slv_log_viewer_clear_logs');

    $log_file = SLVPL_UPLOADS_LOGS_DIR . 'logs-viewer.log';
    $log_content = file_get_contents($log_file);

    if (file_exists($log_file) && !empty($log_content)) {
        ?>
            <div class="notice notice-warning settings-error is-dismissible"><p><?php echo esc_html__('Enable the log cleaning option to generate a new log file', 'simple-log-viewer'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=slvpl-log-viewer-settings')); ?>"> <?php echo esc_html__('know more', 'simple-log-viewer'); ?></a>.</p></div>
        <?php
    }
}
add_action('admin_notices', 'slvpl_log_viewer_admin_notice');

// Callback function for the input "Clear Log File"
function slvpl_log_viewer_clear_logs_field_cb() {
    ?>
    <label>
        <input type="submit" class="button button-primary" name="slvpl_log_viewer_clear_logs" value="<?php esc_attr_e('To clean', 'simple-log-viewer'); ?>" />
    </label>
    <?php
}

function slvpl_log_viewer_shutdown_handler() {
    if (isset($_POST['slvpl_log_viewer_clear_logs'])) {
        // Regenerate log file if the press button
        file_put_contents(SLVPL_UPLOADS_LOGS_DIR . 'logs-viewer.log', '');
    }
}
register_shutdown_function('slvpl_log_viewer_shutdown_handler');

function slvpl_enable_wp_debug() {
    $wp_config_path = ABSPATH . 'wp-config.php'; // Path wp-config.php file
    
    if (file_exists($wp_config_path)) {
        $wp_config_content = file_get_contents($wp_config_path); // Get the wp-config.php content file
    
        // Check if the WP_DEBUG constant defined
        if (preg_match('/\bdefine\s*\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*(true|false)\s*\);/', $wp_config_content, $matches)) {
            // The WP_DEBUG constant is define, now check  if defined false and replace for true
            if ($matches[1] === 'false') {
                $wp_config_content = preg_replace('/\bdefine\s*\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*false\s*\);/', "define( 'WP_DEBUG', true );", $wp_config_content);
            }
        } else {
            // The WP_DEBUG constant does not exist, let's add after tag <?php only if not present
            $wp_config_content = preg_replace('/<\?php/', "<?php\ndefine( 'WP_DEBUG', true );", $wp_config_content, 1);
        }

        // Add WP_DEBUG_DISPLAY constant if not exist
        if (!preg_match('/\bdefine\s*\(\s*[\'"]WP_DEBUG_DISPLAY[\'"]/', $wp_config_content)) {
            $wp_config_content = preg_replace('/<\?php/', "<?php\ndefine( 'WP_DEBUG_DISPLAY', false );", $wp_config_content, 1);
        }

        // Add WP_DISABLE_FATAL_ERROR_HANDLER if not exists
        if (!preg_match('/\bdefine\s*\(\s*[\'"]WP_DISABLE_FATAL_ERROR_HANDLER[\'"]/', $wp_config_content)) {
            $wp_config_content = preg_replace('/<\?php/', "<?php\ndefine( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );", $wp_config_content, 1);
        }
    
        // Save the update content in the wp-config.php file
        file_put_contents($wp_config_path, $wp_config_content);
    }
}

// Function for handle settings admin pannel input for WP_DEBUG enable
function slvpl_log_viewer_enable_debug_callback() {
    $is_debug_enabled = get_option('slvpl_enable_debug'); // Get the save checkbox state
    ?>
    <input type="checkbox" name="slvpl_enable_debug" value="1" <?php checked( $is_debug_enabled, true ); ?>>
    <?php
}

// Function for disable WP_DEBUG
function slvpl_disable_wp_debug() {
    $wp_config_path = ABSPATH . 'wp-config.php'; // Path wp-config.php file

    if (file_exists($wp_config_path)) {
        $wp_config_content = file_get_contents($wp_config_path); // wp-config.php file get contents

        // Remove the WP_DEBUG define
        $wp_config_content = preg_replace('/\bdefine\s*\(\s*[\'"]WP_DEBUG[\'"]\s*,\s*(true|false)\s*\);/', "define( 'WP_DEBUG', false );", $wp_config_content);

        // Remove the WP_DEBUG_DISPLAY define
        $wp_config_content = preg_replace('/\bdefine\s*\(\s*[\'"]WP_DEBUG_DISPLAY[\'"]\s*,\s*(true|false)\s*\);/', "", $wp_config_content);

        // Log update content wp-config.php file
        file_put_contents($wp_config_path, $wp_config_content);
    }
}

// Check function if save button has been clicked and WP_DEBUG active
function slvpl_log_viewer_save_settings() {
    if (isset($_POST['slvpl_settings_nonce']) && wp_verify_nonce( $_POST['slvpl_settings_nonce'], 'slvpl_save_settings' )) {
        $enable_debug = isset($_POST['slvpl_enable_debug']) ? true : false;
        update_option('slvpl_enable_debug', $enable_debug); // Save WordPress option checkbox state 

        if ($enable_debug && current_user_can('manage_options')) { // Check if the user have manage options capacity
            slvpl_enable_wp_debug();
        } else {
            // WP_DEBUG disable if the checkbox field not marked
            slvpl_disable_wp_debug();
        }
    }
}

// Callback function for debug section
function slvpl_log_viewer_debug_section_cb() {
    ?>
        <p><?php echo esc_html(__('Use this section to enable WP_DEBUG.', 'simple-log-viewer')); ?></p>
    <?php
}
    
// Callback for save the settings
add_action('admin_init', 'slvpl_log_viewer_save_settings');
    
// Register settings and options fields
add_action('admin_init', 'slvpl_log_viewer_register_settings');