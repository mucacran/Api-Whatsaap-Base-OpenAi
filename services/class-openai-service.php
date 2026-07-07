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
	 * Categories accepted by the application.
	 *
	 * @var string[]
	 */
	private const ALLOWED_CATEGORIES = array(
		'Hot Lead',
		'Warm Lead',
		'General Inquiry',
		'Support Request',
		'Vendor / Not Lead',
		'Unknown / Spam',
	);

	/**
	 * Sends a user message to OpenAI and returns structured lead metadata.
	 *
	 * @param string $message Incoming WhatsApp message.
	 * @return array
	 */
	public function classify_message( $message ) {
		$api_key       = Mucacran_Wa_Ai_Config::get( 'openai_api_key' );
		$model         = Mucacran_Wa_Ai_Config::get( 'openai_model' );
		$system_prompt = Mucacran_Wa_Ai_Config::get( 'system_prompt' );
		$fallback      = $this->fallback_classification( __( 'Classification is unavailable.', 'api-whatsaap-base-openai' ) );

		if ( '' === $api_key || '' === $model ) {
			return array(
				'success'        => false,
				'classification' => $fallback,
				'raw_response'   => '',
				'error'          => __( 'OpenAI settings are incomplete.', 'api-whatsaap-base-openai' ),
			);
		}

		$structured_instruction = implode(
			"\n",
			array(
				'Return only a valid JSON object. Do not use Markdown or code fences.',
				'The JSON keys must be: lead_category, confidence, reason, suggested_reply.',
				'lead_category must be exactly one of: ' . implode( ', ', self::ALLOWED_CATEGORIES ) . '.',
				'confidence must be an integer from 0 to 100.',
				'reason must be brief and based only on the incoming message.',
				'suggested_reply must be a short draft for a human operator to review. It has not been sent.',
			)
		);

		// This asks OpenAI for internal classification data, not a customer-facing message.
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
								'content' => trim( $system_prompt . "\n\n" . $structured_instruction ),
							),
							array(
								'role'    => 'user',
								'content' => $message,
							),
						),
						'temperature' => 0.2,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'        => false,
				'classification' => $fallback,
				'raw_response'   => '',
				'error'          => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return array(
				'success'        => false,
				'classification' => $fallback,
				'raw_response'   => '',
				'error'          => $data['error']['message'] ?? __( 'OpenAI API request failed.', 'api-whatsaap-base-openai' ),
				'data'           => $data,
			);
		}

		$raw_response   = trim( (string) ( $data['choices'][0]['message']['content'] ?? '' ) );
		$classification = $this->parse_classification( $raw_response );

		return array(
			'success'        => true,
			'classification' => $classification,
			'raw_response'   => $raw_response,
			'data'           => $data,
		);
	}

	/**
	 * Keeps compatibility for callers that still request a reply.
	 *
	 * The returned reply is only the operator draft and is not sent automatically.
	 *
	 * @param string $message Incoming WhatsApp message.
	 * @return array
	 */
	public function create_reply( $message ) {
		$result         = $this->classify_message( $message );
		$classification = $result['classification'];

		return array(
			'success' => $result['success'],
			'reply'   => $classification['suggested_reply'],
			'error'   => $result['error'] ?? '',
			'data'    => $result['data'] ?? array(),
		);
	}

	/**
	 * Converts an OpenAI JSON response into safe application values.
	 *
	 * @param string $raw_response Raw assistant response.
	 * @return array
	 */
	private function parse_classification( $raw_response ) {
		$json = trim( $raw_response );

		if ( preg_match( '/```(?:json)?\s*(.*?)```/is', $json, $matches ) ) {
			$json = trim( $matches[1] );
		}

		$first_brace = strpos( $json, '{' );
		$last_brace  = strrpos( $json, '}' );

		if ( false !== $first_brace && false !== $last_brace && $last_brace >= $first_brace ) {
			$json = substr( $json, $first_brace, $last_brace - $first_brace + 1 );
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return $this->fallback_classification( __( 'OpenAI returned an invalid classification format.', 'api-whatsaap-base-openai' ) );
		}

		$category   = $this->normalize_category( $decoded['lead_category'] ?? '' );
		$confidence = $this->normalize_confidence( $decoded['confidence'] ?? 0 );
		$reason     = $this->normalize_text( $decoded['reason'] ?? '' );
		$suggestion = $this->normalize_text( $decoded['suggested_reply'] ?? '' );

		if ( '' === $reason ) {
			$reason = __( 'No classification reason was provided.', 'api-whatsaap-base-openai' );
		}

		return array(
			'lead_category'  => $category,
			'confidence'     => $confidence,
			'reason'         => $reason,
			'suggested_reply' => $suggestion,
		);
	}

	/**
	 * Returns a valid category or the safest fallback category.
	 *
	 * @param mixed $category Category returned by OpenAI.
	 * @return string
	 */
	private function normalize_category( $category ) {
		$category = is_scalar( $category ) ? trim( (string) $category ) : '';

		foreach ( self::ALLOWED_CATEGORIES as $allowed_category ) {
			if ( 0 === strcasecmp( $category, $allowed_category ) ) {
				return $allowed_category;
			}
		}

		return 'Unknown / Spam';
	}

	/**
	 * Returns an integer confidence between 0 and 100.
	 *
	 * @param mixed $confidence Confidence returned by OpenAI.
	 * @return int
	 */
	private function normalize_confidence( $confidence ) {
		if ( is_string( $confidence ) ) {
			$confidence = str_replace( '%', '', $confidence );
		}

		if ( ! is_numeric( $confidence ) ) {
			return 0;
		}

		return min( 100, max( 0, (int) round( (float) $confidence ) ) );
	}

	/**
	 * Returns trimmed text without producing warnings for invalid JSON value types.
	 *
	 * @param mixed $value Value returned by OpenAI.
	 * @return string
	 */
	private function normalize_text( $value ) {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Builds safe values when classification cannot be completed.
	 *
	 * @param string $reason Fallback reason.
	 * @return array
	 */
	private function fallback_classification( $reason ) {
		return array(
			'lead_category'  => 'Unknown / Spam',
			'confidence'     => 0,
			'reason'         => $reason,
			'suggested_reply' => '',
		);
	}
}
