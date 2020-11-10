<?php 
/* Template Name: Screen Results */
get_header(); 

function in_multiarray($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_multiarray($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}

?>

<div class="clearfix" style="background: #FFF;">
<?php
	while ( have_posts() ) : the_post();
		get_template_part( 'templates/blocks/content', 'page' );
    endwhile;
?>

<?php
    /**
     * Results Scoring
     */

    // Vars
    $screen_id = $_GET['sid'];

    $total_score = 0;

    // Gravity Forms API Connection
    $consumer_key = 'ck_0edaed6a92a48bea23695803046fc15cfd8076f5';
    $consumer_secret = 'cs_7b33382b0f109b52ac62706b45f9c8e0a5657ced';
    $headers = array( 'Authorization' => 'Basic ' . base64_encode( "{$consumer_key}:{$consumer_secret}" ) );
    $response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/'.$screen_id.'?_labels[0]=1&_field_ids[0]=1' , array( 'headers' => $headers ) );
    
    $your_answers = '';
    $result_terms = [];
    $next_step_terms = [];
    $next_step_manual = [];
    $required_result_tags = [];

    // Check the response code.
    if ( wp_remote_retrieve_response_code( $response ) != 200 || ( empty( wp_remote_retrieve_body( $response ) ) ) ){
        
        // There was an error here

    } else {

        // Got a good response, proceed
        $json = wp_remote_retrieve_body($response);
        $data = json_decode($json);

        // Text
        $label = '';
        $value_label = '';
        $screen_id = '';
        $alert = 0;
        $i = 0;

        $your_answers .= "<h3>Your Answers</h3>";
        $your_answers .= '<ul>';
            
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
                    $total_score = $total_score + $v; // Add to total score
                    $your_answers .= "<li><strong>$label:</strong> $value_label (+$v)</li>";
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
            
        $your_answers .= '</ul>';

        // For Debugging
        /*
        $your_answers .= "<h3>Optional Answers</h3>";
        $your_answers .= '<ul>';
        $previous_label = '';
        foreach($data as $k => $v){
            
            // Get field object
            $field = GFFormsModel::get_field( $data->form_id, $k );

            // Demo Questions
            if (strpos($field->cssClass, 'optional') !== false && $v != '') {   
                if($previous_label != $field->label){
                    $your_answers .= "</li>";
                    $label = $field->label; // Field label
                    $previous_label = $label;
                    $value_label = $v; // Selection Label
                    $your_answers .= "<li><strong>$label:</strong> $value_label";
                } else {                
                    $your_answers .= ", $v"; 
                }
            }
            
        }
        $your_answers .= '</ul>';
        */

    }

?>


<?
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

            echo '<h2>'.get_sub_field('result_title').'</h2>';
            if($alert > 0){
                the_field('warning_message', $screen_id);
            }
            the_sub_field('result_content');
            
        }

    endwhile;
    endif;

    echo "<p><strong>Total Score:</strong> $total_score</p>";
    echo "<hr />";

    echo "<h2>Interpretation of Scores</h2>";
    the_field('interpretation_of_scores', $screen_id);
    echo "<hr />";

    echo $your_answers;
    echo "<hr />";

    echo "<h2>Next Steps</h2>";
    echo '<ol>';

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

    echo '</ol>';


?>


</div>


<?php
get_footer();