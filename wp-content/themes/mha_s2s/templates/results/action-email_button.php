
<button id="screen-email" class="button mint round thin input-focus toggle-switcher" type="button" data-toggle="collapse" data-target="#email-results" aria-expanded="false" aria-controls="email-results">                                    
    <?php
    if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
        esc_html_e( 'Enviar sus respuestas por correo electrónico', 'mha_s2s' );
    } else {
        esc_html_e( 'Email Results', 'mha_s2s' );
    }
    ?>
</button>
