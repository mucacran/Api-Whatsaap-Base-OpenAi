<?php

/**
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class saludoPersonalizado_Model {

  
    public function saludo($saludoP) {
        if ( empty( $saludoP ) ) {
            $saludoP = 'Visitante.';
        }
        return sprintf(
            __( 'Hola, %s', 'api-whatsaap-base-openai' ),
            $saludoP
        );
    }
}