<a class="button mint round thin" id="screen-take" href="<?php echo $args['url']; ?>">
    <?php
    if ( isset( $args['espanol'] ) && $args['espanol'] == 1 ) {
        esc_html_e( 'Tomar otra prueba de salud mental', 'mha_s2s' );
    } else {
        esc_html_e( 'Take Another Mental Health Test', 'mha_s2s' );
    }
    ?>
</a>
