<?php
/**
 * WhatsApp webhook controller.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles Meta webhook requests.
 */
class Mucacran_Wa_Ai_Webhook_Controller {

	/**
	 * Adds the REST routes used by Meta.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'mucacran-wa-ai/v1',
			'/webhook',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'verify_webhook' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'receive_webhook' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Verifies the webhook token when Meta configures the endpoint.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function verify_webhook( WP_REST_Request $request ) {
		$mode      = sanitize_text_field( $request->get_param( 'hub_mode' ) ?: $request->get_param( 'hub.mode' ) );
		$token     = sanitize_text_field( $request->get_param( 'hub_verify_token' ) ?: $request->get_param( 'hub.verify_token' ) );
		$challenge = sanitize_text_field( $request->get_param( 'hub_challenge' ) ?: $request->get_param( 'hub.challenge' ) );

		if ( 'subscribe' === $mode && hash_equals( Mucacran_Wa_Ai_Config::get( 'webhook_verify_token' ), $token ) ) {
			return new WP_REST_Response( absint( $challenge ), 200 );
		}

		return new WP_REST_Response( 'Invalid verification token.', 403 );
	}

	/**
	 * Receives WhatsApp messages, stores them, and asks OpenAI for internal metadata.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function receive_webhook( WP_REST_Request $request ) {
		$raw_body = $request->get_body();

		if ( ! $this->is_valid_signature( $request, $raw_body ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid signature.' ), 403 );
		}

		$payload = json_decode( $raw_body, true );

		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid JSON.' ), 400 );
		}

		$messages = $this->extract_messages( $payload );

		foreach ( $messages as $message ) {
			$this->handle_incoming_message( $message, $payload );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Checks Meta's request signature when the app secret is configured.
	 *
	 * @param WP_REST_Request $request  REST request.
	 * @param string          $raw_body Raw request body.
	 * @return bool
	 */
	private function is_valid_signature( WP_REST_Request $request, $raw_body ) {
		$app_secret = Mucacran_Wa_Ai_Config::get( 'meta_app_secret' );

		if ( '' === $app_secret ) {
			return true;
		}

		$signature = $request->get_header( 'x-hub-signature-256' );

		if ( '' === $signature ) {
			return false;
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', $raw_body, $app_secret );

		return hash_equals( $expected, $signature );
	}

	/**
	 * Extracts the simple text messages this plugin supports.
	 *
	 * @param array $payload Webhook payload.
	 * @return array
	 */
	private function extract_messages( $payload ) {
		$messages = array();

		foreach ( $payload['entry'] ?? array() as $entry ) {
			foreach ( $entry['changes'] ?? array() as $change ) {
				$value    = $change['value'] ?? array();
				$contacts = $value['contacts'] ?? array();
				$contact  = $contacts[0] ?? array();

				foreach ( $value['messages'] ?? array() as $message ) {
					if ( 'text' !== ( $message['type'] ?? '' ) ) {
						continue;
					}

					$messages[] = array(
						'wa_id'               => sanitize_text_field( $contact['wa_id'] ?? $message['from'] ?? '' ),
						'contact_phone'       => sanitize_text_field( $message['from'] ?? '' ),
						'contact_name'        => sanitize_text_field( $contact['profile']['name'] ?? '' ),
						'message_body'        => sanitize_textarea_field( $message['text']['body'] ?? '' ),
						'whatsapp_message_id' => sanitize_text_field( $message['id'] ?? '' ),
					);
				}
			}
		}

		return $messages;
	}

	/**
	 * Stores one incoming message and saves its internal lead classification.
	 *
	 * @param array $message Incoming message.
	 * @param array $payload Full webhook payload.
	 * @return void
	 */
	private function handle_incoming_message( $message, $payload ) {
		if ( '' === $message['wa_id'] || '' === $message['contact_phone'] || '' === $message['message_body'] ) {
			return;
		}

		$conversation_id = Mucacran_Wa_Ai_DB::find_or_create_conversation(
			$message['wa_id'],
			$message['contact_phone'],
			$message['contact_name']
		);

		Mucacran_Wa_Ai_DB::insert_message(
			array(
				'conversation_id'     => $conversation_id,
				'direction'           => 'incoming',
				'sender_type'         => 'user',
				'message_type'        => 'text',
				'message_body'        => $message['message_body'],
				'whatsapp_message_id' => $message['whatsapp_message_id'],
				'raw_payload'         => $payload,
			)
		);

		$openai         = new Mucacran_Wa_Ai_OpenAI_Service();
		$result         = $openai->classify_message( $message['message_body'] );

		if ( empty( $result['success'] ) ) {
			error_log(
				sprintf(
					'[Mucacran WA AI] OpenAI classification failed for conversation %d: %s',
					$conversation_id,
					sanitize_text_field( $result['error'] ?? 'Unknown OpenAI error.' )
				)
			);
		}

		$classification = $result['classification'] ?? array();

		if ( ! is_array( $classification ) || empty( $classification['lead_category'] ) ) {
			error_log(
				sprintf(
					'[Mucacran WA AI] Classification metadata was missing for conversation %d.',
					$conversation_id
				)
			);
			return;
		}

		$classification['raw_classification_response'] = $result['raw_response'] ?? '';

		/* Classification is internal metadata. It is never inserted or sent as a chat message. */
		if ( ! Mucacran_Wa_Ai_DB::update_conversation_classification( $conversation_id, $classification ) ) {
			error_log(
				sprintf(
					'[Mucacran WA AI] Failed to save classification metadata for conversation %d.',
					$conversation_id
				)
			);
		}
	}
}
