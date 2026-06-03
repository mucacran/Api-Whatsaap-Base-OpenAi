<?php
/**
 * Hello world model.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides data for the hello world view.
 */
final class Mucacran_Wa_Ai_Hello_World_Model {

	/**
	 * Returns the page title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Hola mundo de sergio', 'api-whatsaap-base-openai' );
	}

	/**
	 * Returns the page message.
	 *
	 * @return string
	 */
	public function get_message() {
		return __( 'Este es un plugin WordPress basico usando Modelo, Vista y Controlador.', 'api-whatsaap-base-openai' );
	}
}


final class Mucacran_Wa_Ai_Otra_Pagina_Model {

	
}