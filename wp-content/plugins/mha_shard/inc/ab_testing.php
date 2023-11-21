<?php

/**
 * A custom A/B testing tool based on ACF options
 */

add_action('init', 'mhaAbRedirectScripts');
function mhaAbRedirectScripts() {
	wp_enqueue_script('process_mhaAbTest', plugin_dir_url( __FILE__ ).'ab_testing.js', array( 'jquery' ), time(), true);
	wp_localize_script('process_mhaAbTest', 'do_mhaAbTesting', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_body_open', 'mha_ab_redirects_setup');
function mha_ab_redirects_setup(){

    $ab_setup = [];
    $ab_setup['current_id'] = get_the_ID();
    $ab_setup['current_url'] = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $ab_setup['current_path'] = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $ab_setup['current_referrer'] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
    $ab_setup['get'] = $_GET;
    echo '<textarea id="ab-testing-data" style="display: none !important;">'.str_replace( '%2C', ',', json_encode( $ab_setup, JSON_UNESCAPED_SLASHES ) ).'</textarea>';
    echo '<textarea id="ab-testing-get" style="display: none !important;">'.str_replace( '%2C', ',', json_encode( $_GET, JSON_UNESCAPED_SLASHES ) ).'</textarea>';

}

add_action("wp_ajax_nopriv_mha_ab_redirect_logic", "mha_ab_redirect_logic");
add_action("wp_ajax_mha_ab_redirect_logic", "mha_ab_redirect_logic");
function mha_ab_redirect_logic(){

    // Post data
    $defaults = array(
        'show_debug'             => false,
        'redirect'               => null,
        'debug_header'           => null,
        'debug_log'              => null,
        'debug_footer'           => null,
        'current_id'             => null,
        'current_url'            => null,
        'current_path'           => null,
        'current_referrer'       => null,
        'current_referrer_url'   => null,
        'get'                    => array(),
    );    

    // Main results
    parse_str(stripslashes($_POST['data']), $data);  
    $result = wp_parse_args( $data, $defaults ); 
        
    // GET Params
    parse_str(stripslashes($_POST['get']), $get_data);  
    $result['get'] = $get_data;

    // Referrer 
    $result['current_referrer_url'] = sanitize_url($_POST['referrer']);
    
    $debug_all = false;
    if( get_field('mha_ab_debug', 'options') ){
        $debug_all = true;
    }
    $current_pid = $result['current_id'];
    $current_url = $result['current_url'];
    $current_path = $result['current_path'];
    $current_referrer = $result['current_referrer_url'];
    $current_referrer_path = $current_referrer ? parse_url($current_referrer, PHP_URL_PATH) : null;
    $current_referrer_params = [];

    if($current_referrer){
        $referral_url = parse_url($current_referrer);
        $result['ref_param_checker_url_source'] = $result['current_referrer_url'];
        $result['ref_param_checker_original'] = $current_referrer;
        $result['ref_param_checker'] = $referral_url;
        $referral_string = isset($referral_url['query']) ? $referral_url['query'] : null;
        if($referral_string){
            parse_str($referral_string, $current_referrer_params);
        }
    }


    $debug_html_header = '
<button class="button" type="button" data-toggle="collapse" data-target="#collapseABbDebug" aria-expanded="false" aria-controls="collapseABbDebug">
    Display A/B Test Debug Info
</button>
<div class="collapse" id="collapseABbDebug">
<div class="card card-body large">

<pre class="large">';
$debug_html_footer = '</pre></div></div>';

    $debug_log = [];
    $debug_log['Current URL'] = $current_url;
    $debug_log['Current Path'] = $current_path;
    $debug_log['Current Referrer URL'] = $current_referrer;
    $debug_log['Current Referrer Path'] = $current_referrer_path;
    $debug_log['Current Referrer Params'] = $current_referrer_params;
    $has_debug = false;

    if( have_rows('mha_ab_redirects', 'options') ):
    while( have_rows('mha_ab_redirects', 'options') ) : the_row();
    
        $active = get_sub_field('active');
        $name = get_sub_field('name');
        $target_page = get_sub_field('target');
        $con_operator = get_sub_field('operator');
        $proceed = false;
        $debug = false;

        if( get_sub_field('debug_mode') || $debug_all ){
            $debug = true;
            $has_debug = true;
        }

        if($debug){ 
            $debug_log['Experiment'][$name]['Active State'] = $active;
            $debug_log['Experiment'][$name]['Condition'] = $name; 
        }

        if(!$target_page){
            $target_page = array('all');
        }

        if(!$active){
            // Skip non-active tests
            // if(!$debug){ continue; }
        }

        // Check if on a target page
        foreach( $target_page as $tid ){
            if( $tid == $current_pid || $tid == 'all' ){

                if($debug){
                    $debug_log['Experiment'][$name]['Post ID'] = $tid;
                    $debug_log['Experiment'][$name]['Conditions Met'] = [];
                }

                // Conditions
                $i = 0;
                $con_score = 0;


                if( have_rows('target_page_conditions') ):
                while( have_rows('target_page_conditions') ) : the_row();

                    $con_type = get_sub_field('type');
                    $con_condition = get_sub_field('condition');
                    $con_key = get_sub_field('key');
                    //$get_key = isset($result['get'][$con_key]) ? sanitize_text_field($result['get'][$con_key]) : null;
                    //$get_key = get_query_var($con_key) ? get_query_var($con_key) : 'Nope '.$con_key.' / '.$_GET[$con_key];
                    $get_key = isset($result['get'][$con_key]) ? sanitize_text_field($result['get'][$con_key]) : null;
                    $con_value = get_sub_field('value');

                    if($debug){ $debug_log['Experiment'][$name]['condition_type'][] = $con_type; }
                    if($debug){ $debug_log['Experiment'][$name]['condition_key'][] = $con_key; }
                    if($debug){ $debug_log['Experiment'][$name]['condition_value'][] = $con_condition; }
                    if($debug){ $debug_log['Experiment'][$name]['condition_get_key'][] = $get_key; }

                    switch($con_type){
                        
                        case 'path':
                            switch($con_condition){
                                case 'equals':
                                    if($current_path == $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if($current_path && str_contains($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if($current_path && str_starts_with($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if($current_path && str_ends_with($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if($current_path != $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if($current_path && !str_contains($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if($current_path && !str_starts_with($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if($current_path && !str_ends_with($current_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                            }
                            break;

                        case 'referrer_path':
                            switch($con_condition){
                                case 'equals':
                                    if($current_referrer_path == $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if($current_referrer_path && str_contains($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if($current_referrer_path && str_starts_with($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if($current_referrer_path && str_ends_with($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if($current_referrer_path != $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if($current_referrer_path && !str_contains($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if($current_referrer_path && !str_starts_with($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if($current_referrer_path && !str_ends_with($current_referrer_path, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                            }
                            break;
    
                        case 'referrer_url':
                            switch($con_condition){
                                case 'equals':
                                    if($current_referrer == $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if($current_referrer && str_contains($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if($current_referrer && str_starts_with($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if($current_referrer && str_ends_with($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if($current_referrer != $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if($current_referrer && !str_contains($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if($current_referrer && !str_starts_with($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if($current_referrer && !str_ends_with($current_referrer, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                            }
                            break;
    
                        case 'url_parameter':
                            switch($con_condition){
                                case 'equals':
                                    if($get_key == $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if( $get_key && str_contains($get_key, $con_value) ){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if( $get_key && str_starts_with($get_key, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if( $get_key && str_ends_with($get_key, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if($get_key != $con_value || !$get_key){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if( $get_key && !str_contains($get_key, $con_value) || !$get_key){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if( $get_key && !str_starts_with($get_key, $con_value) || !$get_key){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if( $get_key && !str_ends_with($get_key, $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'exists':
                                    if($get_key){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                            }
                            break;
    
                        case 'referrer_parameter':
                            switch($con_condition){
                                case 'equals':
                                    if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                    if(isset($current_referrer_params[$con_key]) && $current_referrer_params[$con_key] == $con_value){
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if( isset($current_referrer_params[$con_key]) && str_contains($current_referrer_params[$con_key], $con_value) ){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if( isset($current_referrer_params[$con_key]) && str_starts_with(isset($current_referrer_params[$con_key]), $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if( isset($current_referrer_params[$con_key]) && str_ends_with($current_referrer_params[$con_key], $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if(isset($current_referrer_params[$con_key]) && $current_referrer_params[$con_key] != $con_value){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if( isset($current_referrer_params[$con_key]) && !str_contains($current_referrer_params[$con_key], $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if( isset($current_referrer_params[$con_key]) && !str_starts_with($current_referrer_params[$con_key], $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if( isset($current_referrer_params[$con_key]) && !str_ends_with($current_referrer_params[$con_key], $con_value)){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'exists':
                                    if(isset($current_referrer_params[$con_key])){
                                        if($debug){ $debug_log['Experiment'][$name]['Conditions Met'][] = $con_type.' '.$con_condition.' "'.$con_value.'"'; }
                                        $con_score++;
                                    }                                     
                                    break;
                            }
                            break;

                    }

                    $i++;
                endwhile;
                endif;

                if($debug){
                    $debug_log['Experiment'][$name]['Total Conditions'] = $i;
                    $debug_log['Experiment'][$name]['Condition Score'] = $con_score;
                    $debug_log['Experiment'][$name]['Condition Operator'] = $con_operator;
                }

                // Operator: "and"
                if($con_operator == 'and' && $i == $con_score){
                    if($debug){ $debug_log['Experiment'][$name]['Condition Met'] = 'Yes'; }
                    $proceed = true;                    
                }

                // Operator: "or"
                if($con_operator == 'or' && $con_score > 0){
                    if($debug){ $debug_log['Experiment'][$name]['Condition Met'] = 'Yes'; }
                    $proceed = true;                    
                }

                if($proceed == true){

                    $variants = [];
                    $variant_values = [];
                    $variant_weights = [];

                    if( have_rows('variants', ) ):
                    while( have_rows('variants') ) : the_row();

                        $variant_name = get_sub_field('variant_name');
                        $variant_weight = get_sub_field('weight');
                        $variant_url = get_sub_field('url_to_redirect');
                        $variant_type = get_sub_field('variant_type');
                        $variant_param_key = get_sub_field('parameter_key');
                        $variant_param_val = get_sub_field('parameter_value');

                        $variants[get_row_index()] = array(
                            'name' => $variant_name,
                            'weight' => $variant_weight,
                            'type' => $variant_type,
                            'url' => $variant_url,
                            'param_key' => $variant_param_key,
                            'param_val' => $variant_param_val,
                        );
                        $variant_values[] = $variant_url;
                        $variant_weights[] = $variant_weight;

                    endwhile;
                    endif;

                    // Get the variant and redirect
                    if(!empty($variants)){

                        $redirect_url = null;
                        $variant_result = get_weighted_random_result($variant_values, $variant_weights);
                        $db_insert = array(
                            'ipiden' => get_ipiden(),
                            'redirect_name' => $name,
                            'redirect' => '',
                            'variant_name' => $variants[$variant_result['key']]['name'],
                            'source' => $current_url,
                            'log' => json_encode($debug_log, JSON_UNESCAPED_SLASHES)
                        );
                        if($debug){ $debug_log['Experiment'][$name]['Variant Result'] = $variants[$variant_result['key']]; }
    
                        if($variant_result){
                            
                            switch($variants[$variant_result['key']]['type']){
                                case 'redirect':                                        
                                    $redirect_url = $variants[$variant_result['key']]['url'];
                                    break;

                                case 'add_parameter':
                                    $redirect_url = add_query_arg( $variants[$variant_result['key']]['param_key'], $variants[$variant_result['key']]['param_val'], $current_url );
                                    break;

                                case 'remove_parameter':
                                    $redirect_url = remove_query_arg( $variants[$variant_result['key']]['param_key'], $current_url );
                                    break;
                            }
                            $db_insert['redirect'] = $redirect_url;      
    
                            // Debug logging                      
                            // if($debug){ $debug_log['Experiment'][$name]['DB Record'] = $db_insert; }
                            if($debug){ $debug_log['Experiment'][$name]['Redirecting To'] = $redirect_url; }
                            if($debug){ 
                                $debug_log['Redirect'][] = $redirect_url;
                            }

                            if($redirect_url == $current_url){
                                $db_insert['Note'] = 'Already on this URL, redirect skipped.<br />';      
                            }

                            if($active && $redirect_url && $redirect_url != $current_url && !$debug){
                                // Final redirect
                                global $wpdb;  
                                $db_result = $wpdb->insert( 'ab_redirects', $db_insert );
                                $result['redirect'] = $redirect_url;
                            }  
    
                        }
    
                    }

                } else {

                    if($debug){ $debug_log['Experiment'][$name]['Condition Met'] = 'No'; }

                }

            } else {
                if($debug){ $debug_log['Experiment'][$name]['Note'] = 'Not on a relevant page for debugging.'; }
            }
        }

    endwhile;    
    endif;

    if(
        $has_debug && current_user_can('editor') || 
        $has_debug && current_user_can('administrator')
    ){ 
        $result['show_debug'] = true;
        $result['debug_log'] = $debug_html_header.' '.print_r($debug_log, true).' '.$debug_html_footer;
    }

    // Wrap it up
    echo json_encode($result);
    exit();
}

/**
 * Helper function to select winning weighted result
 */
function get_weighted_random_result($values, $weights){ 
    $count = count($values); 
    $i = 0; 
    $n = 0; 

    if (array_sum($weights) <= 0 ) { 
        return false; 
    }

    $num = mt_rand(1, array_sum($weights)); 

    while($i < $count){
        $n += $weights[$i]; 
        if($n >= $num){
            break; 
        }
        $i++; 
    } 

    $result = array(
        'key' => $i,
        'number' => $num,
        'value' => $values[$i],
        'weight' => $weights[$i],
    );

    return $result;
}