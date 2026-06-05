<?php
/**
 * Vista del formulario de saludo y resultado.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Saludo', 'api-whatsaap-base-openai' ); ?></h1>

    <form method="post">
        <?php wp_nonce_field( 'saludo_nonce_action', 'saludo_nonce_field' ); ?>

        <table class="form-table">
            <tr>
                <th><label for="saludo"><?php echo esc_html__( 'Nombre', 'api-whatsaap-base-openai' ); ?></label></th>
                <td>
                    <input name="saludo" type="text" id="saludo" class="regular-text" value="<?php echo isset( $saludo ) ? esc_attr( $saludo ) : ''; ?>" />
                </td>
            </tr>
        </table>

        <?php submit_button( esc_html__( 'Enviar', 'api-whatsaap-base-openai' ) ); ?>
    </form>

    <?php if ( ! empty( $greeting_message ) ) : ?>
        <div style="margin-top:20px;">
            <h2><?php echo esc_html( $greeting_message ); ?></h2>
        </div>
    <?php endif; ?>
</div>
