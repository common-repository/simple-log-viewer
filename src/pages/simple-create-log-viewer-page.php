<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    // Add settings page 
    add_action('admin_menu', 'slvpl_log_viewer_plugin_menu');

    function slvpl_log_viewer_plugin_menu() {
        add_menu_page(
            __('Simple Log Viewer Settings', 'simple-log-viewer'),
            'Simple Log Viewer',
            'manage_options',
            'slvpl-log-viewer-settings',
            'slvpl_log_viewer_settings_page',
            'dashicons-welcome-write-blog'
        );
    }