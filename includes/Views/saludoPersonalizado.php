<?php
/**
 * Hello world admin view.
 *
 * @package MucacranWaAi
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap mucacran-wa-ai-page">
	<h1><?php echo esc_html( $modeloDePagina ); ?></h1>
</div>

<form method="post" action="">
	<?php wp_nonce_field( 'saludo_nonce_action', 'saludo_nonce_field' ); ?>
	<label for="saludoP">Ingrese su saludo personalizado:</label>
	<input type="text" id="saludoP" name="saludoP" value="<?php echo isset( $saludoP ) ? esc_attr( $saludoP ) : ''; ?>" required>
	<input type="submit" value="Enviar">
</form>


	<?php if ( ! empty( $message_saludoPersonalizado ) ) : ?>
        <div style="margin-top:20px;">
            <h2><?php echo esc_html( $message_saludoPersonalizado ); ?></h2>
        </div>
    <?php endif; ?>

