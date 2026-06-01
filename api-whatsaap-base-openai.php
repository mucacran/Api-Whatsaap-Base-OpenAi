<?php
/**
 * Plugin Name: Api-Whatsaap-Base-OpenAi
 * Description: Plugin basico con acceso desde el menu lateral de WordPress.
 * Version: 1.0.0
 * Author: Api-Whatsaap-Base-OpenAi
 * Text Domain: api-whatsaap-base-openai
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */

if (! defined('ABSPATH')) {
    exit;
}

define( 'MUCACRAN_WA_AI_VERSION', '1.3.0' );
define( 'MUCACRAN_WA_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MUCACRAN_WA_AI_URL', plugin_dir_url( __FILE__ ) );

add_action('admin_menu', 'awbo_register_admin_menu');

function awbo_register_admin_menu()
{
    add_menu_page(
        'Api-Whatsaap-Base-OpenAi',
        'Api-Whatsaap-Base-OpenAi',
        'manage_options',
        'api-whatsaap-base-openai',
        'awbo_render_admin_page',
        'dashicons-admin-generic',
        25
    );
}

function awbo_render_admin_page()
{
    echo MUCACRAN_WA_AI_PATH;
    echo "<br>";
    echo MUCACRAN_WA_AI_URL;
    echo "<br>";


    if ( is_admin() ) {
        echo "Estas en admin";
    }else{
        echo "Salio falso, no estas en admin";
    }
    echo "<br>";
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

    echo $request_uri;

    echo "<br>";

    if ( empty( $request_uri ) ) {
        echo empty( $request_uri );
    }else{
        echo "paso lo contrario";
    }
}
