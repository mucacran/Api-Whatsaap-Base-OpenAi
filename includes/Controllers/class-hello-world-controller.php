<?php
/**
 * Hello world admin controller.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin page flow.
 */
final class Mucacran_Wa_Ai_Hello_World_Controller {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'api-whatsaap-base-openai';

	/**
	 * Admin page hook suffix returned by add_menu_page().
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Registers the admin menu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->hook_suffix = add_menu_page(
			__( 'Hola mundo', 'api-whatsaap-base-openai' ),
			__( 'Hola mundo', 'api-whatsaap-base-openai' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render' ),
			'dashicons-smiley',
			25
		);
	}

	/**
	 * Loads plugin assets only on this admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'mucacran-wa-ai-hello-world',
			MUCACRAN_WA_AI_URL . 'public/css/hello-world.css',
			array(),
			MUCACRAN_WA_AI_VERSION
		);
	}

	/**
	 * Renders the admin page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para ver esta pagina.', 'api-whatsaap-base-openai' ) );
		}

		$model = new Mucacran_Wa_Ai_Hello_World_Model();
		$title = $model->get_title();
		$message = $model->get_message();

		require MUCACRAN_WA_AI_PATH . 'includes/Views/admin-hello-world.php';
	}
}
