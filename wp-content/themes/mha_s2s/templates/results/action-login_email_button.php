
<button id="screen-email" class="button mint round thin input-focus toggle-switcher" type="button" data-toggle="collapse" data-target="#login-email-results" aria-expanded="false" aria-controls="login-email-results">                                    
    <?php 
    if(!is_user_logged_in()):
        if($args['with_email'] == true){
            echo ($args['espanol'] ? 'Grabar o enviar sus respuestas por correo electrónico' : 'Save or Email Results'); 
        } else {
            echo ($args['espanol'] ? 'Grabar o enviar sus respuestas por correo electrónico' : 'Log in to Save Results'); 
        }
    else:
        if($args['with_email'] == true){
            echo ($args['espanol'] ? 'Enviar sus respuestas por correo electrónico' : 'Email Results'); 
        }
    endif;
    ?>
</button>