<?php
/**
 * Saludo admin controller.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}   



// formulario de saludo
echo "<h1>Hola mundo</h1>";
echo "<p>Este es un saludo personalizado.</p>";
?>

<form method="post" action="">
    <label for="nombre">Ingrese su nombre:</label>
    <input type="text" id="nombre" name="nombre" required>
    <input type="submit" value="Enviar">
    <?php wp_nonce_field( 'saludo_nonce_action', 'saludo_nonce_field' ); ?>
</form>

<?php
// Procesar el formulario
if ( isset( $_POST['nombre'] ) && check_admin_referer( 'saludo_nonce_action', 'saludo_nonce_field' ) ) {
    esc_html_e( 'Hola, ' . sanitize_text_field( $_POST['nombre'] ) . '! Bienvenido a nuestro sitio.', 'mucacran-wa-ai' );
}
?>