<?php
/**
 * WordPress admin screens.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the Configuration and Conversations admin pages.
 */
class Mucacran_Wa_Ai_Admin {

	/**
	 * Registers only the two pages requested by the plugin.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WhatsApp + OpenAI', 'api-whatsaap-base-openai' ),
			__( 'WhatsApp + OpenAI', 'api-whatsaap-base-openai' ),
			'manage_options',
			MUCACRAN_WA_AI_PAGE_SLUG,
			array( $this, 'render_configuration_page' ),
			MUCACRAN_WA_AI_URL . 'public/images/logoMucacranAplicacion_20.svg',
			56
		);

		add_submenu_page(
			MUCACRAN_WA_AI_PAGE_SLUG,
			__( 'Configuration', 'api-whatsaap-base-openai' ),
			__( 'Configuration', 'api-whatsaap-base-openai' ),
			'manage_options',
			MUCACRAN_WA_AI_PAGE_SLUG,
			array( $this, 'render_configuration_page' )
		);

		add_submenu_page(
			MUCACRAN_WA_AI_PAGE_SLUG,
			__( 'Conversations', 'api-whatsaap-base-openai' ),
			__( 'Conversations', 'api-whatsaap-base-openai' ),
			'manage_options',
			MUCACRAN_WA_AI_PAGE_SLUG . '-conversations',
			array( $this, 'render_conversations_page' )
		);
	}

	/**
	 * Loads CSS and JS only on this plugin's pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, MUCACRAN_WA_AI_PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'mucacran-wa-ai-admin',
			MUCACRAN_WA_AI_URL . 'assets/css/admin.css',
			array(),
			MUCACRAN_WA_AI_VERSION
		);

		wp_enqueue_script(
			'mucacran-wa-ai-admin',
			MUCACRAN_WA_AI_URL . 'assets/js/admin.js',
			array(),
			MUCACRAN_WA_AI_VERSION,
			true
		);

		wp_localize_script(
			'mucacran-wa-ai-admin',
			'MucacranWaAi',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mucacran_wa_ai_admin' ),
				'i18n'    => array(
					'sending' => __( 'Sending...', 'api-whatsaap-base-openai' ),
					'send'    => __( 'Send', 'api-whatsaap-base-openai' ),
					'error'   => __( 'Something went wrong.', 'api-whatsaap-base-openai' ),
				),
			)
		);
	}

	/**
	 * Shows and saves the Configuration screen.
	 *
	 * @return void
	 */
	public function render_configuration_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'api-whatsaap-base-openai' ) );
		}

		if ( isset( $_POST['mucacran_wa_ai_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mucacran_wa_ai_settings_nonce'] ) ), 'mucacran_wa_ai_save_settings' ) ) {
			// This saves the API parameters so the plugin can talk to OpenAI and WhatsApp.
			Mucacran_Wa_Ai_Config::save_from_admin( $_POST );
			add_settings_error( 'mucacran_wa_ai_messages', 'settings_saved', __( 'Settings saved.', 'api-whatsaap-base-openai' ), 'updated' );
		}

		$settings = Mucacran_Wa_Ai_Config::all();

		include MUCACRAN_WA_AI_PATH . 'admin/settings-page.php';
	}

	/**
	 * Shows the chat window.
	 *
	 * @return void
	 */
	public function render_conversations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'api-whatsaap-base-openai' ) );
		}

		$conversations = Mucacran_Wa_Ai_DB::get_conversations();
		$selected_id   = isset( $_GET['conversation_id'] ) ? absint( $_GET['conversation_id'] ) : (int) ( $conversations[0]->id ?? 0 );
		$conversation  = $selected_id ? Mucacran_Wa_Ai_DB::get_conversation( $selected_id ) : null;
		$messages      = $selected_id ? Mucacran_Wa_Ai_DB::get_messages( $selected_id ) : array();

		if ( $selected_id ) {
			Mucacran_Wa_Ai_DB::mark_read( $selected_id );
		}

		include MUCACRAN_WA_AI_PATH . 'admin/conversations-page.php';
	}

	/**
	 * Returns messages for the selected conversation.
	 *
	 * @return void
	 */
	public function ajax_get_messages() {
		$this->verify_ajax_request();

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$conversation    = Mucacran_Wa_Ai_DB::get_conversation( $conversation_id );

		if ( ! $conversation ) {
			wp_send_json_error( array( 'message' => __( 'Conversation not found.', 'api-whatsaap-base-openai' ) ), 404 );
		}

		Mucacran_Wa_Ai_DB::mark_read( $conversation_id );

		wp_send_json_success(
			array(
				'conversation' => $this->format_conversation( $conversation ),
				'messages'     => array_map( array( $this, 'format_message' ), Mucacran_Wa_Ai_DB::get_messages( $conversation_id ) ),
			)
		);
	}

	/**
	 * Sends a manual admin reply through WhatsApp.
	 *
	 * @return void
	 */
	public function ajax_send_message() {
		$this->verify_ajax_request();

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$body            = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$conversation    = Mucacran_Wa_Ai_DB::get_conversation( $conversation_id );

		if ( ! $conversation || '' === $body ) {
			wp_send_json_error( array( 'message' => __( 'Choose a conversation and write a message.', 'api-whatsaap-base-openai' ) ), 400 );
		}

		$whatsapp = new Mucacran_Wa_Ai_WhatsApp_Service();
		$sent     = $whatsapp->send_text_message( $conversation->contact_phone, $body );

		Mucacran_Wa_Ai_DB::insert_message(
			array(
				'conversation_id'     => $conversation_id,
				'direction'           => 'outgoing',
				'sender_type'         => 'admin',
				'message_body'        => $body,
				'whatsapp_message_id' => $sent['whatsapp_message_id'] ?? '',
				'delivery_status'     => ! empty( $sent['success'] ) ? 'sent' : 'failed',
				'error_message'       => $sent['error'] ?? '',
			)
		);

		if ( empty( $sent['success'] ) ) {
			wp_send_json_error( array( 'message' => $sent['error'] ?? __( 'Message could not be sent.', 'api-whatsaap-base-openai' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Message sent.', 'api-whatsaap-base-openai' ),
			)
		);
	}

	/**
	 * Checks permissions and nonce for admin AJAX actions.
	 *
	 * @return void
	 */
	private function verify_ajax_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'api-whatsaap-base-openai' ) ), 403 );
		}

		check_ajax_referer( 'mucacran_wa_ai_admin', 'nonce' );
	}

	/**
	 * Formats a conversation for JavaScript.
	 *
	 * @param object $conversation Conversation row.
	 * @return array
	 */
	private function format_conversation( $conversation ) {
		return array(
			'id'            => (int) $conversation->id,
			'contact_name'  => (string) $conversation->contact_name,
			'contact_phone' => (string) $conversation->contact_phone,
		);
	}

	/**
	 * Formats a message for JavaScript.
	 *
	 * @param object $message Message row.
	 * @return array
	 */
	private function format_message( $message ) {
		return array(
			'id'              => (int) $message->id,
			'direction'       => (string) $message->direction,
			'sender_type'     => (string) $message->sender_type,
			'message_body'    => (string) $message->message_body,
			'delivery_status' => (string) $message->delivery_status,
			'error_message'   => (string) $message->error_message,
			'created_at'      => (string) mysql2date( 'Y-m-d H:i', $message->created_at ),
		);
	}
}
