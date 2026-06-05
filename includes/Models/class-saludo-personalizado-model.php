<?php

/**
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class saludoPersonalizado_Model {

  
    public function saludo($saludo) {
        if ( empty( $saludo ) ) {
            $saludo = 'Visitante.';
        }
        return __( "Hola, $saludo, niño feo", 'api-whatsaap-base-openai' );
    }
}