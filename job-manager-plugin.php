<?php
/*
Plugin Name: Jobs Post_types
Description: Create a custom job listing post type.
Version: 1.0
Author: Nihal
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'jobs-post-type.php';
require_once plugin_dir_path( __FILE__ ) . 'applicants-child-post-type.php';

register_activation_hook( __FILE__, 'jm_activation' );
register_deactivation_hook(__FILE__, 'jm_deactivation');
register_uninstall_hook(__FILE__, 'jm_uninstall');

function jm_activation() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'jm_applicants';

    $sql = "CREATE TABLE wp_jm_applicants (
        `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `date` DATE NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `cover` VARCHAR(255) NOT NULL,
        `job_id` INT UNSIGNED NOT NULL,
        `job_name` VARCHAR(255) NOT NULL,
        `status` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE = InnoDB;
    ";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function jm_deactivation() {}

function jm_uninstall() {
    global $wpdb;

    // Drop custom database table
    $table_name = $wpdb->prefix . 'jm_applicants';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
