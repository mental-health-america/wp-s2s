<?php 
/* Template Name: Screen Results */
get_header(); 
global $wpdb;

// The user's obfuscated custom ID
$user_screen_id = get_query_var('sid');

// Get the gravity forms entry ID for easier lookups
$entry_id = $wpdb->get_var("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_value = '$user_screen_id' ORDER BY id DESC"); 

if ( is_wp_error( $entry_id ) || !$entry_id ):

    // Entry doesn't exist, display an error
    echo '<div class="wrap narrow mb-5"><div id="message" class="error text-center"><p>This screen result does not exists.</p></div></div>';

else:

    // Entry exists, continue

    // Get Screen Results
    $user_screen_result = getUserScreenResults( $entry_id );  
    
    $next_step_terms = [];
    $next_step_manual = [];
    $excluded_ids = [];
    $result_cta = [];
    $demo_steps = [];
    $result_title = '';
    $max_score = get_field('overall_max_score', $user_screen_result['screen_id']); // Get the screen's overall max score
    $espanol = get_field('espanol', $user_screen_result['screen_id']); // Spanish page
    $partner_var = get_query_var('partner'); // Partner layout overrides
    $iframe_var = get_query_var('iframe'); // Template flags when site is viewed in an iframe

    // Global Default Options
    $global_hide_articles = get_field('global_hide_articles', 'options');
    if($global_hide_articles){
        foreach($global_hide_articles as $gha){
            $excluded_ids[] = $gha;
        }
    }

    $screen_results_hide_articles = get_field('screen_results_hide_articles', 'options');
    if($screen_results_hide_articles){
        foreach($screen_results_hide_articles as $srha){
            $excluded_ids[] = $srha;
        }
    }

    $url_exclude = get_query_var('exclude_ids');
    if($url_exclude){
        $url_exclude_array = explode(',',$url_exclude);
        foreach($url_exclude_array as $ue){
            $excluded_ids[] = $ue;
        }
    }
    
    // A/B Testing
    $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing

    // "Take another test" button URL
    $take_another_url = '/screening-tools/';
    if(
        $user_screen_result['referer'] != '' &&
        strpos($user_screen_result['referer'], 'screening.mhanational.org') === false &&
        strpos($user_screen_result['referer'], 'mhanationalstg.wpengine.com') === false
    ):
        $take_another_url = add_query_arg( 'ref', $user_screen_result['referer'], $take_another_url );
    endif;

    if($partner_var && in_array($partner_var, mha_approved_partners() )){                                    
        $take_another_url = add_query_arg( 'partner', $partner_var, $take_another_url );
    }
    if($iframe_var){                                         
        $take_another_url = add_query_arg( 'iframe','true', $take_another_url );
    }
    ?>
	
	<script>
        window.dataLayer.push({
			'event': 'screen_complete_d',
			'sid': '<?php echo $user_screen_id; ?>',
			'transaction_id': '<?php echo $user_screen_id; ?>',
			'screen_name': '<?php echo html_entity_decode( get_the_title($user_screen_result['screen_id']) ); ?>',
		});
    </script>

    <?php 
        /**
         * Introductory Header
         */
        get_template_part( 'templates/results/block', 'header', array('espanol' => $espanol) ); 
    ?>

    <div class="wrap narrow">
    <article class="screen screen-result">

        <?php 
            /**
             * Current Step
             */

            if(in_array('results_header_v1', $layout) || in_array('show_progress', $layout)){
                $last_progress_label = get_field('survey',$user_screen_result['screen_id']) ? 'Submit<br /> Survey' : 'Your<br />Results';
                if($espanol){
                    echo '<ol class="screen-progress-bar clearfix step-3-of-3">
                        <li class="step-1"><span>Preguntas<br />de la Prueba</span></li>
                        <li class="step-2"><span>Preguntas<br />Opcionales</span></li>
                        <li class="step-3"><span>Sus<br />Resultados</span></li>
                    </ol>';
                } else {
                    $demo_label = in_array('alt_demo_label', $layout) ? 'Optional<br />Questions' : 'Demographic<br />Information';
                    echo '<ol class="screen-progress-bar clearfix step-3-of-3">
                        <li class="step-1"><span>Test<br />Questions</span></li>
                        <li class="step-2"><span>'.$demo_label.'</span></li>
                        <li class="step-3"><span>'.$last_progress_label.'</span></li>
                    </ol>';
                }
            }
            
            /**
             * Login/Register Prompt (Top)
             */
            if(count(array_intersect( array('login_cta_top'), $layout))):
                echo '<div class="mb-4">';
                get_template_part( 'templates/results/cta', 'login', array( 'id' => $user_screen_result['result_id'] ) ); 
                echo '</div>';
            endif;

            /**
             * Screening Results
             */
            if( have_rows('results', $user_screen_result['screen_id']) ):
                                
                // Result Display
                while( have_rows('results', $user_screen_result['screen_id']) ) : the_row();
                    $min = get_sub_field('score_range_minimum');
                    $max = get_sub_field('score_range_max');
                    $custom_logic_condition_row = get_sub_field('custom_logic_condition');
                    
                    if(
                        $user_screen_result['total_score'] >= $min && $user_screen_result['total_score'] <= $max || 
                        $user_screen_result['has_advanced_conditions'] > 0 && $user_screen_result['advanced_condition_row'] == get_row_index() || 
                        isset($user_screen_result['custom_results_logic']) && $user_screen_result['custom_results_logic'] != '' && $user_screen_result['custom_result_row'] == $custom_logic_condition_row ){

                        // Advanced Condition Double Check (in case score condition passes)
                        if($user_screen_result['has_advanced_conditions'] > 0){
                            if($user_screen_result['advanced_condition_row'] != get_row_index()){ 
                                continue;
                            }
                        }

                        // Custom Condition Double Check (in case score condition passes)
                        if(isset($user_screen_result['custom_results_logic']) && $user_screen_result['custom_results_logic'] != ''){
                            if($user_screen_result['custom_result_row'] != $custom_logic_condition_row){ 
                                continue;
                            }
                        }
                        
                        // Required Tags Check
                        if(empty($user_screen_result['required_result_tags']) && !empty(get_sub_field('required_tags'))){
                            continue;
                        }

                        // Relevant Tags
                        if(get_sub_field('relevant_tags')){
                            $tags = get_sub_field('relevant_tags');
                            foreach($tags as $t){
                                $next_step_terms[] = $t;
                            }
                        }

                        // Manual Next Steps
                        $next = get_sub_field('featured_next_steps');
                        if($next){
                            foreach($next as $n){
                                if(isset($n['link']->ID)){
                                    $next_step_manual[] = $n['link']->ID;
                                }
                            }
                        }
                        
                        $featured_cta = get_sub_field('featured_call_to_actions');
                        if($featured_cta){
                            foreach($featured_cta as $cta){
                                $result_cta[] = $cta;
                            }
                        }
                        ?>

                            <?php 
                                /** 
                                 * Survey 
                                 */
                                if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                                    
                                    /**
                                     * Survey Results
                                     */
                                    $result = get_field('results', $user_screen_result['screen_id']);
                                    ?>
                                        <div class="bubble thin teal round-small-bl mb-4">
                                        <div class="inner">

                                        <span id="screen-name" style="display: none;"><?php echo get_the_title($user_screen_result['screen_id']); ?></span>
                                            <?php if(!get_field('show_survey_results', $user_screen_result['screen_id']) && !in_array('results_header_v1', $layout) ): ?>
                                                <h1 class="subtitle thin montserrat block pb-1">
                                                    Thank you for completing this survey!
                                                </h1>
                                                <h2 class="white small m-0">
                                                    <strong><?php echo get_the_title($user_screen_result['screen_id']); ?></strong>
                                                </h2>
                                            <?php else: ?>
                                                <h2 class="white small m-0">
                                                    <strong><?php echo $result[0]['result_title']; $result_title = $result[0]['result_title']; ?></strong>
                                                </h2>
                                            <?php endif; ?>
                                        </div>
                                        </div>
                                    <?php
                                else:
                                    /** 
                                     * Test Results
                                     */
                                    ?>
                                        <div class="bubble thin teal round-small-bl mb-4">
                                        <div class="inner">
                                            <h1 class="subtitle thin block pb-1 <?php if(in_array('results_header_v1', $layout)){ echo 'caps'; } else { echo 'montserrat'; } ?>">
                                                <?php if($espanol): ?>
                                                    Su resultado para la <span id="screen-name"><?php echo get_the_title($user_screen_result['screen_id']); ?></span> fue
                                                <?php else: ?>
                                                    <?php if(in_array('results_header_v1', $layout)): ?>
                                                        Your <span id="screen-name"><?php echo get_the_title($user_screen_result['screen_id']); ?></span> score was
                                                    <?php else: ?>
                                                        Your Results &mdash; <span id="screen-name"><?php echo get_the_title($user_screen_result['screen_id']); ?></span>:
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </h1>
                                            <h2 class="white small m-0">
                                                <strong><?php the_sub_field('result_title'); $result_title = get_sub_field('result_title'); ?></strong>
                                            </h2>
                                        </div>
                                        </div>
                                    <?php 
                                endif; 

                            ?>
                                        
                            <?php 
                                /**
                                 * Default Result Button Placement
                                 */
                                if(!count(array_intersect( array('result_buttons_below'), $layout))){
                                    get_template_part( 'templates/results/result', 'buttons', array( 
                                        'layout' => $layout,
                                        'user_screen_result' => $user_screen_result,
                                        'max_score' => $max_score,
                                        'espanol' => $espanol,
                                        'take_another_url' => $take_another_url,
                                        'iframe_var' => $iframe_var
                                    ) );  
                                }
                            ?>
                            
                            <div id="screen-result-content" class="pt-4">

                                <?php
                                    if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d', 'result_buttons_below'), $layout))){
                                        get_template_part( 'templates/results/action', 'email_display', array( 
                                            'width' => 'normal', 
                                            'show' => 0, 
                                            'screen_id' => $user_screen_result['screen_id'], 
                                            'user_screen_id' => $user_screen_id,
                                            'espanol' => $espanol,
                                            'entry_id' => $user_screen_result['result_id']
                                        ) ); 
                                    }
                                ?>
                                
                                <?php 
                                    /**
                                     * Default Your Answer/More Info Dropdowns Placement
                                     */
                                    if(!count(array_intersect( array('result_buttons_below'), $layout))){
                                        get_template_part( 'templates/results/result', 'dropdowns', array( 
                                            'layout' => $layout,
                                            'user_screen_result' => $user_screen_result,
                                            'espanol' => $espanol,
                                        ) );  
                                    }
                                ?>
                                
                                <div class="screen-result-content-inner d-print-none">
                                    <?php
                                        /**
                                         * Begin Result Content
                                         */

                                        // Alert message
                                        if($user_screen_result['alert'] > 0){
                                            echo '<div class="bold warning-message mb-4">';
                                                echo get_field('warning_message', $user_screen_result['screen_id']);
                                            echo '</div>';
                                        }

                                        // Additional scores to display
                                        if(have_rows('additional_results', $user_screen_result['screen_id'])):
                                            echo '<p class="additional-result-scores">';

                                                // Overall Score
                                                echo '<strong>Overall Score:</strong> '.$user_screen_result['total_score'].' / '.$max_score.'<br />';

                                                // Specific Score Groups
                                                while( have_rows('additional_results', $user_screen_result['screen_id']) ) : the_row();  
                                                    $add_scores = get_sub_field('scores');
                                                    $add_score_total = 0;
                                                    $add_score_max = 0;
                                                    foreach($add_scores as $score){
                                                        $add_score_total = intval($user_screen_result['general_score_data'][$score['question_id']]) + $add_score_total;
                                                        $add_score_max = $add_score_max + $user_screen_result['max_values'][$score['question_id']];
                                                    }

                                                    echo '<strong>'.get_sub_field('title').'</strong> '.$add_score_total.' / '.intval($add_score_max).'<br />';                                                
                                                endwhile;

                                            echo '</p>';
                                        endif;

                                        // Result content
                                        if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ){
                                            echo $result[0]['result_content'];
                                        } else {
                                            echo get_sub_field('result_content');
                                        }

                                        // Footer Content
                                        the_field('results_footer', $user_screen_result['screen_id']);
                                    ?>
                                </div>

                            </div>
                                    
                            <?php 
                                /**
                                 * A/B TestResult Button Placement
                                 */
                                if(count(array_intersect( array('result_buttons_below'), $layout))){

                                    get_template_part( 'templates/results/result', 'buttons', array( 
                                        'layout' => $layout,
                                        'user_screen_result' => $user_screen_result,
                                        'max_score' => $max_score,
                                        'espanol' => $espanol,
                                        'take_another_url' => $take_another_url,
                                        'iframe_var' => $iframe_var
                                    ) ); 
                                    
                                    get_template_part( 'templates/results/action', 'email_display', array( 
                                        'width' => 'normal', 
                                        'show' => 0, 
                                        'screen_id' => $user_screen_result['screen_id'], 
                                        'user_screen_id' => $user_screen_id,
                                        'espanol' => $espanol,
                                        'entry_id' => $user_screen_result['result_id']
                                    ) ); 

                                    get_template_part( 'templates/results/result', 'dropdowns', array( 
                                        'layout' => $layout,
                                        'user_screen_result' => $user_screen_result,
                                        'espanol' => $espanol,
                                    ) );  

                                }                                
                            ?>

                        <?php
                    }

                endwhile;
            endif;
        ?>
    </article>
    </div>


    <?php
        /**
        * Demographic Based Next Steps Data
        */

        // Get the user's answered demographic questions
        $entry_data = GFAPI::get_entry( $user_screen_result['result_id'] );
        $answered_demos = [];
        if(!is_wp_error($entry_data)){
            foreach($entry_data as $k => $v){            
                $field = GFFormsModel::get_field( $entry_data['form_id'], $k );  
                //if (isset($field->cssClass) && strpos($field->cssClass, 'optional') !== false || isset($field->cssClass) && strpos($field->cssClass, 'question') !== false || isset($field->cssClass) && strpos($field->cssClass, 'question-optional') !== false) {
                    if(trim($entry_data[$k]) != '' && isset($field->label)){
                        $answered_demos[$field->label][] = $entry_data[$k];
                    }
                //}
            }        
        }

        // Additional custom demo results to reference
        $answered_demos['user_result'] = array($result_title);
        $answered_demos['screen_id'] = array($user_screen_result['screen_id']);
        $answered_demos['result_id'] = array($user_screen_result['result_id']);

        // Screen specific demo steps/CTAs
        $demo_data = get_mha_demo_steps( $user_screen_result['screen_id'], $answered_demos );      
        foreach($demo_data['excluded_ids'] as $ex){ 
            $excluded_ids[] = $ex;
        }
        foreach($demo_data['demo_steps'] as $e){
            $demo_steps[] = $e;
        }
        foreach($demo_data['ctas'] as $e){
            $result_cta[] = $e;
        }

        // Global demo steps/CTAs
        $demo_data_global = get_mha_demo_steps( 'options', $answered_demos );
        foreach($demo_data_global['demo_steps'] as $e){
            $demo_steps[] = $e;
        }
        foreach($demo_data_global['ctas'] as $e){
            $result_cta[] = $e;
        }
            
        /*
        * Screen Specific CTAs
        */
        $screen_specific_cta = get_field('call_to_actions_all_results', $user_screen_result['screen_id']);
        if($screen_specific_cta){
            foreach($screen_specific_cta as $cta){
                $result_cta[] = $cta; // Add to our array for later
            }
        }
    ?>


    <div class="wrap normal pt-0 pb-3 d-print-none">

        <div class="py-4">
            <?php             
                if( !count(array_intersect( array('login_cta_top', 'actions_e', 'login_cta_blw_btns'), $layout)) && count(array_intersect( array('login_prompt_og'), $layout)) ):
                    get_template_part( 'templates/results/cta', 'login', array( 
                        'width' => 'narrow', 
                        'corners' => '', 
                        'iframe_var' => $iframe_var, 
                        'id' => $user_screen_result['result_id'] 
                    ) ); 
                endif;
            ?>
        </div>

        <?php
            
            /**
             * A/B Variant
             * Layout: actions_hide_ns_r
             */    
            if(!in_array('actions_hide_ns_r', $layout) && !in_array('actions_hide_nsh', $layout) ):
        ?>
            <?php if(!in_array('results_header_v1', $layout)): ?><div class="wrap narrow"><?php endif; ?>

                <h2 class="section-title dark-blue bold mb-3">
                    <?php if($espanol): ?>
                        Siguientes Pasos
                    <?php else: ?>
                        Next Steps
                    <?php endif; ?>
                </h2>

            <?php if(!in_array('results_header_v1', $layout)): ?></div><?php endif; ?>
        <?php endif; ?>
        

        <?php 
            /**
             * A/B Variant
             * Layout: actions_hide_ns
             */

            if(!in_array('actions_hide_ns', $layout)):          
                
                /**
                 * A/B Variant
                 * Layout: actions_ns_custom
                 */
                if(in_array('actions_ns_custom', $layout)): 

                    if( have_rows('next_steps', $user_screen_result['screen_id']) ):     
                    while( have_rows('next_steps', $user_screen_result['screen_id']) ) : the_row();    
                    
                        // Additional custom conditions
                        if( get_sub_field('layout_condition') != '' ){
                            $ns_custom_condition = array_map('trim', explode(',', get_sub_field('layout_condition')));
                            $ns_custom_total = count($ns_custom_condition);
                            $ns_custom_count = 0;
                            $layout_total = count($layout);

                            foreach($ns_custom_condition as $c){
                                if(in_array($c, $layout)){
                                    $ns_custom_count++;
                                }
                            }

                            if($ns_custom_count != $ns_custom_total){
                                continue;
                            }
                        } 
                        
                        $ns_custom_classes = get_sub_field('custom_classes') != '' ? get_sub_field('custom_classes') : 'bubble round-tl mb-5 mint';
                        ?>
                        <div class="<?php echo $ns_custom_classes; ?>">
                            <div class="inner">
                                <?php
                                    the_sub_field('content');
                                ?>
                            </div>
                        </div>
                        <?php 
                    endwhile;
                    endif;

                endif; 
                
            endif; // End 'actions_hide_ns'
        ?>    

        <?php 
            /**
             * A/B Variant
             * Layout: actions_b
             */
            if(in_array('actions_b', $layout)):
            ?>
                <div class="wrap narrow" id="layout-action_b">
                    <div id="screen-result-buttons-next_steps" class="button-grid pt-3 pb-3 px-0">
                        <?php 
                            if( !get_field('survey', $user_screen_result['screen_id']) || get_field('show_survey_results', $user_screen_result['screen_id']) ):
                                get_template_part( 'templates/results/action', 'email_button', array( 
                                    'espanol' => $espanol 
                                    ) ); 
                                get_template_part( 'templates/results/action', 'take_test', array( 
                                    'url' => $take_another_url, 
                                    'espanol' => $espanol 
                                ) ); 
                            endif; 
                        ?>
                    </div>
                    <?php 
                        get_template_part( 'templates/results/action', 'email_display', array( 
                            'width' => 'normal', 
                            'show' => 0, 
                            'screen_id' => $user_screen_result['screen_id'], 
                            'user_screen_id' => $user_screen_id,
                            'espanol' => $espanol,
                            'iframe_var' => $iframe_var,
                            'entry_id' => $user_screen_result['result_id']
                        )); 
                    ?>
                </div>        
            <?php 
            endif; 
        ?>

        <?php 
            /**
             * A/B Variant
             * Layout: actions_c
             */
            if(in_array('actions_c', $layout)): 
            ?>        
            <div class="wrap narrow" id="layout-action_c">
                <div id="screen-result-buttons-next_steps" class="button-grid pt-3 pb-3 px-0">
                    <?php 
                        if( !get_field('survey', $user_screen_result['screen_id']) || get_field('show_survey_results', $user_screen_result['screen_id']) ):
                            get_template_part( 'templates/results/action', 'login_email_button', array( 
                                'espanol' => $espanol, 
                                'with_email' => true 
                            ) ); 
                            get_template_part( 'templates/results/action', 'take_test', array( 
                                'url' => $take_another_url, 
                                'espanol' => $espanol 
                            ) ); 
                        endif; 
                    ?>
                </div>
                <div id="login-email-results" class="collapse">
                    <?php 
                        get_template_part( 'templates/results/action', 'login_email_display', array( 
                            'espanol' => $espanol, 
                            'id' => $user_screen_result['result_id'], 
                            'with_email' => true 
                        ) );
                        get_template_part( 'templates/results/action', 'email_display', array( 
                            'width' => 'normal', 
                            'show' => 1, 
                            'espanol' => $espanol, 
                            'screen_id' => $user_screen_result['screen_id'], 
                            'user_screen_id' => $user_screen_id,
                            'entry_id' => $user_screen_result['result_id']
                        ) ); 
                    ?>
                </div>
            </div>        
        <?php endif; ?>

        <?php 
            /**
             * A/B Variant
             * Layout: actions_d
             */
            if(in_array('actions_d', $layout)): 
            ?>          
            <div class="wrap narrow" id="layout-action_d">
                <div id="screen-result-buttons-next_steps" class="button-grid pt-3 pb-3 px-0">
                    <?php 
                        if( !get_field('survey', $user_screen_result['screen_id']) || get_field('show_survey_results', $user_screen_result['screen_id']) ):
                            if(!is_user_logged_in()):
                                get_template_part( 'templates/results/action', 'login_button', array( 
                                    'espanol' => $espanol 
                                ) ); 
                            endif;
                            get_template_part( 'templates/results/action', 'email_button', array( 
                                'espanol' => $espanol 
                            ) ); 
                            get_template_part( 'templates/results/action', 'take_test', array( 
                                'url' => $take_another_url, 
                                'espanol' => $espanol 
                            ) ); 
                        endif; 
                    ?>
                </div>
                <div id="login-email-results" class="collapse">
                    <?php get_template_part( 'templates/results/action', 'login_email_display', array( 'espanol' => $espanol, 'id' => $user_screen_result['result_id'], 'with_email' => false) ); ?>
                </div>
                <?php 
                    get_template_part( 'templates/results/action', 'email_display', array( 
                        'width' => 'normal', 
                        'show' => 0, 
                        'espanol' => $espanol,
                        'screen_id' => $user_screen_result['screen_id'], 
                        'user_screen_id' => $user_screen_id,
                        'entry_id' => $user_screen_result['result_id']
                    ) ); 
                ?>
            </div>        
        <?php endif; ?>

        <?php 
            /**
             * A/B Variant
             * Layout: actions_ns_top
             */
            if(!in_array('actions_ns_top_r', $layout)):          
        ?>        
            <div class="bubble round-tl mb-5 mint">
            <div class="inner">
                                    
                <?php if(get_field('next_steps_subtitle', $user_screen_result['screen_id'])): ?>
                    <h2 class="section-title cerulean small bold"><?php the_field('next_steps_subtitle', $user_screen_result['screen_id']); ?></h2>
                <?php endif; ?>
                
                <?php 
                    // Related Articles
                    $related_article_args = array(
                        'demo_steps'         => $demo_steps,
                        'next_step_manual'   => $next_step_manual,
                        'user_screen_result' => $user_screen_result,
                        'excluded_ids'       => $excluded_ids,
                        'next_step_terms'    => $next_step_terms,
                        'espanol'            => $espanol,
                        'iframe_var'         => $iframe_var,
                        'partner_var'        => $partner_var,
                        'total'              => 5,
                        'style'              => 'button',
                        'hide_all'           => true,
                        'layout'             => $layout,
                        'answered_demos'     => $answered_demos
                    );
                    if(in_array('related_v1', $layout)){
                        mha_results_related_articles_simple( $related_article_args );
                    } else {
                        $related_articles = mha_results_related_articles( $related_article_args );
                        $excluded_ids = $related_articles['excluded_ids'];
                        echo $related_articles['html'];
                    }
                ?>
            </div>
            </div>
        <?php endif; // Hide 'actions_ns_top_r' ?>

        <?php 
            /**
             * A/B Variant
             * Layout: actions_e
             */
            if(in_array('actions_e', $layout)): 
                if(!is_user_logged_in()):
                ?>          
                <div class="wrap normal mb-4" id="layout-action_e">
                    <div class="bubble round-tl mint normal">
                    <div class="inner">
                        <h2>Sign Up</h2>
                        <p>Did you know you can track your Mental Health Test results over time?</p>
                        <?php
                            if($iframe_var){    
                                $login_target = ' target="_blank"';
                            } else {
                                $login_target = '';
                            }
                        ?>
                        <a class="append-thought-id button teal round"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_screen_').$user_screen_result['result_id'] ?>">Log In or Create Account</a>
                    </div>
                    </div>
                </div>        
                <?php 
                endif;
            endif; 
        ?>
        
        
        <?php 
            /**
             * A/B Variant
             * Layout: actions_hide_ns
             */
            if(!in_array('actions_hide_ns', $layout)):

                if(!$espanol){
                    // All Screen CTAs
                    if( have_rows('actions_global_screening', 'option') ):
                    while( have_rows('actions_global_screening', 'option') ) : the_row();  
                        $action_option = get_sub_field('action');
                        $result_cta[] = get_sub_field('action');
                    endwhile;
                    endif;
                    wp_reset_postdata();
                    
                    // Global CTAs
                    if( have_rows('actions', 'option') ):
                    while( have_rows('actions', 'option') ) : the_row();  
                        $action_option = get_sub_field('action');
                        $result_cta[] = $action_option;
                    endwhile;
                    endif;
                    wp_reset_postdata();
                }

                $unique_result_cta = array_unique($result_cta);  
        ?>
        <div id="cta-col" class="cta-cols total-<?php echo count($unique_result_cta); ?>">
            <?php       
                /*
                * Result specific CTA
                */

                // Limit CTAs to 2 max
                $total_ctas = 0;
                $max_ctas = 2;
                if(count($unique_result_cta) > $max_ctas){
                    shuffle($unique_result_cta);
                    $unique_result_cta = array_slice($unique_result_cta, 0, $max_ctas);
                }

                // Veteran CTA Override 5/26/2023
                if(
                    isset($answered_demos['Which of the following populations describes you?']) && 
                    in_array('Veteran or active-duty military', $answered_demos['Which of the following populations describes you?'])
                ){
                    $unique_result_cta = array('126533');
                }

                global $post;
                foreach($unique_result_cta as $cta){
                    $post = get_post($cta); 
                    get_template_part( 'templates/blocks/block', 'cta' );  
                    $total_ctas++;
                } 
                wp_reset_postdata();
                
            ?>
        </div>
        <?php endif; // Hide 'actions_hide_ns' ?>

        <?php if(get_field('next_steps_subtitle', $user_screen_result['screen_id'])): ?>
            <?php if(!in_array('results_header_v1', $layout)): ?><div class="wrap narrow"><?php endif; ?>
            <h2 class="section-title cerulean small bold">
                <?php 
                if($espanol){
                    echo 'Más información y recursos';
                } else {
                    if(!in_array('actions_ns_top_r', $layout)){
                        echo 'More ';
                    }
                    the_field('next_steps_subtitle', $user_screen_result['screen_id']); 
                }
                ?>
            </h2>
            <?php if(!in_array('results_header_v1', $layout)): ?></div><?php endif; ?>
        <?php endif; ?>
        
    </div>

    <div class="wrap narrow mb-5 d-print-none">
        <?php 
            // Related Articles
            $related_article_args = array(
                'demo_steps'         => $demo_steps,
                'next_step_manual'   => $next_step_manual,
                'user_screen_result' => $user_screen_result,
                'excluded_ids'       => $excluded_ids,
                'next_step_terms'    => $next_step_terms,
                'espanol'            => $espanol,
                'espanol'            => $espanol,
                'total'              => 20,
                'partner_var'        => $partner_var,
                'answered_demos'     => $answered_demos,
                'layout'             => $layout
            );
            
            /**
             * A/B Variant
             * Layout: actions_hide_ns_r
             */
            if(!in_array('actions_ns_top_r', $layout)){
                //$related_article_args['skip'] = 5;
            }

            if(in_array('related_v1', $layout)){
                mha_results_related_articles_simple( $related_article_args );
            } else {
                $related_articles_2 = mha_results_related_articles( $related_article_args );
                $excluded_ids = $related_articles_2['excluded_ids'];
                echo $related_articles_2['html'];
            }
        ?>
    </div>

    <div class="wrap narrow mb-5 test-taken-time-link p-5 pb-0 d-none d-print-block">
        <?php
            wp_reset_query();
            echo _e('This test was taken on ');
            echo $user_screen_result['date'].'. ';
            echo _e('To view this result on the web, visit:'); 
        ?><br />
        <a class="no-after" href="<?php echo add_query_arg( 'sid', $user_screen_id, get_the_permalink($user_screen_result['screen_id']) ); ?>">
            <?php echo add_query_arg( 'sid', $user_screen_id, get_the_permalink() ); ?>
        </a><br />

        <?php 
            $condition_id = yoast_get_primary_term_id( 'condition', $user_screen_result['screen_id'] );
            $condition_page_id = null;
            $args = array(
                'post_type'  => 'page', 
                "post_status" => 'publish',
                "posts_per_page" => 1,
                'meta_query' => array( 
                    'relation' => 'AND',
                    array(
                        'key'   => '_wp_page_template', 
                        'value' => 'templates/page-path-collection.php'
                    ),
                    array(
                        'key'   => 'condition', 
                        'value' => $condition_id
                    )
                )
            );
            $loop = new WP_Query($args);
            if($loop->have_posts()):
            while($loop->have_posts()) : $loop->the_post();  
                $condition_page_id = get_the_ID();
            endwhile;
            endif;
            wp_reset_query();

            $condition_name = get_term_by( 'id', $condition_id, 'condition' );
        ?>
        <?php if($condition_page_id != null): ?>
            <br />Learn more about <?php echo $condition_name->name; ?> at <?php echo get_the_permalink( $condition_page_id ); ?>
        <?php endif; ?>
    </div>

<?php endif; ?>
    
<?php    
// Content Blocks
wp_reset_query();
if( have_rows('block') ):
while ( have_rows('block') ) : the_row();
    $row_layout = get_row_layout();
    if( get_template_part( 'templates/blocks/block', $row_layout ) ):
        get_template_part( 'templates/blocks/block', $row_layout );
    endif;
endwhile;
endif;

get_footer();