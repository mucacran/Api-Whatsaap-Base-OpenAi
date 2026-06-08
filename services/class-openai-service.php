<?php
/**
 * OpenAI service.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets assistant replies for incoming WhatsApp messages.
 */
class Mucacran_Wa_Ai_OpenAI_Service {

	/**
	 * Sends a user message to OpenAI and returns the assistant text.
	 *
	 * @param string $message Incoming WhatsApp message.
	 * @return array
	 */
	public function create_reply( $message ) {
		$api_key       = Mucacran_Wa_Ai_Config::get( 'openai_api_key' );
		$model         = Mucacran_Wa_Ai_Config::get( 'openai_model' );
		$system_prompt = Mucacran_Wa_Ai_Config::get( 'system_prompt' );

		if ( '' === $api_key || '' === $model ) {
			return array(
				'success' => false,
				'error'   => __( 'OpenAI settings are incomplete.', 'api-whatsaap-base-openai' ),
			);
		}

		// This asks OpenAI for one assistant reply to the WhatsApp message.
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => $system_prompt,
							),
							array(
								'role'    => 'user',
								'content' => $message,
							),
						),
						'temperature' => 0.7,
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
				'error'   => $data['error']['message'] ?? __( 'OpenAI API request failed.', 'api-whatsaap-base-openai' ),
				'data'    => $data,
			);
		}

		return array(
			'success' => true,
			'reply'   => trim( $data['choices'][0]['message']['content'] ?? '' ),
			'data'    => $data,
		);
	}
}
