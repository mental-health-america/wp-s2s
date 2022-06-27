<?php 
/* Template Name: Screen Results */
get_header(); 
global $wpdb;

 // The user's obfuscated custom ID
$user_screen_id = get_query_var('sid');

// Get the gravity forms entry ID for easier lookups
$entry_id = $wpdb->get_var("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_value = '$user_screen_id' ORDER BY id DESC"); 

if ( is_wp_error( $entry_id ) || !$entry_id ):

    // Entry doesn't exist,
    echo '<div class="wrap narrow mb-5"><div id="message" class="error text-center"><p>This screen result does not exists.</p></div></div>';

else:

    // Entry exists, continue

    // Get Screen Results
    $user_screen_result = getUserScreenResults( $entry_id );  
    
    $next_step_terms = [];
    $next_step_manual = [];
    $exclude_ids = [];
    $result_cta = [];
    $demo_steps = [];
    $result_title = '';
    $max_score = get_field('overall_max_score', $user_screen_result['screen_id']); // Get the screen's overall max score
    $espanol = get_field('espanol', $user_screen_result['screen_id']); // Spanish page?
    $partner_var = get_query_var('partner'); // Partner layout overrides
    $iframe_var = get_query_var('iframe'); // Template flags when site is viewed in an iframe

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

    <div class="wrap normal">
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="page-heading plain">			
                <?php 
                    if($espanol){
                        echo '<h1 class="entry-title">Sus Resultados</h1>';
                    } else {
                        the_title( '<h1 class="entry-title">', '</h1>' ); 
                    }
                ?>
            </div>
            <div class="page-intro">
                <?php the_content(); ?>				
            </div>
        </article>
    </div>

    <div class="wrap narrow">
    <article class="screen screen-result">

        <?php 
            /**
             * Current Step
             */
            if($espanol){
                echo '<ol class="screen-progress-bar clearfix step-3-of-3">
                    <li class="step-1"><span>Preguntas<br />de la Prueba</span></li>
                    <li class="step-2"><span>Preguntas<br />Opcionales</span></li>
                    <li class="step-3"><span>Sus<br />Resultados</span></li>
                </ol>';
            } else {
                echo '<ol class="screen-progress-bar clearfix step-3-of-3">
                    <li class="step-1"><span>Test<br />Questions</span></li>
                    <li class="step-2"><span>Demographic<br />Information</span></li>
                    <li class="step-3"><span>Your<br />Results</span></li>
                </ol>';
            }
            
            /**
             * Login/Register Prompt (Top)
             */
            /*
            if(!count(array_intersect( array('actions_a', 'actions_c', 'actions_d', 'actions_e'), $layout))):
                get_template_part( 'templates/results/cta', 'login', array( 'id' => $user_screen_result['result_id'] ) ); 
            endif;
            */

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
                                $next_step_manual[] = $n['link']->ID;
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
                                            <h2 class="white small m-0">
                                                <strong><?php echo $result[0]['result_title']; $result_title = $result[0]['result_title']; ?></strong>
                                            </h2>
                                        </div>
                                        </div>
                                    <?php
                                else :
                                    /** 
                                     * Test Results
                                     */
                                    ?>
                                        <div class="bubble thin teal round-small-bl mb-4">
                                        <div class="inner">
                                            <div class="subtitle thin caps block pb-1">
                                                <?php if($espanol): ?>
                                                    Su resultado para la <span id="screen-name"><?php echo get_the_title($user_screen_result['screen_id']); ?></span> fue
                                                <?php else: ?>
                                                    Your <span id="screen-name"><?php echo get_the_title($user_screen_result['screen_id']); ?></span> score was
                                                <?php endif; ?>
                                            </div>
                                            <h2 class="white small m-0">
                                                <strong><?php the_sub_field('result_title'); $result_title = get_sub_field('result_title'); ?></strong>
                                            </h2>
                                        </div>
                                        </div>
                                    <?php 
                                endif; 

                            ?>
                                        
                            <div id="screen-result-buttons" class="button-grid pt-3 pb-3 pl-0 pr-0 pl-md-5 pr-md-5">

                                <?php 
                                    if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                                        // Hide these buttons on normal surveys 
                                    else :
                                    ?>
                                        <button id="screen-about" class="button mint round thin" type="button" data-toggle="collapse" data-target="#score-interpretation" aria-expanded="false" aria-controls="score-interpretation">       
                                            <?php 
                                                echo ($espanol ? 'Sobre su puntuación: ' : 'About your Score: '); 
                                                echo $user_screen_result['total_score'].' / '.$max_score; 
                                            ?>    
                                        </button>

                                        <?php
                                            if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d'), $layout))){
                                                get_template_part( 'templates/results/action', 'email_button', array( 'espanol' => $espanol ) ); 
                                            }
                                        ?>
                                    <?php 
                                    endif; 
                                ?>

                                <button id="screen-answers" class="button mint round thin" type="button" data-toggle="collapse" data-target="#your-answers" aria-expanded="false" aria-controls="your-answers">
                                    <?php echo ($espanol ? 'Sus respuestas' : 'Your Answers'); ?>
                                </button>
                                
                                <?php
                                    if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d'), $layout))){
                                        get_template_part( 'templates/results/action', 'take_test', array( 'url' => $take_another_url, 'espanol' => $espanol ) ); 
                                    }
                                ?>

                            </div>

                            <div class="pt-4">

                                <?php
                                    if(!count(array_intersect( array('actions_b', 'actions_c', 'actions_d'), $layout))){
                                        get_template_part( 'templates/results/action', 'email_display', array( 
                                            'width' => 'normal', 
                                            'screen_id' => $user_screen_result['screen_id'], 
                                            'user_screen_id' => $user_screen_id,
                                            'entry_id' => $user_screen_result['result_id']
                                        ) ); 
                                    }
                                ?>
                                
                                <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="score-interpretation">
                                <div class="inner small">
                                    <div class="container-fluid">
                                        <!--<h3 class="section-title dark-teal mb-4">Interpretation of Scores</h3>-->
                                        <?php the_field('interpretation_of_scores', $user_screen_result['screen_id']); ?>
                                    </div>
                                </div>
                                </div>     

                                <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="your-answers">
                                <div class="inner small">
                                    <div class="container-fluid p-0">
                                        <?php 
                                            echo ($espanol ? '<h3 class="section-title dark-teal mb-4">Sus respuestas</h3>' : '<h3 class="section-title dark-teal mb-4">Your Answers</h3>');
                                            echo $user_screen_result['your_answers']; 
                                        ?>
                                    </div>
                                </div>
                                </div>                   
                            

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
                                    $additional_scores = array();
                                    if(have_rows('additional_results', $user_screen_result['screen_id'])):
                                        echo '<p>';

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
                                                $additional_scores[] = strval($add_score_total);
                                            endwhile;

                                        echo '</p>';
                                    endif;

                                    // Result content
                                    if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ){
                                        echo $result[0]['result_content'];
                                    } else {
                                        the_sub_field('result_content');
                                    }
                                ?>
                            </div>

                        <?php
                        // Update the entry with the user score and result
                        $updateScreenArray = array(
                            'entry_id'          => $user_screen_result['result_id'],
                            'user_score'        => strval($user_screen_result['total_score']),
                            'user_result'       => get_sub_field('result_title'),
                            'additional_scores' => $additional_scores
                        );
                        updateUserScreenResults( $updateScreenArray );
                                                
                    
                        /**
                         * Array sorting helper
                         * Usage: usort($array, array_key_sorter('id'));
                         */
                        function array_key_sorter($key) {
                            return function ($a, $b) use ($key) {
                                return strnatcmp($a[$key], $b[$key]);
                            };
                        }

                        /**
                         * Handle rare duplicate
                         */
                        /*
                        $search_criteria['field_filters'][] = array( 
                            'key' => 38, 
                            'value' => $user_screen_id
                        );
                        $search_entries = GFAPI::get_entries( '0', $search_criteria );   
                        usort($search_entries, array_key_sorter('id'));
                        $search_counter = 0;
                        foreach($search_entries as $item){
                            if($search_counter > 0){ // Skip the first entry
                                $updateScreenArray_dupe = $updateScreenArray;
                                $updateScreenArray_dupe['entry_id'] = $item['id'];
                                $updateScreenArray_dupe['duplicate'] = '1';
                                updateUserScreenResults( $updateScreenArray_dupe );
                            }                                
                            $search_counter++;
                        }
                        */

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
                    if(trim($entry_data[$k]) != ''){
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
        foreach($demo_data['demo_steps'] as $e){
            $demo_steps[] = $e;
        }
        foreach($demo_data['exclude_ids'] as $e){
            $exclude_ids[] = $e;
        }
        foreach($demo_data['ctas'] as $e){
            $result_cta[] = $e;
        }

        // Global demo steps/CTAs
        $demo_data_global = get_mha_demo_steps( 'options', $answered_demos );
        foreach($demo_data_global['demo_steps'] as $e){
            $demo_steps[] = $e;
        }
        foreach($demo_data_global['exclude_ids'] as $e){
            $exclude_ids[] = $e;
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


    <div class="wrap normal pt-0 pb-3">

        <div class="mb-5 pb-2">
            <?php get_template_part( 'templates/results/cta', 'login', array( 'width' => 'narrow', 'corners' => '', 'id' => $user_screen_result['result_id'] ) ); ?>
        </div>

        <?php
            
            /**
             * A/B Variant
             * Layout: actions_hide_ns
             */    
            if(!in_array('actions_hide_ns', $layout)):          
        ?>
            <h2 class="section-title dark-blue bold">
                <?php if($espanol): ?>
                    Siguientes Pasos
                <?php else: ?>
                    Next Steps
                <?php endif; ?>
            </h2>
        <?php endif; ?>

        <?php 
            /**
             * A/B Variant
             * Layout: actions_ns_top
             */
            if(in_array('actions_ns_top', $layout)):          
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
                        'exclude_ids'        => $exclude_ids,
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
                    mha_results_related_articles( $related_article_args );
                ?>
            </div>
            </div>
        <?php endif; // Hide 'actions_ns_top' ?>


        <?php 
            /**
             * A/B Variant
             * Layout: actions_hide_ns
             */

            if(!in_array('actions_hide_ns', $layout)):          
        ?>
            
            <?php 
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
                        
                        $ns_custom_classes = get_sub_field('custom_classes') != '' ? get_sub_field('custom_classes') : 'bubble wtf round-tl mb-5 mint';
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
            ?>

            <?php         
            endif; // End 'actions_hide_ns'

            /**
             * A/B Variant
             * Layout: actions_b
             */
            if(in_array('actions_b', $layout)):
            ?>
                <div class="wrap narrow" id="layout-action_b">
                    <div id="screen-result-buttons-next_steps" class="button-grid pt-3 pb-3 px-0">
                        <?php 
                            if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                                // Hide these buttons on normal surveys 
                            else :
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
                            'screen_id' => $user_screen_result['screen_id'], 
                            'user_screen_id' => $user_screen_id,
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
                        if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                            // Hide these buttons on normal surveys 
                        else :
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
                            'show' => 'yes', 
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
                        if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                            // Hide these buttons on normal surveys 
                        else :
                            if(!is_user_logged_in()):
                                get_template_part( 'templates/results/action', 'login_button', array( 
                                    'espanol' => $espanol 
                                ) ); 
                            endif;
                            get_template_part( 'templates/results/action', 'email_button', array( 
                                'espanol' => $espanol 
                            ) ); 
                            get_template_part( 'templates/results/action', 'take_test', array( 
                                'url' => $take_another_url, 'espanol' => $espanol 
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
        ?>
        <div id="cta-col" class="cta-cols">
            <?php       
                /*
                * Result specific CTA
                */
                global $post;
                $unique_result_cta = array_unique($result_cta);
                foreach($unique_result_cta as $cta){
                    $post = get_post($cta); 
                    get_template_part( 'templates/blocks/block', 'cta' );  
                } 
                wp_reset_postdata();
                
                if(!$espanol){
                    // All Screen CTAs
                    if( have_rows('actions_global_screening', 'option') ):
                    while( have_rows('actions_global_screening', 'option') ) : the_row();  
                        $post_id = get_sub_field('action');
                        $post = get_post($post_id); 
                        if(!in_array($post_id, $result_cta)){ // Skip in case the result has this already
                            setup_postdata($post);
                            get_template_part( 'templates/blocks/block', 'cta' );  
                            $result_cta[] = $post_id;
                        }
                    endwhile;
                    endif;
                    
                    // Global CTAs
                    if( have_rows('actions', 'option') ):
                    while( have_rows('actions', 'option') ) : the_row();  
                        $post_id = get_sub_field('action');
                        $post = get_post($post_id); 
                        if(!in_array($post_id, $result_cta)){ // Skip in case the result has this already
                            setup_postdata($post);
                            get_template_part( 'templates/blocks/block', 'cta' );  
                        }
                    endwhile;
                    endif;
                }
                wp_reset_postdata();
            ?>
        </div>
        <?php endif; // Hide 'actions_hide_ns' ?>

        <?php if(get_field('next_steps_subtitle', $user_screen_result['screen_id'])): ?>
            <h2 class="section-title cerulean small bold">
                <?php 
                if($espanol){
                    echo 'Más información y recursos';
                } else {
                    if(in_array('actions_ns_top', $layout)){
                        echo 'More ';
                    }
                    the_field('next_steps_subtitle', $user_screen_result['screen_id']); 
                }
                ?>
            </h2>
        <?php endif; ?>
        
    </div>

    <div class="wrap narrow mb-5">
        <?php 
            // Related Articles
            $related_article_args = array(
                'demo_steps'         => $demo_steps,
                'next_step_manual'   => $next_step_manual,
                'user_screen_result' => $user_screen_result,
                'exclude_ids'        => $exclude_ids,
                'next_step_terms'    => $next_step_terms,
                'espanol'            => $espanol,
                'iframe_var'         => $iframe_var,
                'partner_var'        => $partner_var,
                'answered_demos'     => $answered_demos,
                'layout'             => $layout
            );
            
            /**
             * A/B Variant
             * Layout: actions_hide_ns
             */
            if(in_array('actions_ns_top', $layout)){
                $related_article_args['skip'] = 5;
            }

            mha_results_related_articles( $related_article_args );
        ?>
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