<?php
/**
 * Plugin Name: Api-Whatsaap-Base-OpenAi
 * Description: Hola mundo con estructura Modelo, Vista y Controlador.
 * Version: 1.5.0
 * Author: Api-Whatsaap-Base-OpenAi
 * Text Domain: api-whatsaap-base-openai
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MUCACRAN_WA_AI_VERSION', '1.5.0' );
define( 'MUCACRAN_WA_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MUCACRAN_WA_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'MUCACRAN_WA_AI_FILE', __FILE__ );

require_once MUCACRAN_WA_AI_PATH . 'includes/Models/class-hello-world-model.php';
require_once MUCACRAN_WA_AI_PATH . 'includes/Controllers/class-hello-world-controller.php';
require_once MUCACRAN_WA_AI_PATH . 'includes/class-plugin.php';

Mucacran_Wa_Ai_Plugin::init();
