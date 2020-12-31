<?php 
/* Template Name: My Account */
get_header(); 
?>

<article id="my-account" <?php post_class(); ?>>

	<div class="page-heading bar blue">	
		<div class="wrap normal relative">		
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			
			<div class="bubble narrow round-small-bl blue width-50" id="account-settings">
			<div class="inner">
				<div class="caps large">DISPLAY NAME:</div>
				<?php
					global $current_user;
					get_currentuserinfo();
					echo '<h3 class="text-white">'.$current_user->nickname.'</h3>';
				?>

				<div class="pt-2">
					<button class="button white plain caps p-0 hover-bar" type="button" data-toggle="collapse" data-target="#account-settings-form" aria-expanded="false" aria-controls="account-settings-form">Account Settings</button> | 
					<a class="button white plain caps p-0 hover-bar" href="<?php echo wp_logout_url( home_url() ); ?>">Log Out</a>
				</div>

				<div id="account-settings-form" class="form-container line-form collapse">
					<?php echo do_shortcode('[gravityform id="3" title="false" description="false"]'); ?>
				</div>

			</div>
			</div>

		</div>
	</div>

	<div class="page-intro clear" id="dashboard-content">
    <div class="wrap normal pb-5">

        <?php the_content(); ?>	


        <div id="dashboard-test-results" class="pt-5 mt-5">

            <h2 class="pt-3 mb-4">Recent Test Results</h2>

            <div class="container-fluid">
            <div class="row">

                <?php
                    $current_user = wp_get_current_user();
                    $consumer_key = 'ck_0edaed6a92a48bea23695803046fc15cfd8076f5';
                    $consumer_secret = 'cs_7b33382b0f109b52ac62706b45f9c8e0a5657ced';
                    $headers = array( 'Authorization' => 'Basic ' . base64_encode( "{$consumer_key}:{$consumer_secret}" ) );
                    $response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/?search={"field_filters": [{"key":41,"value":"'.$current_user->email.'","operator":"contains"}]}', array( 'headers' => $headers ) );
                    
                    // Check the response code.
                    if ( wp_remote_retrieve_response_code( $response ) != 200 || ( empty( wp_remote_retrieve_body( $response ) ) ) ){
                        
                        // Error!
                        echo '<p>There was a problem displaying to your results. Please contact us if the issue persists.</p>';
                        echo '<p><strong>Response Error:</strong>'.wp_remote_retrieve_response_code( $response ).'<br />';
                        echo '<strong>ID:</strong>'.$user_screen_id.'</p>';

                    } else {

                        // Got a good response, proceed!
                        $json = wp_remote_retrieve_body($response);
                        $info = json_decode($json); 
                        $total_results = $info->total_count;    
                        $count_results = 1;
                        $graph_data = [];
                        $pre_data = [];
                        
                        if($total_results > 0):
                            foreach($info->entries as $data){
                                $total_score = 0;
                                foreach($data as $k => $v):
                                    // Get field object
                                    $field = GFFormsModel::get_field( $data->form_id, $k );

                                    // Get referring screen ID                    
                                    if (strpos($field->label, 'Screen ID') !== false) {     
                                        $screen_id = $v;
                                    }
                                    //Screening Questions
                                    if (strpos($field->cssClass, 'question') !== false) {                               
                                        $total_score = $total_score + $field['choices'][$v]['value']; // Add to total score
                                    }                            
                                endforeach;

                                // Vars for later
                                $test_title = get_the_title($screen_id);
                                $test_date = date('M j, Y', strtotime($data->date_created));
                                $screen_results = get_field('results', $screen_id);

                                // Min/Max
                                $min_score = 0;
                                $max_score = get_field('overall_max_score', $screen_id);
                                if($total_score >= $max_score){
                                    $total_score = $max_score;
                                }
                                if(count($graph_data[$test_title]['labels']) < 21){
                                    $graph_data[$test_title]['labels'][] = date('M', strtotime($data->date_created));
                                    $graph_data[$test_title]['scores'][] = $total_score;
                                }

                                if($total_results > 3 && $count_results == 4){
                                    echo '</div>';
                                    echo '<div class="row collapse" id="allScreenResults">';
                                }
                                ?>
                                    <div class="col-4 mb-4 pl-2 pr-2">
                                        <div class="bubble teal thinner filled round-small-bl mb-2">
                                        <div class="inner montserrat medium">
                                            <div class="type-date small"><?php echo $test_title.' &ndash; '. $test_date; ?></div>
                                            <div class="caps small">Your test score was:</div>
                                            <div class="result bold large">
                                                <?php                          
                                                    if($total_score >= $min && $total_score <= $max){
                                                        if(get_sub_field('required_tags')){
                                                            $req = get_sub_field('required_tags');
                                                            foreach($req as $t){
                                                                if(in_multiarray($t, $result_terms)){
                                                                    $required_result_tags[] = $t;
                                                                }
                                                            }
                                                        }
                                                    }
                    
                                                    if( have_rows('results', $screen_id) ):
                                                    while( have_rows('results', $screen_id) ) : the_row();                                    
                                                        $min = get_sub_field('score_range_minimum');
                                                        $max = get_sub_field('score_range_max');                                                    
                                                        if($total_score >= $min && $total_score <= $max){                                                    
                                                            if(empty($required_result_tags) && !empty(get_sub_field('required_tags'))){
                                                                continue;
                                                            } else {
                                                                the_sub_field('result_title');
                                                            }
                                                        }
                                                    endwhile;
                                                    endif;
                                                ?>
                                            </div>
                                        </div>
                                        </div>
                                        <a href="/screening-results/?sid=<?php echo $data->id; ?>" class="bubble mint thinner round-small bubble-link text-dark-blue">
                                            <span class="inner result caps text-center bold montserrat block">
                                                About your Score: <?php echo $total_score; ?> / <?php echo $max_score; ?>
                                            </span>
                                        </a>
                                    </div>
                                <?php
                                $count_results++;
                            }
                        else: 
                            echo '<p>You have not taken any mental health tests. <a href="/screening-tools">Explore mental health tests</a></p>';
                        endif;
                        
                        // Better chronological data
                        $reverse_labels = array_reverse($graph_data[$test_title]['labels']);
                        $reverse_scores = array_reverse($graph_data[$test_title]['scores']);

                        $pre_data[] = [
                            'borderWidth' => 3,
                            'fill' => false,
                            'backgroundColor' => '#1CA4AB',
                            'borderColor' => '#1CA4AB',
                            'pointRadius' => 4,
                            'data' => $reverse_scores
                        ];
                    }
                ?>
                </div>

                <?php 
                    // More results button
                    if($total_results > 3): 
                ?>
                    <div class="row">
                        <div class="col-12">
                            <p class="text-right mb-0">
                                <button class="plain teal caps large bold" type="button" data-toggle="collapse" data-target="#allScreenResults" aria-expanded="false" aria-controls="allScreenResults">Show More Results</button>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <?php if($graph_data): ?>
            <div class="dropdown">
                <button class="button gray round-br dropdown-toggle" type="button" id="testSelection" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Select Test
                </button>
                <div class="dropdown-menu" aria-labelledby="testSelection">
                    <?php foreach($graph_data as $key => $value): ?>
                        <button class="dropdown-item" type="button"><?php echo $key; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>


            <?php foreach($graph_data as $key => $value): ?>
            <div class="container-fluid pt-4">
            <div class="row">

                <div class="col-12">
                    <h3 class="text-dark-teal mb-4"><?php echo $key; ?> Results Over Time</h3>
                </div>

                <div class="col-12 col-md-8">

                    <div class="bubble bubble-border round-tl results-graph">
                    <div class="inner">
                    <?php

                        $chart_counter = 0;
                        foreach($graph_data as $k => $v){
                            echo '<div class="chart-container"><canvas id="canvas-'.$chart_counter.'"></canvas></div>';
                            $chartData = [
                                'id' => 'canvas-'.$chart_counter,
                                'labels' => $reverse_labels,
                                'data' => $pre_data,
                                'ymax' => $max_score
                            ];
                            $cdata = json_encode($chartData,JSON_NUMERIC_CHECK);
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
                                                        stepSize: <?php echo get_field('chart_steps', $screen_id); ?>
                                                    }
                                                }]
                                            }
                                        }
                                    });
                                }
                                loadLineChart(<?=$cdata?>);
                            </script>
                            <?php
                            $chart_counter++;
                        }
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
            <?php endforeach; ?>         

        </div>

        <!-- Overcoming Thoughts -->
        <div class="dashboard-block thought-activity pt-5 pb-5">
            <h2 class="bar">Overcoming Thoughts</h2>                
            <?php
                $args = array(
                    "author" => get_current_user_id(),
                    "post_type" => 'thought',
                    "orderby" => 'date',
                    "post_status" => array( 'draft', 'publish' ),
                    "order"	=> 'DESC',
                    "posts_per_page" => 100
                );
                $loop = new WP_Query($args);
                $loop_total = $loop->found_posts;
                $counter = 0;

                if($loop->have_posts()):
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
                        continue;
                    }  

                    if($counter == 3 && $loop_total > 3){
                        // Collapse thoughts if more than 5
                        echo '<div class="collapse" id="allThoughts">';
                    }
                ?>
                <div class="bubble round-small-bl thin relative gray mb-4">
                <div class="inner">
                    
                    <div claass="container-fluid">
                    <div class="row">
                        <div class="coll-12 col-md-9 monsterrat medium text-blue-dark pb-2 pb-md-0">
                            <?php echo $initial_thought; ?>
                        </div>
                        <div class="col-12 col-md-3">
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

                </div>
                </div>
                <?php 
                $counter++;
                endwhile; 
                else :?>
                    <p>You have no submitted thoughts.</p>
                    <p><a class="button blue round" href="/overcoming-thoughts">What thought are you struggling with right now?</a></p>
                <?php
                endif;
                if($counter > 5 && $loop_total > 5){
                    echo '</div>';
                    echo '<p class="text-right"><button class="button round-tl" type="button" data-toggle="collapse" data-target="#allThoughts" aria-expanded="false" aria-controls="allThoughts">View All Thoughts</button></p>';
                }
                wp_reset_query();
            ?>
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
        ?>


        <h2 class="mb-4">Saved Mental Health Information</h2>
        <?php
            global $wpdb;
            $uid = get_current_user_id();
            $liked_articles = [];
            $liked_resources = [];
            $resources = array('diy','connect','treatment','provider');
            $likes = $wpdb->get_results("SELECT pid FROM article_likes WHERE uid = $uid AND unliked = 0 ORDER BY id DESC");	

            foreach($likes as $like){
                $article_type = get_field('type', $like->pid);                    
                if(count(array_intersect($article_type, $resources)) > 0){
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

<?php
get_footer();