<?php 
/* Template Name: Screen Results */
get_header(); 

// Get Screen Results
$user_screen_id = get_query_var('sid');
$user_screen_result = getUserScreenResults( $user_screen_id );  
$next_step_terms = [];
$next_step_manual = [];
$exclude_ids = [];
$result_cta = [];
$demo_steps = [];
$result_title = '';
$max_score = get_field('overall_max_score', $user_screen_result['screen_id']);
$espanol = get_field('espanol', $user_screen_result['screen_id']);
$partner_var = get_query_var('partner');
$iframe_var = get_query_var('iframe');
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

    <?php 
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
    ?>
    
    <?php if(!is_user_logged_in()): ?>
        <div class="wrap narrow">
            <div id="screen-save">
                <div class="bubble round blue thin mb-3">
                <div class="inner bold text-center">
                    <?php 
                        if($iframe_var){    
                            $login_target = ' target="_blank"';
                        } else {
                            $login_target = '';
                        }
                    ?>
                    <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_screen_').$user_screen_result['result_id'] ?>">Log in</a>
                    or
                    <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/sign-up/?action=save_screen_<?php echo $user_screen_result['result_id']; ?>">register for an account</a>
                    to save this result to your account.
                </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php

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
                                                Su resultado para la <?php echo get_the_title($user_screen_result['screen_id']); ?> fue
                                            <?php else: ?>
                                                Your <?php echo get_the_title($user_screen_result['screen_id']); ?> score was
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
                                // Hide these buttons on normal surveys 
                                if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ):
                                    //
                                else :
                                ?>
                                <button id="screen-about" class="button mint round thin" type="button" data-toggle="collapse" data-target="#score-interpretation" aria-expanded="false" aria-controls="score-interpretation">                                                                   
                                    <?php if($espanol): ?>
                                        Sobre su puntuación:
                                    <?php else: ?>
                                        About your Score:
                                    <?php endif; ?>
                                    <?php echo $user_screen_result['total_score']; ?> / <?php echo $max_score; ?>    
                                </button>

                                <button id="screen-email" class="button mint round thin" type="button" data-toggle="collapse" data-target="#email-results" aria-expanded="false" aria-controls="email-results">                                    
                                    <?php if($espanol): ?>
                                        Enviar sus respuestas por correo electrónico
                                    <?php else: ?>
                                        Email Results
                                    <?php endif; ?>
                                </button>
                                <?php 
                                endif; 
                            ?>

                            <button id="screen-answers" class="button mint round thin" type="button" data-toggle="collapse" data-target="#your-answers" aria-expanded="false" aria-controls="your-answers">
                                <?php if($espanol): ?>
                                    Sus respuestas
                                <?php else: ?>
                                    Your Answers
                                <?php endif; ?>
                            </button>
                            
                            <?php
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
                            <a class="button mint round thin" id="screen-take" href="<?php echo $take_another_url; ?>">
                                <?php if($espanol): ?>
                                    Tomar otra prueba de salud mental
                                <?php else: ?>
                                    Take Another Mental Health&nbsp;Test
                                <?php endif; ?>
                            </a>

                        </div>

                        <div class="pt-4">

                            <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="email-results">
                            <div class="inner small">
                                <div class="container-fluid">
                                    
                                    <form id="email-screening-results" action="#" method="POST" class="form-container line-form wide blue" autocomplete="off">   

                                    <div class="form-message" style="display: none;"></div>
                                    <div class="form-content">

                                        <p class="form-group float-label mb-0">
                                            <label class="form-label" for="email">email</label>
                                            <input type="text" name="email" id="email" class="form-input required" />
                                            <input type="text" autocomplete="off" name="email_doublecheck" value="" class="email_doublecheck" tabindex="-1" />
                                        </p>

                                        <?php 					
                                            global $post;
                                            $postSlug = $post->post_name;
                                        ?>
                                        <div class="form-actions pt-3">
                                            <input type="hidden" name="nonce" value="<?php $nonce = wp_create_nonce('mhaScreenEmail'); echo $nonce; ?>" />
                                            <input type="hidden" name="screen_id" value="<?php echo $user_screen_result['screen_id']; ?>" />
                                            <input type="hidden" name="screen_user_id" value="<?php echo $user_screen_id; ?>" />                                                
                                            <?php if($espanol): ?>
                                                <input type="submit" class="submit button teal gform_button espanol" value="Enviar" />
                                            <?php else: ?>
                                                <input type="submit" class="submit button teal gform_button" value="Send Results" />
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                    </form>

                                </div>
                            </div>
                            </div>
                            
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
                                    <?php if($espanol): ?>
                                        <h3 class="section-title dark-teal mb-4">Sus respuestas</h3>
                                    <?php else: ?>
                                        <h3 class="section-title dark-teal mb-4">Your Answers</h3>
                                    <?php endif; ?>                                        
                                    <?php echo $user_screen_result['your_answers']; ?>
                                </div>
                            </div>
                            </div>                   
                        
                            <?php
                                // Alert message
                                if($user_screen_result['alert'] > 0){
                                    //echo '<div class="bubble coral round-tl mb-4 narrow"><div class="inner bold">';
                                    echo '<div class="bold warning-message mb-4">';
                                    echo get_field('warning_message', $user_screen_result['screen_id']);
                                    echo '</div>';
                                    //echo '</div></div>';
                                }

                                // Additional scores to display
                                $additional_scores = array();
                                if(have_rows('additional_results', $user_screen_result['screen_id'])):
                                echo '<p>';
                                    echo '<strong>Overall Score:</strong> '.$user_screen_result['total_score'].'<br />';
                                    while( have_rows('additional_results', $user_screen_result['screen_id']) ) : the_row();  
                                        $add_scores = get_sub_field('scores');
                                        $add_score_total = 0;
                                        foreach($add_scores as $score){
                                            $add_score_total = $user_screen_result['general_score_data'][$score['question_id']] + $add_score_total;
                                        }

                                        echo '<strong>'.get_sub_field('title').'</strong> '.$add_score_total.'<br />';
                                        
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
                    $search_criteria['field_filters'][] = array( 
                        'key' => 38, 
                        'value' => $user_screen_id
                    );
                    $search_entries = GFAPI::get_entries( '0', $search_criteria, $sorting );   
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

                }

            endwhile;
        endif;
    ?>
</div>

<div class="wrap normal pt-5 pb-3">

    <h2 class="section-title dark-blue bold">
        <?php if($espanol): ?>
            Siguientes Pasos
        <?php else: ?>
            Next Steps
        <?php endif; ?>
    </h2>
    
    <div id="cta-col" class="cta-cols">
        <?php       
            /**
             * Demographic Based Next Steps
             */
                // Get the user's answered demographic questions
                $entry_data = GFAPI::get_entry( $user_screen_result['result_id'] );
                $answered_demos = [];
                if(!is_wp_error($entry_data)){
                    foreach($entry_data as $k => $v){            
                        $field = GFFormsModel::get_field( $entry_data['form_id'], $k );  
                        if (isset($field->cssClass) && strpos($field->cssClass, 'optional') !== false || isset($field->cssClass) && strpos($field->cssClass, 'question') !== false || isset($field->cssClass) && strpos($field->cssClass, 'question-optional') !== false) {
                            if(trim($entry_data[$k]) != ''){
                                $answered_demos[$field->label][] = $entry_data[$k];
                            }
                        }
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

    <?php if(get_field('next_steps_subtitle', $user_screen_result['screen_id'])): ?>
        <h2 class="section-title cerulean small bold"><?php the_field('next_steps_subtitle', $user_screen_result['screen_id']); ?></h2>
    <?php endif; ?>
</div>

<div class="wrap narrow mb-5">
    <ol class="next-steps">        
        <?php
            
            /**
             * Demo Based Steps
             */
            foreach($demo_steps as $link){
                echo '<li><a class="dark-gray plain rec-screen-demobased" href="'.get_the_permalink($link->ID).'">'.$link->post_title.'</a>';
            }

            /**
             * Result Based Manual Steps
             */
                if(isset($next_step_manual)){
                    foreach($next_step_manual as $step){
                        $step_link = get_the_permalink($step);
                        $step_link_target = '';
                        if($partner_var && in_array($partner_var, mha_approved_partners() )){                                    
                            //$step_link = add_query_arg( 'partner', $partner_var, $step_link );
                        }
                        if($iframe_var){                                         
                            //$step_link = add_query_arg( 'iframe','true', $step_link );
                            $step_link_target = ' target="_blank"';
                        }
                        if(!in_array($step, $exclude_ids)){
                            echo '<li><a class="dark-gray plain rec-result-manual"'.$step_link_target.' href="'.$step_link.'">'.get_the_title($step).'</a></li>';
                            $exclude_id[] = $step;
                        }
                    }
                }


            /**
             * Manual Steps
             */
                if( have_rows('featured_next_steps', $user_screen_result['screen_id']) ):
                while( have_rows('featured_next_steps', $user_screen_result['screen_id']) ) : the_row();
                    $step = get_sub_field('link');
                    if($step && !in_array($step->id, $exclude_ids)){
                        $manual_step_link = get_the_permalink($step->ID);
                        $manual_step_target = '';
                        if($partner_var && in_array($partner_var, mha_approved_partners() )){                                    
                            //$manual_step_link = add_query_arg( 'partner', $partner_var, $manual_step_link );
                        }
                        if($iframe_var){                                         
                            //$manual_step_link = add_query_arg( 'iframe','true', $manual_step_link );
                            $manual_step_target = ' target="_blank"';
                        }
                        echo '<li><a class="dark-gray plain rec-screen-manual"'.$manual_step_target.' href="'.$manual_step_link.'">'.$step->post_title.'</a></li>';
                        $exclude_id[] = $step->ID;
                    }
                endwhile;        
                endif;


            /**
             * Automatic Query Args
             */
            if(!empty($exclude_id)){
                $total_exclude = count($exclude_id);
            } else {
                $total_exclude = 0;
            }
            $total_recs = 20 - $total_exclude;
            $args = array(
                "post_type" => 'article',
                "order"	=> 'ASC',
                "orderby" => 'date',
                "post_status" => 'publish',
                "posts_per_page" => $total_recs,
                "meta_query" => array(
                    array(
                        "key"       => 'type',
                        "value"     => 'condition',
                        'compare'   => 'LIKE'
                    )
                )
            );
            
            if($espanol){
                $args['meta_query'] = array(
                    array(
                        "key" => 'espanol',
                        "value" => 1
                    )
                );
                //$args['meta_query']['relationship'] = 'AND';
            }

            /**
             * Result Based Next Steps
             */
            if(isset($next_step_terms)){
                $next_step_terms = array_unique($next_step_terms);
                $taxonomy_query = [];
                foreach($next_step_terms as $step){
                    $step = get_term($next);
                    if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                        $taxonomy_query[$step->taxonomy][] = $step->term_id;
                    }
                }
            }

            // Demographic based steps
            if(!empty($user_screen_result['result_terms'])){
                foreach($user_screen_result['result_terms'] as $step){ 
                    if($step['taxonomy'] == 'condition' || $step['taxonomy'] == 'age_group' || $step['taxonomy'] == 'post_tag'){
                        $taxonomy_query[$step['taxonomy']][] = $step['id'];    
                    }   
                }
            }

            // Overall screen based steps
            $tags = get_field('related_tags', $user_screen_result['screen_id']);
            if($tags){
                foreach($tags as $step){
                    if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                        $taxonomy_query[$step->taxonomy][] = $step->term_id;
                    }
                }
            }

            // Excluded previous manual 
            if(!empty($exclude_id)){
                $args['post__not_in'] = $exclude_id; 
            }

            // Set up taxonomy query filters
            foreach($taxonomy_query as $k => $v){
                $args['tax_query'][] = array(
                    'taxonomy' => $k,
                    'field'    => 'term_id',
                    'terms'    => $v
                );
            }

            // Make all tags required for multiple taxonomies (e.g. avoid eating disorder articles on depression results 
            // if someone answered 18-25 demographic questions)
            if(count($taxonomy_query) > 1){
                $args['tax_query']['relation'] = 'AND';
            }

            // Automatic Related Article Query
            $loop = new WP_Query($args);
            while($loop->have_posts()) : $loop->the_post();
                $related_link = get_the_permalink();
                $related_link_target = '';
                if($partner_var && in_array($partner_var, mha_approved_partners() )){                                    
                    //$related_link = add_query_arg( 'partner', $partner_var, $related_link );
                }
                if($iframe_var){                                         
                    //$related_link = add_query_arg( 'iframe','true', $related_link );
                    $related_link_target = ' target="_blank"';
                }
                if(!in_array(get_the_ID(), $exclude_ids)){
                    echo '<li><a class="dark-gray plain rec-auto"'.$related_link_target.' href="'.$related_link.'">'.get_the_title().'</a></li>';
                }
            endwhile;

            // See All Link
            if(get_field('see_all_link', $user_screen_result['screen_id'])){
                $see_all_text = 'See All';
                $see_all_link = get_field('see_all_link', $user_screen_result['screen_id']);
                $see_all_target = '';
                if(get_field('see_all_link_text', $user_screen_result['screen_id'])){
                    $see_all_text = get_field('see_all_link_text', $user_screen_result['screen_id']);
                }
                if($partner_var && in_array($partner_var, mha_approved_partners() )){                                    
                    //$see_all_link = add_query_arg( 'partner', $partner_var, $see_all_link );
                }
                if($iframe_var){                                         
                    //$see_all_link = add_query_arg( 'iframe','true', $see_all_link );
                    $see_all_target = ' target="_blank"';
                }
                echo '<li><a class="caps cerulean plain"'.$see_all_target.' href="'.$see_all_link.'">'.$see_all_text.'</a></li>';
            }

        ?>
    </ol>
</div>
    
<?php
    
    // Content Blocks
    wp_reset_query();
    if( have_rows('block') ):
    while ( have_rows('block') ) : the_row();
        $layout = get_row_layout();
        if( get_template_part( 'templates/blocks/block', $layout ) ):
            get_template_part( 'templates/blocks/block', $layout );
        endif;
    endwhile;
    endif;
?>


<?php
get_footer();