<?php
    $button_color = (isset($args['button_color']) && $args['button_color'] != '') ? $args['button_color'] : 'mint';
?>

<button id="screen-email" class="button <?php echo $button_color; ?> round thin input-focus toggle-switcher" type="button" data-toggle="collapse" data-target="#login-email-results" aria-expanded="false" aria-controls="login-email-results">                                    
    <?php
    if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
        esc_html_e( 'Grabar o enviar sus respuestas por correo electrónico', 'mha_s2s' );
    } else {
        esc_html_e( 'Log in to Save Results', 'mha_s2s' );
    }
    ?>
</button>
