<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class saludoPersonalizado_Controller {

    private $page_slug;

    public function __construct() {
        $this->page_slug = MUCACRAN_WA_AI_PAGE_SLUG;
    }

    public function register_menu() {
        add_submenu_page(
            $this->page_slug,
            __( 'Saludo Personalizado', 'api-whatsaap-base-openai' ),
            __( 'Saludo Personalizado', 'api-whatsaap-base-openai' ),
            'manage_options',
            $this->page_slug . '-saludo',
            array( $this, 'renderSaludoPersonalizado' )
        );
    }

    public function renderSaludoPersonalizado() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para ver esta pagina.', 'api-whatsaap-base-openai' ) );
		}

		
		$modeloDePagina = "Soy Un Hijo de Dios";
		$modelo = new saludoPersonalizado_Model();
        $message_saludoPersonalizado = '';
        $saludoP = '';

        if (
            isset( $_POST['saludo_nonce_field'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['saludo_nonce_field'] ) ),
                'saludo_nonce_action'
            )
        ) {
            $saludoP = isset( $_POST['saludoP'] ) ? sanitize_text_field( wp_unslash( $_POST['saludoP'] ) ) : '';
            $message_saludoPersonalizado = $modelo->saludo( $saludoP );
        }


		


		require MUCACRAN_WA_AI_PATH . 'includes/Views/saludoPersonalizado.php';
	}



}
