<?php
/**
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Como desde aqui podria crear un sub menu dentro del menu principal, y que al hacer click en el sub menu se muestre una pagina diferente a la del menu principal, pero usando el mismo controlador?
 * o sea crear una pagina de admin
 * para leer nombre enviados por post
 * Validar un nonce.
 *Usar wp_unslash().
 * Sanitizar con sanitize_text_field().
 * Llamar al modelo.
 * Cargar la vista.
 */
final class Mucacran_Wa_Ai_Saludo_Controller {
    /**
     * Admin page slug.
     *
     * @var string
     */
    private $page_slug;

    public function __construct() {
        $this->page_slug = MUCACRAN_WA_AI_PAGE_SLUG;
    }
    

    /**
     * Registers the admin menu page.
     *
     * @return void
     */
    public function register_menu() {
        add_submenu_page(
            $this->page_slug,
            __( 'Saludo', 'api-whatsaap-base-openai' ),
            __( 'Saludo', 'api-whatsaap-base-openai' ),
            'manage_options',
            $this->page_slug . '-saludo',
            array( $this, 'render_saludo' )
        );
    }

    public function render_saludo() {
        $model = new saludoPersonalizado_Model();

        if ( isset( $_POST['saludo'] ) && check_admin_referer( 'saludo_nonce_action', 'saludo_nonce_field' ) ) {
            $saludo = sanitize_text_field( wp_unslash( $_POST['saludo'] ) );
            $greeting_message = $model->saludo( $saludo );
        } else {
            $greeting_message = '';
        }

        //require MUCACRAN_WA_AI_PATH . 'includes/Views/saludo-view.php';
        require MUCACRAN_WA_AI_PATH . 'includes/Views/greeting-view.php';
    }
}