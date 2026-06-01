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
    return;
}
