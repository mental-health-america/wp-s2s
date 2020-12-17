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
        $response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/?search={"field_filters": [{"key":38,"value":"'.$user_screen_id.'","operator":"contains"}]}', array( 'headers' => $headers ) );

        
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

            $your_answers .= '<h3 class="section-title dark-teal mb-4">Your Answers</h3>';      
            foreach($data as $k => $v){
                
                // Get field object
                $field = GFFormsModel::get_field( $data->form_id, $k );

                // Get referring screen ID
                
                if (strpos($field->label, 'Screen ID') !== false) {     
                    $screen_id = $v;
                }

                //Screening Questions
                if (strpos($field->cssClass, 'question') !== false) {                    
                    $label = $field->label; // Field label 
                    $value_label = $field['choices'][$v]['text']; // Selection Label                    
                    $total_score = $total_score + $field['choices'][$v]['value']; // Add to total score
                    $your_answers .= '<div class="row pb-4">';
                        $your_answers .= '<div class="col-7 text-gray">'.$label.'</div>';
                        $your_answers .= '<div class="col-5 bold caps text-dark-blue pl-4">'.$value_label.'</div>';
                    $your_answers .= '</div>';
                }

                // Warning message counter
                if (strpos($field->cssClass, 'alert') !== false) {    
                    if($v > 0){
                        $alert++;
                    }  
                }

                // Taxonomy grabber
                if (strpos($field->cssClass, 'taxonomy') !== false) { 
                    $term = get_term_by('slug', esc_attr($v), $field->adminLabel);
                    if($term){
                        $result_terms[$i]['id'] = $term->term_id;
                        $result_terms[$i]['taxonomy'] = $field->adminLabel;
                        $i++;
                    }
                }
                
            }   

        }
        
        /**
         * Results Content
         */

        $required_check = '0';
        
        // Check this result's required tags
        if( have_rows('results', $screen_id) ):
        while( have_rows('results', $screen_id) ) : the_row();
        
            $min = get_sub_field('score_range_minimum');
            $max = get_sub_field('score_range_max');
            if($total_score >= $min && $total_score <= $max || $total_score >= $min && !is_numeric($max)){
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
            while( have_rows('results', $screen_id) ) : the_row();

                $min = get_sub_field('score_range_minimum');
                $max = get_sub_field('score_range_max');

                /*
                echo '<hr />';
                echo get_row_index();
                echo "Min: $min<br />";
                echo "Max: $max<br />";
                echo "Total: $total_score<br />";
                */
                
                if($total_score >= $min && $total_score <= $max || $total_score >= $min && !is_numeric($max)){
                    
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
                            <div class="subtitle thin caps block pb-1">Your score was</div>
                            <h2 class="white small m-0">
                                <strong><?php the_sub_field('result_title'); ?></strong>
                            </h2>
                        </div>
                        </div>
                                    
                        <div id="screen-result-buttons" class="button-grid p-4 pl-5 pr-5">
                            <button id="screen-about" class="button mint round thin reveal-slide-button" data-reveal="score-interpretation">About your Score: <?php echo $total_score; ?></button>
                            <button id="screen-email" class="button mint round thin reveal-slide-button" data-reveal="email-results">Email Results</button>
                            <button id="screen-answers" class="button mint round thin reveal-slide-button" data-reveal="your-answers">Your Answers</button>
                            <a class="button mint round thin" id="screen-take" href="/screening-tools/">Take a Mental Health Test</a>
                        </div>

                        <div class="pt-4">

                            <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4" id="email-results" style="display: none;">
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

                            <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4" id="your-answers" style="display: none;">
                            <div class="inner small">
                                <div class="container-fluid">
                                    <?php echo $your_answers; ?>
                                </div>
                            </div>
                            </div>
                            
                            <div class="bubble thick light-teal bubble-border round-tl montserrat mb-4" id="score-interpretation" style="display: none;">
                            <div class="inner small">
                                <div class="container-fluid">
                                <h3 class="section-title dark-teal mb-4">Interpretation of Scores</h3>
                                    <?php the_field('interpretation_of_scores', $screen_id); ?>
                                </div>
                            </div>
                            </div>                        
                        
                            <?php
                                if($alert > 0){
                                    the_field('warning_message', $screen_id);
                                }
                                the_sub_field('result_content');
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

<div class="wrap narrow">
    <ol class="next-steps">        
        <?php
            // Result based manual steps
            foreach($next_step_manual as $step){
                echo "<li><strong>Manual step from result: </strong>".get_the_title($step).'</li>';
            }

            // Manual steps
            if( have_rows('featured_next_steps', $screen_id) ):
            while( have_rows('featured_next_steps', $screen_id) ) : the_row();
                $step = get_sub_field('link');
                echo '<li><strong>Manual step from screen:</strong> '. get_the_title($step->ID).'</li>'; // Simply print the manual selection
            endwhile;        
            endif;

            // Result based related tag steps
            $next_step_terms = array_unique($next_step_terms);
            foreach($next_step_terms as $step){
                echo "<li><strong>Relevant tag from result: </strong>".get_term($step)->name.'</li>';
            }

            // Demographic based steps
            if(!empty($result_terms)){
                foreach($result_terms as $step){         
                    echo "<li><strong>Optional answers tag: </strong>".get_term_by('id', $step['id'], $step['taxonomy'])->name.'</li>';            
                }
            }

            // Overall screen based steps
            $tags = get_field('related_tags', $screen_id);
            foreach($tags as $t){
                echo "<li><strong>Related tag from screen: </strong>".get_term($t)->name.'</li>';
            }

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
get_footer();