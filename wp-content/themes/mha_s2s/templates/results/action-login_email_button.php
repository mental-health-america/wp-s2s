
<button id="screen-email" class="button mint round thin input-focus toggle-switcher" type="button" data-toggle="collapse" data-target="#login-email-results" aria-expanded="false" aria-controls="login-email-results">                                    
    <?php 
    if(!is_user_logged_in()):
        if($args['with_email'] == true){
            if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
                esc_html_e( 'Grabar o enviar sus respuestas por correo electrónico', 'mha_s2s' );
            } else {
                esc_html_e( 'Save or Email Results', 'mha_s2s' );
            }
        } else {
            if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
                esc_html_e( 'Grabar o enviar sus respuestas por correo electrónico', 'mha_s2s' );
            } else {
                esc_html_e( 'Log in to Save Results', 'mha_s2s' );
            }
        }
    else:
        if($args['with_email'] == true){
            if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
                esc_html_e( 'Enviar sus respuestas por correo electrónico', 'mha_s2s' );
            } else {
                esc_html_e( 'Email Results', 'mha_s2s' );
            }
        }
    endif;
    ?>
</button>
