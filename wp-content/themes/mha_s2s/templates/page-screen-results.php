<?php 
/* Template Name: Screen Results */
get_header(); 
?>

<div class="wrap normal">
    <?php
        while ( have_posts() ) : the_post();
            get_template_part( 'templates/blocks/content', 'plain' );
        endwhile;
    ?>
</div>

<div class="wrap narrow">

    <ol class="screen-progress-bar clearfix step-3-of-3">
        <li class="step-1"><span>Test<br />Questions</span></li>
        <li class="step-2"><span>Demographic<br />Information</span></li>
        <li class="step-3"><span>Your<br />Results</span></li>
    </ol>
    
    <?php
        /**
         * Results Scoring
         */

        // Vars
        $user_screen_id = get_query_var('sid');
        $total_score = 0;

        // Gravity Forms API Connection
        $consumer_key = 'ck_0edaed6a92a48bea23695803046fc15cfd8076f5';
        $consumer_secret = 'cs_7b33382b0f109b52ac62706b45f9c8e0a5657ced';
        $headers = array( 'Authorization' => 'Basic ' . base64_encode( "{$consumer_key}:{$consumer_secret}" ) );
        //$response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/'.$user_screen_id.'?_labels[0]=1&_field_ids[0]=1' , array( 'headers' => $headers ) );
        $response = wp_remote_get( get_site_url().'/wp-json/gf/v2/entries/?search={"field_filters": [{"key":38,"value":"'.$user_screen_id.'","operator":"contains"}]}', array( 'headers' => $headers ) );

        
        // Future Content
        $your_answers = '';
        $result_terms = [];
        $next_step_terms = [];
        $next_step_manual = [];
        $required_result_tags = [];

        // Check the response code.
        if ( wp_remote_retrieve_response_code( $response ) != 200 || ( empty( wp_remote_retrieve_body( $response ) ) ) ){
            
            // Error!
            echo '<p>There was a problem displaying to your results. Please contact us if the issue persists.</p>';
            echo '<p><strong>Response Error:</strong>'.wp_remote_retrieve_response_code( $response ).'<br />';
            echo '<strong>Screen ID:</strong>'.$user_screen_id.'</p>';

        } else {

            // Got a good response, proceed!
            $json = wp_remote_retrieve_body($response);
            $data = json_decode($json);                 
            $data = $data->entries[0];

            // Text
            $label = '';
            $value_label = '';
            $screen_id = '';
            $alert = 0;
            $i = 0;         
            $advanced_conditions_data = []; 
            $general_score_data = []; 

            $your_answers .= '<h3 class="section-title dark-teal mb-4">Your Answers</h3>';    
            foreach($data as $k => $v){
                
                // Get field object
                $field = GFFormsModel::get_field( $data->form_id, $k );  

                // Get referring screen ID                
                if (strpos($field->label, 'Screen ID') !== false) {     
                    $screen_id = $v;
                }

                //Screening Questions
			    if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {  
                    
                    // Advanced Conditions Check
                    if(count(get_sub_field('advanced_condition', $screen_id)) > 0){
                        $advanced_conditions_data[$field->id] = $v; 
                    };
                    $general_score_data[$field->id] = $v; 

                    $label = $field->label; // Field label    
                    if(strpos($field->cssClass, 'exclude') === false){         
                        $total_score = $total_score + $v; // Add to total score
                    }
                    // Get label for selected choice
                    foreach($field['choices'] as $choice){
                        if($choice['value'] == $v){
                            $value_label = $choice['text'];
                        }
                    }
                    if($v != ''){			
                        $your_answers .= '<div class="row pb-4">';
                            $your_answers .= '<div class="col-sm-7 col-12 text-gray">'.$label.'</div>';
                            $your_answers .= '<div class="col-sm-5 col-12 bold caps text-dark-blue">'.$value_label.' ('.$v.')</div>';
                        $your_answers .= '</div>';
                    }
                }

                // Warning message counter  
                if (isset($field->cssClass) && strpos($field->cssClass, 'alert') !== false) {    
                    if($v > 0){
                        $alert++;
                    }  
                }

                // Taxonomy grabber
                if (isset($field->cssClass) && strpos($field->cssClass, 'taxonomy') !== false) {  
                    $term = get_term_by('slug', esc_attr($v), $field->adminLabel);
                    if($term){
                        $result_terms[$i]['id'] = $term->term_id;
                        $result_terms[$i]['taxonomy'] = $field->adminLabel;
                        $i++;
                    }
                }
                
            }   
            
            // Custom Logic Override
            $custom_results_logic = get_field('custom_results_logic', $screen_id);
            $custom_result_row = '';
            if($custom_results_logic){
                $custom_result_logic_data = custom_logic_checker($general_score_data, $custom_results_logic);
                $total_score = $custom_result_logic_data['total_score'];
                $custom_result_row = $custom_result_logic_data['custom_result_row'];
            }
                        
            // Update total score to be the max possible score if its over
            $max_score = get_field('overall_max_score', $screen_id);
            if($total_score >= $max_score){
                $total_score = $max_score;
            }

        }
        
        /**
         * Results Content
         */

        $required_check = '0';
        $has_advanced_conditions = 0;
        $advanced_condition_row = '';
        
        // Check this result's required tags
        if( have_rows('results', $screen_id) ):
            
            // Advanced Conditions
            while( have_rows('results', $screen_id) ) : the_row();   
                $advanced_conditions = get_sub_field('advanced_conditions');
                if(count($advanced_conditions) > 1){
                    foreach($advanced_conditions as $ac){
                        $advanced_min = $ac['score_range_minimum'];
                        $advanced_max = $ac['score_range_max'];
                        $advanced_id = $ac['question_id'];   
                        if($advanced_conditions_data[$advanced_id]){
                            if($advanced_max){
                                if($advanced_conditions_data[$advanced_id] >= $advanced_min && $advanced_conditions_data[$advanced_id] <= $advanced_max ){
                                    $advanced_condition_row = get_row_index();
                                }
                            } else if($advanced_min) {
                                if($advanced_conditions_data[$advanced_id] == $advanced_min){
                                    $advanced_condition_row = get_row_index();
                                }
                            }
                        }
                    }
                    $has_advanced_conditions++;
                }
            endwhile;
                    
            while( have_rows('results', $screen_id) ) : the_row();            
                $min = get_sub_field('score_range_minimum');
                $max = get_sub_field('score_range_max');
                if($total_score >= $min && $total_score <= $max || $has_advanced_conditions > 0 && $advanced_condition_row == get_row_index()){

                    if($has_advanced_conditions > 0){
                        if($advanced_condition_row != get_row_index()){ 
                            continue;
                        }
                    }

                    if(get_sub_field('required_tags')){
                        $req = get_sub_field('required_tags');
                        foreach($req as $t){
                            if(in_multiarray($t, $result_terms)){
                                $required_result_tags[] = $t;
                            }
                        }
                    }
                }
            endwhile;
        else:
            echo '<p class="text-center bold">This screening result does not exist. <a href="/screening-tools">Try taking a screen!</a>';
        endif;
        
        if(get_field('survey', $screen_id)){
            
            // Survey Results
            $result = get_field('results', $screen_id);
            ?>

                <div class="bubble thin teal round-small-bl mb-4">
                <div class="inner">
                    <h2 class="white small m-0">
                        <strong><?php echo $result[0]['result_title']; ?></strong>
                    </h2>
                </div>
                </div>
                
                <div class="pt-4">                   
                    <?php echo $result[0]['result_content']; ?>
                </div>

            <?php

        } else {

            // Screening Results                
            if( have_rows('results', $screen_id) ):
                                
                // Result Display
                while( have_rows('results', $screen_id) ) : the_row();
                    $min = get_sub_field('score_range_minimum');
                    $max = get_sub_field('score_range_max');
                    $custom_logic_condition_row = get_sub_field('custom_logic_condition');

                    if($total_score >= $min && $total_score <= $max || $has_advanced_conditions > 0 && $advanced_condition_row == get_row_index() || $custom_results_logic != '' && $custom_result_row == $custom_logic_condition_row ){

                        // Advanced Condition Double Check (in case score condition passes)
                        if($has_advanced_conditions > 0){
                            if($advanced_condition_row != get_row_index()){ 
                                continue;
                            }
                        }

                        // Custom Condition Double Check (in case score condition passes)
                        if($custom_results_logic != ''){
                            if($custom_result_row != $custom_logic_condition_row){ 
                                continue;
                            }
                        }
                        
                        // Required Tags Check
                        if(empty($required_result_tags) && !empty(get_sub_field('required_tags'))){
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
                        foreach($next as $n){
                            $next_step_manual[] = $n['link']->ID;
                        }
                        ?>

                            <div class="bubble thin teal round-small-bl mb-4">
                            <div class="inner">
                                <div class="subtitle thin caps block pb-1">Your <?php echo get_the_title($screen_id); ?> score was</div>
                                <h2 class="white small m-0">
                                    <strong><?php the_sub_field('result_title'); ?></strong>
                                </h2>
                            </div>
                            </div>
                                        
                            <div id="screen-result-buttons" class="button-grid pt-3 pb-3 pl-0 pr-0 pl-md-5 pr-md-5">
                                <button id="screen-about" class="button mint round thin" type="button" data-toggle="collapse" data-target="#score-interpretation" aria-expanded="false" aria-controls="score-interpretation">About your Score: <?php echo $total_score; ?></button>
                                <button id="screen-email" class="button mint round thin" type="button" data-toggle="collapse" data-target="#email-results" aria-expanded="false" aria-controls="email-results">Email Results</button>
                                <button id="screen-answers" class="button mint round thin" type="button" data-toggle="collapse" data-target="#your-answers" aria-expanded="false" aria-controls="your-answers">Your Answers</button>
                                <a class="button mint round thin" id="screen-take" href="/screening-tools/" target="_blank">Take a Mental Health Test</a>
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
                                                <input type="hidden" name="screen_id" value="<?php echo $screen_id; ?>" />
                                                <input type="hidden" name="screen_user_id" value="<?php echo $user_screen_id; ?>" />
                                                <input type="submit" class="submit button teal gform_button" value="Send Results" />
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
                                        <?php the_field('interpretation_of_scores', $screen_id); ?>
                                    </div>
                                </div>
                                </div>     

                                <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="your-answers">
                                <div class="inner small">
                                    <div class="container-fluid p-0">
                                        <?php echo $your_answers; ?>
                                    </div>
                                </div>
                                </div>                   
                            
                                <?php
                                    if($alert > 0){
                                        //echo '<div class="bubble coral round-tl mb-4 narrow"><div class="inner bold">';
                                        echo '<div class="bold">';
                                        echo get_field('warning_message', $screen_id);
                                        echo '</div>';
                                        //echo '</div></div>';
                                    }
                                    the_sub_field('result_content');

                                    if(have_rows('additional_results', $screen_id)):
                                    echo '<p>';
                                        echo '<strong>Overall Score:</strong> '.$total_score.'<br />';
                                        while( have_rows('additional_results', $screen_id) ) : the_row();  
                                            $add_scores = get_sub_field('scores');
                                            $add_score_total = 0;
                                            foreach($add_scores as $score){
                                                $add_score_total = $general_score_data[$score['question_id']] + $add_score_total;
                                            }

                                            echo '<strong>'.get_sub_field('title').'</strong> '.$add_score_total.'<br />';
                                        endwhile;
                                        echo '</p>';
                                    endif;
                                ?>
                            </div>

                        <?php
                    }

                endwhile;
            endif;

        }
    ?>
</div>

<div class="wrap normal pt-5 pb-3">
    <h2 class="section-title dark-blue bold">Next Steps</h2>
    <?php if(get_field('next_steps_subtitle', $screen_id)): ?>
        <h2 class="section-title cerulean small bold"><?php the_field('next_steps_subtitle', $screen_id); ?></h2>
    <?php endif; ?>
</div>

<div class="wrap narrow mb-5">
    <ol class="next-steps">        
        <?php
            $exclude_ids = [];

            // Result based manual steps
            foreach($next_step_manual as $step){
                echo '<li><a class="dark-gray plain rec-result-manual" href="'.get_the_permalink($step).'">'.get_the_title($step).'</a></li>';
                $exclude_id[] = $step;
            }

            // Manual steps
            if( have_rows('featured_next_steps', $screen_id) ):
            while( have_rows('featured_next_steps', $screen_id) ) : the_row();
                if(!in_array($step->id, $exclude_ids)){
                    $step = get_sub_field('link');
                    echo '<li><a class="dark-gray plain rec-screen-manual" href="'.get_the_permalink($step->ID).'">'.$step->post_title.'</a></li>';
                    $exclude_id[] = $step->ID;
                }
            endwhile;        
            endif;


            // Automatic query args
            $total_recs = 20 - count($exclude_id);
            $args = array(
                "post_type" => 'article',
                "order"	=> 'ASC',
                "orderby" => 'date',
                "post_status" => 'publish',
                "posts_per_page" => $total_recs,
                "meta_query" => array(
                    array(
                        "key" => 'type',
                        "value" => 'condition'
                    )
                )
            );

            // Result based related tag steps
            $next_step_terms = array_unique($next_step_terms);
            $taxonomy_query = [];
            foreach($next_step_terms as $step){
                $step = get_term($next);
                if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                    $taxonomy_query[$step->taxonomy][] = $step->term_id;
                }
            }

            // Demographic based steps
            if(!empty($result_terms)){
                foreach($result_terms as $step){ 
                    if($step['taxonomy'] == 'condition' || $step['taxonomy'] == 'age_group' || $step['taxonomy'] == 'post_tag'){
                        $taxonomy_query[$step['taxonomy']][] = $step['id'];    
                    }   
                }
            }

            // Overall screen based steps
            $tags = get_field('related_tags', $screen_id);
            foreach($tags as $step){
                if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                    $taxonomy_query[$step->taxonomy][] = $step->term_id;
                }
            }

            // Excluded previous manual 
            $args['post__not_in'] = $exclude_id; 

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
                echo '<li><a class="dark-gray plain rec-auto" href="'.get_the_permalink().'">'.get_the_title().'</a></li>';
            endwhile;

            // See All Link
            if(get_field('see_all_link', $screen_id)){
                $see_all_text = 'See All';
                if(get_field('see_all_link_text', $screen_id)){
                    $see_all_text = get_field('see_all_link_text', $screen_id);
                }
                echo '<li><a class="caps cerulean plain" href="'.get_field('see_all_link', $screen_id).'">'.$see_all_text.'</a></li>';
            }


        ?>
    </ol>
</div>
    
<?php
    // CTA
    if( have_rows('global_call_to_actions', 'option') ):
    while( have_rows('global_call_to_actions', 'option') ) : the_row();      
        if(!get_sub_field('disabled')){    
            get_template_part( 'templates/blocks/block', 'text' );        
        }    
    endwhile;
    endif;
    
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