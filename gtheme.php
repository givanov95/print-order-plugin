<?php
/*
   Plugin Name: Rodinashop invoice
   Plugin URI: https://rodinashop.de/
   description: Plugin made for rodinashop.de
   Additional functionality plugin made for rodinashop.de 
   Version: 0.1
   Author: Georgi Ivanov
   Author URI: https://gwebsolutions.net
   License: GPL2
   */
// Start the session
session_start();
?>
<?php

if (!defined('ABSPATH')) exit;

function register_print_orders_menu()
{

    add_menu_page(
        __('Custom Menu Title', 'textdomain'),
        'Rodinashop invoice',
        'manage_options',
        '/print-orders-template/orders.php',
        '',
        plugins_url('print-orders-template'),
        6
    );
}
add_action('admin_menu', 'register_print_orders_menu');

// Create database if not exist on activation 

function activate_print_orders()
{


    // Initialize DB Tables
    function init_db_print_orders()
    {

        // WP Globals
        global $table_prefix, $wpdb;

        // Customer Table
        $customerTable = $table_prefix . 'invoices';

        // Query - Create Table
        $sql = "CREATE TABLE IF NOT EXISTS `$customerTable` (";
        $sql .= " `invoice_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, ";
        $sql .= " `invoice_order_id` INT UNSIGNED NOT NULL, ";
        $sql .= " `invoice_date` DATETIME DEFAULT CURRENT_TIMESTAMP ";
        $sql .= ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;";

        // Include Upgrade Script
        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        // Create Table - DB delta is Function used to create tables in WP
        dbDelta($sql);
    }
    
    // Insert DB Tables
    init_db_print_orders();
}
// Act on plugin activation
register_activation_hook(__FILE__, "activate_print_orders");



// Adding Meta container admin shop_order pages
add_action('add_meta_boxes', 'mv_add_meta_boxes');
if (!function_exists('mv_add_meta_boxes')) {
    function mv_add_meta_boxes()
    {
        add_meta_box('mv_other_fields', __('Rodinashop invoice', 'woocommerce'), 'mv_add_other_fields_for_packaging', 'shop_order', 'side', 'core');
    }
}

// Adding Meta field in the meta container admin shop_order pages
if (!function_exists('mv_add_other_fields_for_packaging')) {
    function mv_add_other_fields_for_packaging()
    {
        global $post;
        $order = wc_get_order($post->ID);
        $order_status  = $order->get_status();

        if ($order_status == "completed" || $order_status == "processing" ) {
            echo "<a href='/wp-admin/admin.php?page=print-orders-template%2Forders.php&order_id=$post->ID' class='button save_order button-primary' target='_blank'>Принтиране</a>";
        } else {
            echo "<a disabled class='button button-primary' target='_blank'>Принтиране</a>";
        }
    }
}


# -> Addd the button in the products table 

add_filter( 'manage_edit-shop_order_columns', 'add_column_to_wc_orders_table' );
 
function add_column_to_wc_orders_table( $columns ) {
    $columns['rodinashop_invoice'] = 'Rodinashop Invoice';
    return $columns;
}
 
add_action( 'manage_shop_order_posts_custom_column', 'add_button_in_orders_table' );
 
function add_button_in_orders_table( $column ) {
    
    global $post;
    $order = wc_get_order( $post->ID );

    if ( 'rodinashop_invoice' == $column ) {
        $order_status  = $order->get_status();
        if ($order_status == "completed" || $order_status == "processing" ) {
            echo "<a href='/wp-admin/admin.php?page=print-orders-template%2Forders.php&order_id=$post->ID' class='button save_order button-primary' target='_blank'>Принтиране</a>";
        } else {
            echo "<a disabled class='button button-primary' target='_blank'>Принтиране</a>";
        }
 
   
       
    }
}

?>