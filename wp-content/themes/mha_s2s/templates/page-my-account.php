<?php 
/* Template Name: My Account */
get_header(); 
?>

<article id="my-account" <?php post_class(); ?>>

	<div class="page-heading bar">	
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
					<button class="button white plain caps p-0 hover-bar reveal-button" data-reveal="account-settings-form">Account Settings</button> | 
					<a class="button white plain caps p-0 hover-bar" href="<?php echo wp_logout_url( home_url() ); ?>">Log Out</a>
				</div>

				<div id="account-settings-form" class="form-container line-form">
					<?php echo do_shortcode('[gravityform id="3" title="false" description="false"]'); ?>
				</div>

			</div>
			</div>

		</div>
	</div>

	<div class="page-intro clear">
		<div class="wrap normal">

            <?php the_content(); ?>	


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
                    ?>
                    <div class="bubble round-small-bl thin relative gray mb-4">
                    <div class="inner">
                        
                        <div claass="container-fluid">
                        <div class="row">
                            <div class="col-9">
                                <?php echo $initial_thought; ?>
                            </div>
                            <div class="col-3">
                                <?php 
                                    if($status != 'publish'){
                                        echo 'Continue this thought &raquo;';
                                    } else {
                                        echo 'Review your submission &raquo;';
                                    }
                                ?>
                            </div>
                        </div>
                        </div>

                    </div>
                    </div>
                    <?php 
                        endwhile; 
                ?>
            </div>
            			
		</div>
    </div>
    

</article>

<?php
get_footer();