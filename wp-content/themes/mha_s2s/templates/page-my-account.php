<?php 
/* Template Name: My Account */

if( !is_user_logged_in() ){
    wp_redirect('/log-in');
    exit();
}

get_header(); 

global $wpdb;
$uid = get_current_user_id();
$current_user = wp_get_current_user();

if(get_query_var('admin_uid') && current_user_can('administrator')){
    $uid = get_query_var('admin_uid');
    $current_user = get_user_by( 'id', $uid );
}

/**
 * Special action overrides
 */
$account_action = get_query_var('action');
$ipiden = get_ipiden();	

// Attribute a thought to this user
if (strpos($account_action, 'save_thought_') !== false) {
    $thought_id = str_replace('save_thought_', '', $account_action);
    $thought_author_id = get_post_field( 'post_author', $thought_id );
    $thought_ipiden = get_field('ipiden', $thought_id);

    if($thought_author_id == 4 && $ipiden == $thought_ipiden) {
        // Only update this thought if its anonymous and matches the user's ipiden
        $update_thought_args = array(
            'ID' => $thought_id,
            'post_author' => $uid
        );
        $update_thought = wp_update_post( $update_thought_args );
    }
}

// Attribute a DIY activity to this user
if (strpos($account_action, 'save_diy_') !== false) {
    $diy_id = str_replace('save_diy_', '', $account_action);
    $diy_author_id = get_post_field( 'post_author', $diy_id );
    $diy_ipiden = get_field('ipiden', $diy_id);
    if($diy_author_id == 4 && $ipiden == $diy_ipiden || $diy_author_id == 0 && $ipiden == $diy_ipiden) {
        // Only update this DIY response if its anonymous and matches the user's ipiden
        $update_diy_args = array(
            'ID' => $diy_id,
            'post_author' => $uid
        );
        $update_diy = wp_update_post( $update_diy_args );

        // Confirmation of saving message
        if($update_diy){
        ?>
            <div class="wrap narrow mb-4 <?php echo $width; ?>">
                <div id="screen-save">
                    <div class="bubble round green thin mb-1 <?php echo $corners; ?>">
                    <div class="inner bold text-center">
                        Your <?php echo get_the_title( get_field('activity_id', $update_diy) ); ?> activity was successfully saved to this account. 
                    </div>
                    </div>
                </div>
            </div>
        <?php
        }
    }
}


// Attribute a screen to this user
if (strpos($account_action, 'save_screen_') !== false) {
    $screen_id = str_replace('save_screen_', '', $account_action);
    $screen_ipiden = get_ipiden();

    $entry = GFAPI::get_entry( $screen_id );

    if(!is_wp_error($entry)){
                        
        // If the IP matches and uid is blank, add this test to this account
        if($entry[40] == $screen_ipiden && $entry[41] == ''){
            $entry = GFAPI::get_entry( $entry['id'] );
            $entry['41'] = $current_user->user_email; // UID field
            $update = GFAPI::update_entry( $entry );
        }

    }
}
?>

<script>
    const loadLineChart = (cdata) => {
        new Chart(document.getElementById(cdata.id).getContext('2d'), {
            type: 'line',
            data: {
                labels: cdata.labels,
                datasets: cdata.data
            },
            options: {
                title: { display: false	},
                elements: {
                    line: { tension: 0 },
                    point: { radius: 0 }
                },
                legend: { display: false },
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        lineWidth: 4,
                        minBarLength: 1,
                        gridLines: {
                            display: false,
                            color:  '#aec7dc'
                        },
                        ticks: {
                            fontFamily: "Montserrat",
                            fontColor: "#055596",
                            fontStyle: "bold",
                        }
                    }],
                    yAxes: [{
                        lineWidth: 4,
                        gridLines: {
                            color:  '#aec7dc',
                            drawBorder: false
                        },
                        ticks: {
                            fontColor: "#055596",
                            fontFamily: "Montserrat",
                            fontWeight: 'bold',
                            fontStyle: "bold",
                            padding: 10,
                            min: 0,
                            max: cdata.ymax,
                            stepSize: cdata.steps
                        }
                    }]
                }
            }
        });
    }
</script>


<article id="my-account" <?php post_class(); ?>>

	<div class="page-heading bar blue">	
		<div class="wrap normal relative">		
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			
			<div class="bubble narrow round-small-bl blue width-50" id="account-settings">
			<div class="inner">
				<div class="caps montserrat"><?php _e('Welcome', 'mhas2s'); ?>,</div>
				<?php 
                    $display_name = $current_user->first_name ? $current_user->first_name : $current_user->display_name;
                    echo '<h3 class="text-white text-truncate">'.$display_name.'</h3>'; 
                ?>

				<div class="pt-2">
					<button class="button white plain caps p-0 hover-bar" type="button" data-toggle="collapse" data-target="#account-settings-form" aria-expanded="false" aria-controls="account-settings-form">Account Settings</button> | 
					<a class="button white plain caps p-0 hover-bar" href="<?php echo wp_logout_url( home_url() ); ?>">Log Out</a>
				</div>

				<div id="account-settings-form" class="form-container line-form collapse">
					<?php 
                        $user_sso = get_field('sso', 'user_'.$current_user->ID);
                        if($user_sso != ''){
                            echo "<div class=\"pt-3\"><em>You account information is associated with your $user_sso account and cannot be modified here.</em></div>";
                        } else {
                            echo do_shortcode('[gravityform id="3" title="false" description="false"]'); 
                        }
                    ?>
				</div>

			</div>
			</div>

		</div>
	</div>

	<div class="page-intro clear" id="dashboard-content">
    <div class="wrap normal pb-5">

        <?php the_content(); ?>	


        <div id="dashboard-test-results" class="pt-5 mt-5">

            <h2 class="pt-3 mb-4 heading">Recent Test Results</h2>
            <?php
                $hidden_screens_check = $wpdb->get_results("SELECT pid FROM screens_hidden WHERE uid = $uid", ARRAY_N);
                $hide_screens = [];
                foreach($hidden_screens_check as $pid){
                    $hide_screens[] = $pid[0];
                }
            ?>

            <?php if(get_field('test_results_introduction')){ ?>
                <?php 
                    $test_result_color = 'cerulean';
                    if(get_field('test_results_introduction_-_background')){ 
                        $test_result_color = get_field('test_results_introduction_-_background');
                    }
                ?>
                <div class="bubble <?php echo $test_result_color; ?> thinner filled round-small mb-4 ml-4 mr-4">
                <div class="inner montserrat medium">
                    <?php the_field('test_results_introduction'); ?>
                </div>
                </div>  
            <?php } ?>

            <div class="container-fluid">
            <div class="row">


                <?php
                // Get all of the user's test IDs and form IDs
                    $info = $wpdb->get_results("SELECT form_id, entry_id FROM wp_gf_entry_meta WHERE meta_key = 41 AND meta_value = '$current_user->user_email' ORDER BY id DESC"); 

                    /*
                    $search_criteria = array();
                    $search_criteria['field_filters'][] = array( 
                        'key' => '41', 
                        'value' => $current_user->user_email
                    );
                    $info = GFAPI::get_entries( '0', $search_criteria);
                    */

                    $total_results = count($info); 
                    $count_results = 1;
                    $graph_data = [];
                    $pre_data = [];
                    $your_results_display = [];
                    $advanced_conditions_data = []; 
                    $general_score_data = [];                     
                    
                    if($total_results > 0):
                        foreach($info as $i){

                            // Skip hidden screens
                            if(in_array($i->entry_id, $hide_screens)) {
                                continue;
                            }

                            // Get individual screen data
                            $data = GFAPI::get_entry( $i->entry_id );

                            $total_score = 0;
                            $test_id = '';
                            foreach($data as $k => $v):
                                
                                // Get field object
                                $field = GFFormsModel::get_field( $i->form_id, $k );

                                // Get referring screen ID                    
                                if (isset($field->label) && strpos($field->label, 'Screen ID') !== false) {     
                                    $screen_id = $v;
                                }

                                // Get screen token                  
                                if (isset($field->label) && strpos($field->label, 'Token') !== false) {     
                                    $test_id = $v;
                                }

                                //Screening Questions
                                if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {   
                                    
                                    if(strpos($field->cssClass, 'exclude') === false){                                 
                                        $total_score = $total_score + intval($v); // Add to total score                                        
                                    }
                                    
                                    // Advanced Conditions Check
                                    $get_results = get_field('results', $screen_id);
                                    if( $get_results ) {
                                        foreach($get_results as $result){
                                            if($result['advanced_conditions']){
                                                foreach($result['advanced_conditions'] as $ac){
                                                    if($ac['question_id'] == $field->id){
                                                        $advanced_conditions_data[$field->id] = $v; 
                                                    }                                
                                                }
                                            }
                                        }
                                    }
                                    
                                    $general_score_data[$field->id] = $v; 
                                }                            
                            endforeach;

                            // Vars for later
                            $test_title = get_the_title($screen_id);
                            $test_date = date('M j, Y', strtotime($data['date_created']));
                            $screen_results = get_field('results', $screen_id);

                            // Custom Logic Override
                            $custom_results_logic = get_field('custom_results_logic', $screen_id);
                            $custom_result_row = '';
                            if($custom_results_logic){
                                $custom_result_logic_data = custom_logic_checker($general_score_data, $custom_results_logic);
                                $total_score = $custom_result_logic_data['total_score'];
                                $custom_result_row = $custom_result_logic_data['custom_result_row'];
                            }

                            // Min/Max
                            $min_score = 0;
                            $max_score = get_field('overall_max_score', $screen_id);
                            if($total_score >= $max_score){
                                $total_score = $max_score;
                            }

                            // Limit results 
                            if(!get_field('survey', $screen_id)){
                                $graph_data[$test_title]['hide_scores'] = get_field('hide_result_score', $screen_id);
                                $graph_data[$test_title]['labels'][] = date('M', strtotime($data['date_created']));
                                $graph_data[$test_title]['scores'][] = $total_score;
                                $graph_data[$test_title]['max'] = $max_score;
                                $graph_data[$test_title]['steps'] = get_field('chart_steps', $screen_id);
                            }

                            $your_results_display[$test_title][$count_results]['screen_id'] = $screen_id;

                            //if(!get_field('survey', $screen_id)){
                                $your_results_display[$test_title][$count_results]['test_id'] = isset($data['entry_id']) ? $data['entry_id'] : null;     
                                $your_results_display[$test_title][$count_results]['test_date'] = $test_date;
                                $your_results_display[$test_title][$count_results]['test_title'] = $test_title;
                                $your_results_display[$test_title][$count_results]['total_score'] = $total_score;
                                $your_results_display[$test_title][$count_results]['max_score'] = $max_score;
                                $your_results_display[$test_title][$count_results]['test_link'] = $test_id;     
                            //}

                            if($total_score >= $min_score && $total_score <= $max_score){
                                if(get_sub_field('required_tags')){
                                    $req = get_sub_field('required_tags');
                                    foreach($req as $t){
                                        if(in_multiarray($t, $result_terms)){
                                            $required_result_tags[] = $t;
                                        }
                                    }
                                }
                            }
                    
                            $has_advanced_conditions = 0;
                            $advanced_condition_row = '';   
                            $required_check = '0';
                            $advanced_counter = '';

                            if( have_rows('results', $screen_id) ):
                                
                                // Advanced Conditions
                                while( have_rows('results', $screen_id) ) : the_row();   
                                    $advanced_conditions = get_sub_field('advanced_conditions');
                                    if($advanced_conditions && count($advanced_conditions) > 1){
                                        
                                        $advanced_counter = count($advanced_conditions);

                                        foreach($advanced_conditions as $ac){
                                            $advanced_min = $ac['score_range_minimum'];
                                            $advanced_max = $ac['score_range_max'];
                                            $advanced_id = $ac['question_id'];  
                                            if(isset($advanced_conditions_data[$advanced_id])){
                                                if($advanced_max && $advanced_min){
                                                    if($advanced_conditions_data[$advanced_id] >= $advanced_min && $advanced_conditions_data[$advanced_id] <= $advanced_max ){
                                                        $advanced_condition_row = get_row_index();
                                                        $has_advanced_conditions++;
                                                    }
                                                } else if($advanced_min) {
                                                    if($advanced_conditions_data[$advanced_id] == $advanced_min){
                                                        $advanced_condition_row = get_row_index();
                                                        $has_advanced_conditions++;
                                                    }
                                                }
                                            }
                                        }

                                    }
                                endwhile;
                                
                                // If the total advanced conditions don't match the positive matches, reset to the first result
                                if($has_advanced_conditions != $advanced_counter){
                                    $advanced_condition_row = 0;
                                }
                                
                                // Display Results
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
                                                                                
                                        if(empty($required_result_tags) && !empty(get_sub_field('required_tags'))){
                                            continue;
                                        }
                                        
                                        if(!get_field('survey', $screen_id)){
                                            $your_results_display[$test_title][$count_results]['result_title'] = get_sub_field('result_title');
                                        }
                                    }
                                endwhile;
                            endif;

                            $count_results++;
                        }
                    endif;
                ?>
                </div>

                <div id="test-results-container">
                    <?php 
                        if($your_results_display):
                        $group_counter = 1; 
                        foreach($your_results_display as $k => $v): 
                            $total_test_results = count($v);
                            $group_slug = 'test-results-group-'.sanitize_title($k);
                            ?>

                            <div id="group-<?php echo sanitize_title($k); ?>" data-test-group="<?php echo sanitize_title($k); ?>" class="loading-container container-fluid<?php if($group_counter > 1){ echo ' hidden'; } ?>">

                                <div class="row">
                                    <?php
                                        $test_counter = 1; 
                                        foreach($v as $result):
                                            if($total_test_results > 3 && $test_counter == 4){
                                                echo '</div>';
                                                echo '<div class="row collapse all-screen-results" id="'.$group_slug.'">';
                                            }
                                        ?>                            
                                            <div class="col-lg-4 col-12 mb-4 pl-2 pr-2 screen-result-item">
                                                <div class="bubble teal thinner filled round-small-bl mb-2">
                                                <div class="inner montserrat medium">
                                                <div class="relative">

                                                    <div class="type-date small mb-2">
                                                        <?php 
                                                            echo isset($result['test_date']) ? $result['test_date'].'<br />' : '&nbsp;<br />';
                                                            echo isset($result['test_title']) ? $result['test_title'] : ''; 
                                                        ?>
                                                    </div>
                                                    <div class="caps small">Your test score was:</div>
                                                    <div class="result bold large"><?php echo isset($result['result_title']) ? $result['result_title'] : '&ndash;'; ?></div>

                                                    
                                                    <button class="hide-screen button teal round" 
                                                        id="button-<?php echo $result['test_id']; ?>"
                                                        data-toggle="tooltip" 
                                                        data-placement="top" 
                                                        title="Hide this screening result from displaying on your account."
                                                        aria-expanded="false" 
                                                        aria-controls="screen-<?php echo $result['test_id']; ?>">X</button>

                                                    <div class="hide-screen-confirm-container text-center hidden" id="screen-<?php echo $result['test_id']; ?>">
                                                        <div class="pb-2">
                                                            <button class="hide-screen-confirm thin button red round small" 
                                                                data-toggle="tooltip" 
                                                                data-pid="<?php echo $result['test_id']; ?>" 
                                                                data-nonce="<?php echo wp_create_nonce('hideScreen'); ?>" >Are you sure you want to hide this result?</button>
                                                        </div>
                                                        <button class="cancel-hide-screen plain text-white round">Nevermind</button>
                                                    </div>
                                                    
                                                </div>
                                                </div>
                                                </div>
                                                <a href="/screening-results/?sid=<?php echo $result['test_link']; ?>" class="bubble mint thinner round-small bubble-link text-dark-blue">
                                                    <span class="inner result caps text-center bold montserrat block">
                                                        <?php 
                                                            if(!get_field('hide_result_score', $result['screen_id'])){
                                                                echo 'About your score: '.$result['total_score']; ?> / <?php echo $result['max_score']; 
                                                            } else {
                                                                echo 'About your result'; 
                                                            }
                                                        ?>
                                                    </span>
                                                </a>
                                            </div>
                                        <?php 
                                        $test_counter++;
                                        endforeach; 
                                    ?>                            
                                </div>
                                
                                <?php 
                                    // More results button
                                    if($total_test_results > 3): 
                                ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <p class="text-right mb-0">
                                                <button class="plain teal caps large bold" 
                                                    type="button" 
                                                    data-toggle="collapse" 
                                                    data-target="#<?php echo $group_slug; ?>" 
                                                    aria-expanded="false" 
                                                    aria-controls="<?php echo $group_slug; ?>">Show More Results</button>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                            
                            <?php 
                            $group_counter++;
                            endforeach; 
                        else:
                            echo '<p>You have not taken any mental health tests. <a href="/screening-tools">Explore mental health tests</a></p>';
                        endif;
                    ?>
                </div>


            </div>

            <?php if(!empty($graph_data)): ?>
            <div id="test-selection-dropdown" class="dropdown dropdown-menu-right">
                <button class="button gray round-br dropdown-toggle" type="button" id="testSelection" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="truncate not-upper medium"><?php echo array_key_first($graph_data); ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="testSelection">
                    <?php foreach($graph_data as $key => $value): ?>
                        <button class="dropdown-item show-test-group" type="button" data-group-control="<?php echo sanitize_title($key); ?>"><?php echo $key; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>


            <?php 
                $chart_counter = 0;
                if(!empty($graph_data)):
                    foreach($graph_data as $k => $v): 
                    ?>
                    <div class="container-fluid loading-container pt-4<?php if($chart_counter > 0){ echo ' hidden'; } ?>" data-test-group="<?php echo sanitize_title($k); ?>">
                    <div class="row">

                        <div class="col-12">
                            <h3 class="text-dark-teal mb-4">Recent <?php echo $k; ?> Results Over Time</h3>
                        </div>

                        <div class="col-12 col-md-8">

                            <div class="bubble bubble-border round-tl results-graph">
                            <div class="inner">
                            <?php
                                if(!$v['hide_scores']):
                                    // Better chronological data
                                    $reverse_labels = array_reverse($v['labels']);
                                    $reverse_scores = array_reverse($v['scores']);
                                    $pre_data = [
                                        'borderWidth' => 3,
                                        'fill' => false,
                                        'backgroundColor' => '#199aa0',
                                        'borderColor' => '#199aa0',
                                        'pointRadius' => 4,
                                        'data' => $reverse_scores
                                    ];
                                    
                                    echo '<div class="chart-container"><canvas id="canvas-'.$chart_counter.'"></canvas></div>';
                                    $chartData = [
                                        'id' => 'canvas-'.$chart_counter,
                                        'labels' => $reverse_labels,
                                        'data' => [$pre_data],
                                        'ymax' => $v['max'],
                                        'steps' => $v['steps'] 
                                    ];
                                    $cdata = json_encode($chartData,JSON_NUMERIC_CHECK);
                                    ?>

                                    <script>loadLineChart(<?=$cdata?>);</script>
                                    <?php
                                else:
                                    echo '<p class="p-5 text-center text-gray"><em>Numbered scores not available for this test.</em></p>';
                                endif;
                                $chart_counter++;
                            ?>
                            </div>
                            </div>

                        </div>

                        <div class="col-12 col-md-4">
                            <div class="bubble round-tl mint">
                            <div class="inner">
                                <h3 class="text-green mb-4">Explore more mental health tests</h3>
                                <p class="text-center mb-0"><a class="button teal round" href="/screening-tools">Take a mental health&nbsp;test</a></p>
                            </div>
                            </div>
                        </div>

                    </div>
                    </div>   
                    <?php 
                    endforeach;
                endif; 
            ?>         

        </div>

        <!-- DIY Tools Thoughts -->
        <div class="dashboard-block diy-activity pt-5 pb-5">
            <h2 class="bar">DIY Tools Submissions</h2>                
            <?php
                $hidden_thoughts_check = $wpdb->get_results("SELECT pid FROM thoughts_hidden WHERE uid = $uid", ARRAY_N);
                $hide_diy_submissions = [];
                foreach($hidden_thoughts_check as $pid){
                    $hide_diy_submissions[] = $pid[0];
                }
                $args = array(
                    "author" => $uid,
                    "post_type" => 'diy_responses',
                    "orderby" => 'date',
                    "post_status" => array( 'draft', 'publish' ),
                    "order"	=> 'DESC',
                    "posts_per_page" => 50,
                    'post__not_in' => $hide_diy_submissions,
                );
                $loop = new WP_Query($args);
                $loop_total = $loop->found_posts;
                $counter = 0;

                if($loop->have_posts()):
                while($loop->have_posts()) : $loop->the_post();
                
                    $responses = get_field('response');
                    $activity_id = get_field('activity_id');
                    $questions = get_field('questions', $activity_id);
                    $answer_id = get_the_ID();
                    
                    $initial_response = '';
                    $diy_key = 0;
                    if($responses):
                        foreach($responses as $r){
                            if($r['answer'] != ''){
                                // Get the first response
                                $initial_response = $r['answer'];
                                $diy_key = $r['id'];
                                break;
                            }
                        }
                    endif;
                    
                    if( $initial_response == '' ){
                        $initial_response = ' &mdash; (<em>This submission was removed</em>)';
                    } 

                    if($counter == 3 && $loop_total > 3){
                        // Collapse submissions if more than 5
                        echo '<div class="collapse" id="allDiyTools">';
                    }
                ?>
                <div class="bubble round-small-bl thin relative gray mb-4">
                <div class="inner">
                    
                    <div class="relative">
                        
                        <div claass="container-fluid">
                        <div class="row">
                            <div class="coll-12 col-md-8 monsterrat medium text-blue-dark pb-2 pb-md-0">
                                <div class="mb-0">
                                    <div class="label bold mb-3"><a class="button teal tiny round-tiny-bl" href="<?php echo get_the_permalink($activity_id); ?>" target="_blank"><?php echo get_the_title($activity_id); ?></a></div>
                                    <?php if(isset($questions[$diy_key]['question'])): ?><div class="label bold small mb-3"><?php echo $questions[$diy_key]['question']; ?></div><?php endif; ?>
                                    <?php echo $initial_response; ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <p><?php echo '<a class="bar plain" href="'.get_the_permalink($answer_id).'">Review your submission&nbsp;&raquo;</a>'; ?></p>

                                <p>
                                    <label for="private_thought_<?php echo $answer_id; ?>">
                                        <input type="checkbox" 
                                            value="1" 
                                            class="toggle_private_thought"
                                            name="private_thought_<?php echo $answer_id; ?>"
                                            id="private_thought_<?php echo $answer_id; ?>" 
                                            data-id="<?php echo $answer_id; ?>" 
                                            <?php if(get_field('crowdsource_hidden', $answer_id)){ echo 'checked'; }; ?> 
                                        />
                                        Hide this from public submissions (all submissions are anonymous)
                                    </label>
                                    <div data-thought="<?php echo $answer_id; ?>" class="toggle_private_thought_message bubble white thinner d-none round-tl"><div class="inner"></div></div>
                                </p>
                            </div>
                        </div>
                        </div>
                        
                        <button class="hide-thought button gray round" 
                            id="button-<?php echo $answer_id; ?>"
                            data-toggle="tooltip" 
                            data-placement="top" 
                            title="Hide this from displaying on your account."
                            aria-expanded="false" 
                            aria-controls="thought-<?php echo $answer_id; ?>">X</button>

                        <div class="hide-thought-confirm-container text-center hidden" id="thought-<?php echo $answer_id; ?>">
                            <div class="pb-2">
                                <button class="hide-thought-confirm thin button red round small" 
                                    data-toggle="tooltip" 
                                    data-pid="<?php echo $answer_id; ?>"                                      
                                    data-nonce="<?php echo wp_create_nonce('hideThought'); ?>" >Are you sure you want to hide this?</button>
                            </div>
                            <button class="cancel-hide-thought plain gray round text-gray">Nevermind</button>
                        </div>

                    </div>

                </div>
                </div>
                <?php 
                $counter++;
                endwhile; 
                else: ?>
                    <p>You have no DIY Tool submissions.</p>
                    <p><a class="button blue round" href="/diy">Explore DIY Tools</a></p>
                <?php
                endif;
                
                if($counter > 3 && $loop_total > 3){
                    echo '</div>';
                }
                if($loop_total > 3){
                    ?>
                    <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 col-sm-6 text-sm-left text-center pl-0 mb-3 mb-sm-0">
                            <button class="button teal round-tl" type="button" data-toggle="collapse" data-target="#allDiyTools" aria-expanded="false" aria-controls="allDiyTools">View All DIY Tool Submissions</button>
                        </div>
                    </div>
                    </div>
                    <?php
                }
                wp_reset_query();
            ?>
        </div>

        
        <!-- Overcoming Thoughts -->      
            <?php
                $hidden_thoughts_check = $wpdb->get_results("SELECT pid FROM thoughts_hidden WHERE uid = $uid", ARRAY_N);
                $hide_thoughts = [];
                foreach($hidden_thoughts_check as $pid){
                    $hide_thoughts[] = $pid[0];
                }
                $args = array(
                    "author" => $uid,
                    "post_type" => 'thought',
                    "orderby" => 'date',
                    "post_status" => array( 'draft', 'publish' ),
                    "order"	=> 'DESC',
                    "posts_per_page" => 100,
                    'post__not_in' => $hide_thoughts,
                );
                $loop = new WP_Query($args);
                $loop_total = $loop->found_posts;
                $counter = 0;

                if($loop->have_posts()):
                ?>

                    <div class="dashboard-block thought-activity pt-5 pb-5">
                    <h2 class="bar">Overcoming Thoughts</h2>          

                <?php
                while($loop->have_posts()) : $loop->the_post();
                
                    $responses = get_field('responses');
                    $activity_id = get_field('activity');
                    $abandoned = get_field('abandoned');
                    $status = get_post_status();
                                            
                    if($responses[0]['response'] != ''){
                        // User's thought
                        $initial_thought = $responses[0]['response'];
                    } else if($responses[0]['admin_pre_seeded_thought'] != '') {
                        // Admin pre-seeded thought
                        $initial_thought = get_field('pre_generated_responses', $activity_id);
                        $initial_thought = $initial_thought[$responses[0]['admin_pre_seeded_thought']]['response'];
                    } else if($responses[0]['user_pre_seeded_thought'] != '') {
                        // Other user pre-seeded thought
                        $initial_thought = get_field('responses', $responses[0]['user_pre_seeded_thought']);
                        $initial_thought = $initial_thought[0]['response'];
                    } else {
                        $initial_thought = ' &mdash; (<em>Thought was removed</em>)';
                    }  

                    if($counter == 3 && $loop_total > 3){
                        // Collapse thoughts if more than 5
                        echo '<div class="collapse" id="allThoughts">';
                    }
                ?>
                    <div class="bubble round-small-bl thin relative gray mb-4">
                    <div class="inner">
                        
                        <div class="relative">
                            
                            <div claass="container-fluid">
                            <div class="row">
                                <div class="coll-12 col-md-8
                                monsterrat medium text-blue-dark pb-2 pb-md-0">
                                    <div class="mb-3"><?php echo $initial_thought; ?></div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <?php 
                                        if($status != 'publish'){
                                            echo '<a class="bar plain" href="'.get_the_permalink($activity_id).'">Continue this thought&nbsp;&raquo;</a>';
                                        } else {
                                            echo '<a class="bar plain" href="'.get_the_permalink(get_the_id()).'">Review your submission&nbsp;&raquo;</a>';
                                        }
                                    ?>
                                </div>
                            </div>
                            </div>
                            
                            <button class="hide-thought button gray round" 
                                id="button-<?php echo get_the_ID(); ?>"
                                data-toggle="tooltip" 
                                data-placement="top" 
                                title="Hide this thought from displaying on your account."
                                aria-expanded="false" 
                                aria-controls="thought-<?php echo get_the_ID(); ?>">X</button>

                            <div class="hide-thought-confirm-container text-center hidden" id="thought-<?php echo get_the_ID(); ?>">
                                <div class="pb-2">
                                    <button class="hide-thought-confirm thin button red round small" 
                                        data-toggle="tooltip" 
                                        data-pid="<?php echo get_the_ID(); ?>"                                      
                                        data-nonce="<?php echo wp_create_nonce('hideThought'); ?>" >Are you sure you want to hide this thought?</button>
                                </div>
                                <button class="cancel-hide-thought plain gray round text-gray">Nevermind</button>
                            </div>

                        </div>

                    </div>
                    </div>
                    <?php 
                    $counter++;
                endwhile; 
                /*
                else :?>
                    <p>You have no submitted thoughts.</p>
                    <p><a class="button blue round" href="/overcoming-thoughts">What thought are you struggling with right now?</a></p>
                <?php
                */
                endif;
                
                if($counter > 3 && $loop_total > 3){
                    echo '</div>';
                    ?>
                        <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 col-sm-6 text-sm-left text-center pl-0 mb-3 mb-sm-0">
                                <button class="button gray round-tl" type="button" data-toggle="collapse" data-target="#allThoughts" aria-expanded="false" aria-controls="allThoughts">View All Thoughts</button>
                            </div>
                            <div class="col-12 col-sm-6 text-sm-right text-center pr-0">
                                <a href="/overcoming-thoughts" class="button round-br blue">Start a new thought</a>
                            </div>
                        </div>
                        </div>
                    <?php 
                } elseif($counter > 0 && $counter <= 3){
                    ?>
                        <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 text-right pr-0">
                                <a href="/overcoming-thoughts" class="button round-br blue">Start a new thought</a>
                            </div>
                        </div>
                        </div>
                    <?php 
                }
                wp_reset_query();
            ?>

        <?php
            // CTA
            global $post; 
            if( have_rows('actions', 'option') ):
            while( have_rows('actions', 'option') ) : the_row();  
                $post = get_post(get_sub_field('action')); 
                setup_postdata($post);
                get_template_part( 'templates/blocks/block', 'cta' );  
            endwhile;
            endif;
            wp_reset_query();
        ?>

        <h2 class="mb-4 mt-5 pt-2">Saved Mental Health Information</h2>
        <?php
            $uid = get_current_user_id();
            $liked_articles = [];
            $liked_resources = [];
            $resources = array('diy','connect','treatment','provider');
            $likes = $wpdb->get_results("SELECT pid FROM article_likes WHERE uid = $uid AND unliked = 0 ORDER BY id DESC");	

            foreach($likes as $like){
                $article_type = get_field('type', $like->pid);                    
                if($article_type && count(array_intersect($article_type, $resources)) > 0){
                    $liked_resources[] = $like->pid;
                } else {
                    $liked_articles[] = $like->pid;
                }
            }
        ?>

        <div class="container-fluid">
        <div class="row">

            <div class="col-12 col-md-6 mb-4 mb-md-0">
                <div class="bubble round-small thin pale-blue bubble-border">
                <div class="inner">
                    <h4>Mental Health Information</h4>
                    <?php
                        if(!empty($liked_articles)){
                            echo '<ol class="link-list">';
                            foreach($liked_articles as $article){
                                echo '<li><a class="plain dark-gray montserrat medium" href="'.get_the_permalink($article).'">'.get_the_title($article).'</a></li>';
                            }
                            echo '</ol>';
                        } else {
                            echo '<p>You have no saved articles.</p>';
                        }
                    ?>
                </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="bubble round-small red thin bubble-border">
                <div class="inner">
                    <h4>Resources</h4>
                    <?php
                        if(!empty($liked_resources)){
                            echo '<ol class="link-list">';
                            foreach($liked_resources as $article){
                                echo '<li><a class="plain dark-gray montserrat medium" href="'.get_the_permalink($article).'">'.get_the_title($article).'</a></li>';
                            }
                            echo '</ol>';
                        } else {
                            echo '<p>You have no saved resources.</p>';
                        }
                    ?>
                </div>
                </div>
            </div>

        </div>
        </div>
                    
    </div>
    </div>

</article>

<div class="wrap normal">
    <div class="clear pt-4">
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
    </div>
</div>

<?php

get_footer();