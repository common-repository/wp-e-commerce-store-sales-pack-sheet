<?php
global $wpdb;
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

$table_name = $wpdb->prefix . 'packsheet_deliveryzones'; 
$dropit = "DROP TABLE $table_name";
$wpdb->query( $wpdb->prepare( $dropit ) );