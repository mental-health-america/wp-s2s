<?php
    $button_color = (isset($args['button_color']) && $args['button_color'] != '') ? $args['button_color'] : 'mint';
?>

<button id="screen-email" class="button <?php echo $button_color; ?> round thin input-focus toggle-switcher" type="button" data-toggle="collapse" data-target="#login-email-results" aria-expanded="false" aria-controls="login-email-results">                                    
    <?php  echo ($espanol ? 'Grabar o enviar sus respuestas por correo electrónico' : 'Log in to Save Results'); ?>
</button>