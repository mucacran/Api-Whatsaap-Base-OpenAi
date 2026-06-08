<?php
/**
 * Configuration admin page.
 *
 * @package MucacranWaAi
 *
 * @var array $settings Saved settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap mucacran-wa-ai-page">
	<h1><?php echo esc_html__( 'Configuration', 'api-whatsaap-base-openai' ); ?></h1>
	<?php settings_errors( 'mucacran_wa_ai_messages' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'mucacran_wa_ai_save_settings', 'mucacran_wa_ai_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="openai_api_key"><?php echo esc_html__( 'OpenAI API Key', 'api-whatsaap-base-openai' ); ?></label></th>
				<td>
					<input type="password" id="openai_api_key" name="openai_api_key" class="regular-text" value="" placeholder="<?php echo esc_attr( Mucacran_Wa_Ai_Config::mask_secret( $settings['openai_api_key'] ) ); ?>" autocomplete="off">
					<p class="description"><?php echo esc_html__( 'Leave blank to keep the saved key.', 'api-whatsaap-base-openai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="openai_model"><?php echo esc_html__( 'OpenAI model', 'api-whatsaap-base-openai' ); ?></label></th>
				<td><input type="text" id="openai_model" name="openai_model" class="regular-text" value="<?php echo esc_attr( $settings['openai_model'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="system_prompt"><?php echo esc_html__( 'System prompt', 'api-whatsaap-base-openai' ); ?></label></th>
				<td><textarea id="system_prompt" name="system_prompt" class="large-text" rows="6"><?php echo esc_textarea( $settings['system_prompt'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="whatsapp_access_token"><?php echo esc_html__( 'WhatsApp Access Token', 'api-whatsaap-base-openai' ); ?></label></th>
				<td>
					<input type="password" id="whatsapp_access_token" name="whatsapp_access_token" class="regular-text" value="" placeholder="<?php echo esc_attr( Mucacran_Wa_Ai_Config::mask_secret( $settings['whatsapp_access_token'] ) ); ?>" autocomplete="off">
					<p class="description"><?php echo esc_html__( 'Leave blank to keep the saved token.', 'api-whatsaap-base-openai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="whatsapp_phone_number_id"><?php echo esc_html__( 'WhatsApp Phone Number ID', 'api-whatsaap-base-openai' ); ?></label></th>
				<td><input type="text" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" class="regular-text" value="<?php echo esc_attr( $settings['whatsapp_phone_number_id'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="whatsapp_business_account_id"><?php echo esc_html__( 'WhatsApp Business Account ID', 'api-whatsaap-base-openai' ); ?></label></th>
				<td><input type="text" id="whatsapp_business_account_id" name="whatsapp_business_account_id" class="regular-text" value="<?php echo esc_attr( $settings['whatsapp_business_account_id'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="webhook_verify_token"><?php echo esc_html__( 'Webhook Verify Token', 'api-whatsaap-base-openai' ); ?></label></th>
				<td><input type="text" id="webhook_verify_token" name="webhook_verify_token" class="regular-text" value="<?php echo esc_attr( $settings['webhook_verify_token'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="meta_app_secret"><?php echo esc_html__( 'Meta App Secret', 'api-whatsaap-base-openai' ); ?></label></th>
				<td>
					<input type="password" id="meta_app_secret" name="meta_app_secret" class="regular-text" value="" placeholder="<?php echo esc_attr( Mucacran_Wa_Ai_Config::mask_secret( $settings['meta_app_secret'] ) ); ?>" autocomplete="off">
					<p class="description"><?php echo esc_html__( 'Optional, but recommended for webhook signature validation. Leave blank to keep the saved secret.', 'api-whatsaap-base-openai' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Configuration', 'api-whatsaap-base-openai' ) ); ?>
	</form>
</div>
