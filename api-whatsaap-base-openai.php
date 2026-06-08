<?php
/**
 * Plugin Name: Api-Whatsaap-Base-OpenAi
 * Description: Minimal WhatsApp Cloud API and OpenAI conversation plugin.
 * Version: 2.0.0
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

define( 'MUCACRAN_WA_AI_VERSION', '2.0.0' );
define( 'MUCACRAN_WA_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'MUCACRAN_WA_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'MUCACRAN_WA_AI_FILE', __FILE__ );
define( 'MUCACRAN_WA_AI_PAGE_SLUG', 'api-whatsaap-base-openai' );
define( 'MUCACRAN_WA_AI_TEXT_DOMAIN', 'api-whatsaap-base-openai' );
define( 'MUCACRAN_WA_AI_GRAPH_VERSION', 'v20.0' );

require_once MUCACRAN_WA_AI_PATH . 'includes/class-config.php';
require_once MUCACRAN_WA_AI_PATH . 'includes/class-db.php';
require_once MUCACRAN_WA_AI_PATH . 'services/class-openai-service.php';
require_once MUCACRAN_WA_AI_PATH . 'services/class-whatsapp-service.php';
require_once MUCACRAN_WA_AI_PATH . 'controllers/class-webhook-controller.php';
require_once MUCACRAN_WA_AI_PATH . 'includes/class-admin.php';
require_once MUCACRAN_WA_AI_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Mucacran_Wa_Ai_DB', 'activate' ) );

Mucacran_Wa_Ai_Plugin::init();
