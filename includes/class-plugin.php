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
		$controller = new Mucacran_Wa_Ai_Hello_World_Controller();
		$controllerSaludo = new Mucacran_Wa_Ai_Saludo_Controller();

		add_action( 'admin_menu', array( $controller, 'register_menu' ) );
		add_action( 'admin_menu', array( $controllerSaludo, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $controller, 'enqueue_assets' ) );
	}
}
