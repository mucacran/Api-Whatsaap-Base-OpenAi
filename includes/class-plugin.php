<?php
/**
 * Main plugin bootstrap.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin hooks.
 */
final class Mucacran_Wa_Ai_Plugin {

	/**
	 * Starts the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		$admin   = new Mucacran_Wa_Ai_Admin();
		$webhook = new Mucacran_Wa_Ai_Webhook_Controller();

		Mucacran_Wa_Ai_DB::maybe_upgrade();

		add_action( 'admin_menu', array( $admin, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mucacran_wa_ai_get_messages', array( $admin, 'ajax_get_messages' ) );
		add_action( 'wp_ajax_mucacran_wa_ai_send_message', array( $admin, 'ajax_send_message' ) );
		add_action( 'rest_api_init', array( $webhook, 'register_routes' ) );
	}
}
