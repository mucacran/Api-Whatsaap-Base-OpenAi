<?php
/**
 * Plugin Name: WordPress Basic Plugin
 * Plugin URI: https://example.com/
 * Description: Plugin basico de WordPress con shortcode, pagina de ajustes y hooks de activacion/desactivacion.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://example.com/
 * Text Domain: wordpress-basic-plugin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WBP_VERSION', '1.0.0');
define('WBP_PLUGIN_FILE', __FILE__);
define('WBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WBP_PLUGIN_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'wbp_activate');
register_deactivation_hook(__FILE__, 'wbp_deactivate');

function wbp_activate()
{
    add_option('wbp_message', 'Hola desde WordPress Basic Plugin');
}

function wbp_deactivate()
{
    // Mantiene la configuracion para que no se pierda al desactivar.
}

add_action('plugins_loaded', 'wbp_load_textdomain');
function wbp_load_textdomain()
{
    load_plugin_textdomain('wordpress-basic-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('admin_menu', 'wbp_register_settings_page');
function wbp_register_settings_page()
{
    add_menu_page(
        __('WordPress Basic Plugin', 'wordpress-basic-plugin'),
        __('Basic Plugin', 'wordpress-basic-plugin'),
        'manage_options',
        'wordpress-basic-plugin',
        'wbp_render_settings_page',
        'dashicons-admin-plugins',
        25
    );
}

add_action('admin_init', 'wbp_register_settings');
function wbp_register_settings()
{
    register_setting(
        'wbp_settings',
        'wbp_message',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Hola desde WordPress Basic Plugin',
        )
    );

    add_settings_section(
        'wbp_main_section',
        __('Configuracion principal', 'wordpress-basic-plugin'),
        '__return_false',
        'wordpress-basic-plugin'
    );

    add_settings_field(
        'wbp_message',
        __('Mensaje', 'wordpress-basic-plugin'),
        'wbp_render_message_field',
        'wordpress-basic-plugin',
        'wbp_main_section'
    );
}

function wbp_render_message_field()
{
    $message = get_option('wbp_message', 'Hola desde WordPress Basic Plugin');
    ?>
    <input
        type="text"
        id="wbp_message"
        name="wbp_message"
        value="<?php echo esc_attr($message); ?>"
        class="regular-text"
    />
    <?php
}

function wbp_render_settings_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wbp_settings');
            do_settings_sections('wordpress-basic-plugin');
            submit_button(__('Guardar cambios', 'wordpress-basic-plugin'));
            ?>
        </form>
    </div>
    <?php
}

add_shortcode('basic_plugin_message', 'wbp_render_message_shortcode');
function wbp_render_message_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            'tag' => 'p',
        ),
        $atts,
        'basic_plugin_message'
    );

    $allowed_tags = array('p', 'div', 'span');
    $tag = in_array($atts['tag'], $allowed_tags, true) ? $atts['tag'] : 'p';
    $message = get_option('wbp_message', 'Hola desde WordPress Basic Plugin');

    return sprintf(
        '<%1$s class="wbp-message">%2$s</%1$s>',
        esc_attr($tag),
        esc_html($message)
    );
}
