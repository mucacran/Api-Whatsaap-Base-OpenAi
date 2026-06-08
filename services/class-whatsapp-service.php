<?php
/**
 * WhatsApp Cloud API service.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends text messages through WhatsApp Cloud API.
 */
class Mucacran_Wa_Ai_WhatsApp_Service {

	/**
	 * Sends a plain text WhatsApp message.
	 *
	 * @param string $to   Recipient phone or wa_id.
	 * @param string $body Message text.
	 * @return array
	 */
	public function send_text_message( $to, $body ) {
		$token           = Mucacran_Wa_Ai_Config::get( 'whatsapp_access_token' );
		$phone_number_id = Mucacran_Wa_Ai_Config::get( 'whatsapp_phone_number_id' );

		if ( '' === $token || '' === $phone_number_id ) {
			return array(
				'success' => false,
				'error'   => __( 'WhatsApp settings are incomplete.', 'api-whatsaap-base-openai' ),
			);
		}

		$url = sprintf(
			'https://graph.facebook.com/%s/%s/messages',
			rawurlencode( MUCACRAN_WA_AI_GRAPH_VERSION ),
			rawurlencode( $phone_number_id )
		);

		// This sends the reply through Meta's WhatsApp Cloud API.
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'messaging_product' => 'whatsapp',
						'to'                => $to,
						'type'              => 'text',
						'text'              => array(
							'preview_url' => false,
							'body'        => $body,
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return array(
				'success' => false,
				'error'   => $data['error']['message'] ?? __( 'WhatsApp API request failed.', 'api-whatsaap-base-openai' ),
				'data'    => $data,
			);
		}

		return array(
			'success'             => true,
			'whatsapp_message_id' => $data['messages'][0]['id'] ?? '',
			'data'                => $data,
		);
	}
}
