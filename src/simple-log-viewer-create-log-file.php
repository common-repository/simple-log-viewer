<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define the logs folder on uploads directory
$log_dir = wp_upload_dir()['basedir'] . '/simple-log-viewer/logs/';

// Check if the logs path exists, else exists try to create it
if ( ! file_exists( $log_dir ) ) {
    // Try to create the logs path
    if ( ! wp_mkdir_p( $log_dir ) ) {
        die( 'Unable to create logs folder: ' . $log_dir );
    }
}

// Define the full path for the log file
$log_file =  $log_dir . 'logs-viewer.log';

// If log file exists, else try to create it
if ( ! file_exists( $log_file ) ) {
    $handle = fopen( $log_file, 'w' ) or die( 'Unable to create log file: ' . $log_file );
    fclose( $handle );
}