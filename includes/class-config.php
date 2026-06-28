<?php
/**
 * Saved plugin configuration.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and saves the API settings used by the plugin.
 */
class Mucacran_Wa_Ai_Config {

	const OPTION_NAME = 'mucacran_wa_ai_settings';

	/**
	 * Returns every saved setting with defaults for missing values.
	 *
	 * @return array
	 */
	public static function all() {
		/*
		 * Initial structured lead-classification behavior for the MVP.
		 * The suggested reply remains a draft for human review before final action.
		 */
		$default_system_prompt = implode(
			"\n",
			array(
				'You classify incoming WhatsApp customer messages for a human sales or support reviewer.',
				'Use exactly one Lead Category from: Hot Lead, Warm Lead, General Inquiry, Support Request, Vendor / Not Lead, Unknown / Spam.',
				'Return exactly these four lines and no additional headings:',
				'Lead Category: <allowed category>',
				'Confidence: <0-100%>',
				'Reason: <brief reason based only on the customer message>',
				'Suggested Reply: <short, professional draft reply for human review>',
				'Do not invent facts, promise a sale, confirm payment or booking, or claim that any final action has been completed. When the message lacks enough information, use the most cautious applicable category and explain the uncertainty.',
			)
		);

		$defaults = array(
			'openai_api_key'              => '',
			'openai_model'                => 'gpt-4o-mini',
			'system_prompt'               => $default_system_prompt,
			'whatsapp_access_token'       => '',
			'whatsapp_phone_number_id'    => '',
			'whatsapp_business_account_id' => '',
			'webhook_verify_token'        => '',
			'meta_app_secret'             => '',
		);

		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		/* Use the new classifier on existing installs that still have the old generic default. */
		if ( isset( $saved['system_prompt'] ) && 'You are a helpful WhatsApp assistant.' === trim( (string) $saved['system_prompt'] ) ) {
			$saved['system_prompt'] = $default_system_prompt;
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Returns one setting by key.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public static function get( $key ) {
		$settings = self::all();

		return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
	}

	/**
	 * Saves settings from the Configuration page.
	 *
	 * Sensitive fields keep their previous value when the input is empty.
	 *
	 * @param array $input Raw form values.
	 * @return void
	 */
	public static function save_from_admin( $input ) {
		$current = self::all();

		$settings = array(
			'openai_api_key'              => self::sanitize_secret( $input, 'openai_api_key', $current ),
			'openai_model'                => sanitize_text_field( wp_unslash( $input['openai_model'] ?? $current['openai_model'] ) ),
			'system_prompt'               => sanitize_textarea_field( wp_unslash( $input['system_prompt'] ?? $current['system_prompt'] ) ),
			'whatsapp_access_token'       => self::sanitize_secret( $input, 'whatsapp_access_token', $current ),
			'whatsapp_phone_number_id'    => sanitize_text_field( wp_unslash( $input['whatsapp_phone_number_id'] ?? '' ) ),
			'whatsapp_business_account_id' => sanitize_text_field( wp_unslash( $input['whatsapp_business_account_id'] ?? '' ) ),
			'webhook_verify_token'        => sanitize_text_field( wp_unslash( $input['webhook_verify_token'] ?? '' ) ),
			'meta_app_secret'             => self::sanitize_secret( $input, 'meta_app_secret', $current ),
		);

		update_option( self::OPTION_NAME, $settings, false );
	}

	/**
	 * Sanitizes a secret without deleting it when the admin leaves the field blank.
	 *
	 * @param array  $input   Raw form values.
	 * @param string $key     Setting key.
	 * @param array  $current Existing settings.
	 * @return string
	 */
	private static function sanitize_secret( $input, $key, $current ) {
		$value = isset( $input[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $input[ $key ] ) ) ) : '';

		return '' === $value ? (string) $current[ $key ] : $value;
	}

	/**
	 * Returns a short masked preview for a saved sensitive value.
	 *
	 * @param string $value Saved secret.
	 * @return string
	 */
	public static function mask_secret( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$last_four = substr( $value, -4 );

		return '********' . $last_four;
	}
}
