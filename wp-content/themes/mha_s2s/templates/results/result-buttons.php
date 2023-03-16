<?php 
    $layout = isset($args['layout']) ? $args['layout'] : array();
    $user_screen_result = $args['user_screen_result'];
    $max_score = isset($args['max_score']) ? $args['max_score'] : '';
    $espanol = isset($args['espanol']) ? $args['espanol'] : false;
    $take_another_url = isset($args['take_another_url']) ? $args['take_another_url'] : false;
?>

<div id="screen-result-buttons" class="button-grid pt-3 pb-3 pl-0 pr-0 pl-md-5 pr-md-5">

    <?php 
        if( !get_field('survey', $user_screen_result['screen_id']) || get_field('show_survey_results', $user_screen_result['screen_id']) ):
        ?>
            <button id="screen-about" class="button mint round thin" type="button" data-toggle="collapse" data-target="#score-interpretation" aria-expanded="false" aria-controls="score-interpretation">       
                <?php 
                    echo ($espanol ? 'Sobre su puntuación: ' : 'About your Score: '); 
                    echo $user_screen_result['total_score'].' / '.$max_score; 
                ?>    
            </button>

            <?php
                if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d'), $layout))){
                    get_template_part( 'templates/results/action', 'email_button', array( 
                        'espanol' => $espanol 
                    ) ); 
                }
            ?>
        <?php 
        endif; 
    ?>

    <button id="screen-answers" class="button mint round thin" type="button" data-toggle="collapse" data-target="#your-answers" aria-expanded="false" aria-controls="your-answers">
        <?php echo ($espanol ? 'Sus respuestas' : 'Your Answers'); ?>
    </button>
    <?php
        if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d', 'btn_hide_take_test'), $layout))){
            get_template_part( 'templates/results/action', 'take_test', array( 'url' => $take_another_url, 'espanol' => $espanol ) ); 
        }
    ?>

    <?php 
        if( count(array_intersect( array('btn_login_save', 'btn_login_save_blue'), $layout)) && !is_user_logged_in() ):
            $login_button_args = array( 
                'espanol' => $espanol
            );
            if( count(array_intersect( array('btn_login_save_blue'), $layout)) ){
                $login_button_args = array( 
                    'espanol' => $espanol,
                    'button_color' => 'blue'
                );
            }
            get_template_part( 'templates/results/action', 'login_button', $login_button_args ); 
        endif;
    ?>

</div>

<?php if( count(array_intersect( array('btn_login_save', 'btn_login_save_blue'), $layout)) && !is_user_logged_in() ): ?>
    <div id="login-email-results" class="collapse">
        <?php get_template_part( 'templates/results/action', 'login_email_display', array( 'espanol' => $espanol, 'id' => $user_screen_result['result_id'], 'with_email' => false) ); ?>
    </div>
<?php endif; ?>

<?php             
    if( count(array_intersect( array('login_cta_blw_btns'), $layout)) ):
        get_template_part( 'templates/results/cta', 'login', array( 
            'width' => 'narrow', 
            'corners' => '', 
            'iframe_var' => $iframe_var, 
            'id' => $user_screen_result['result_id'] 
        ) ); 
    endif;
?>