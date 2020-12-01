<?php
/**
 * Thought Activity Container
 */

get_header();

	// General Vars
	$activity_id = get_the_ID();
	$uid = get_current_user_id();
	if(!$uid){
		$uid = 4; // Default "Anonymous" User
	}

	// Get user's most recent incomplete thought
	$unfinished_thought = '';
    $args = array(
		"post_type" 	 => 'thought',
		"author"		 => $uid,
        "orderby" 		 => 'date', // Get the most recent
        "order"			 => 'DESC', // Get the most recent
        "post_status" 	 => 'draft', // Incomplete thoughts only
        "posts_per_page" => 1,
		"meta_query"	 => array(
			'relation'	 	=> 'AND',
			array(
				'key'		=> 'ipiden',
				'value'		=> md5($_SERVER['REMOTE_ADDR'])
			),
			array(
				'key'		=> 'abandoned',
				'compare'   => 'NOT EXISTS'
			)
		)
	);
	$loop = new WP_Query($args);
	while($loop->have_posts()) : $loop->the_post();
		$unfinished_thought = get_the_ID();
	endwhile;
	
	// Previous Response Vars
	$previous_responses = '';
	$last_response = 0;
	$last_path = '';
	$last_question = '';
	$last_admin_seed = '';
	$last_user_seed = '';
	if($unfinished_thought){
		if(get_field('responses', $unfinished_thought)){
			$previous_responses = get_field('responses', $unfinished_thought);
			$last_response = array_key_last($previous_responses);
			$last_path = $previous_responses[$last_response]['path'];
			$last_question = $previous_responses[$last_response]['question'];
			$last_admin_seed = $previous_responses[0]['admin_pre_seeded_thought'];
			$last_user_seed = $previous_responses[0]['user_pre_seeded_thought'];
		}
	}

	// Debugging
	/*
	echo '<pre>';
	print_r($previous_responses);
	echo "<br />";
	print_r('Response '.$last_response);
	echo '<br />';
	print_r('Path '.$last_path);
	echo '<br />';
	print_r('Question '.$last_question);
	echo '<br />';
	print_r('Admin Seed '.$last_admin_seed);
	echo '<br />';
	print_r('User Seed '.$last_user_seed);
	echo '</pre>';
	*/
	wp_reset_query();


	/**
	 * Introduction
	 */
	if(!$unfinished_thought): // Hide introduction if returning with unfinished thought
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/blocks/content', 'page' );
		endwhile;
	endif;


	/**
	 * Begin Activity
	 */

	// Display start over button or not check
	$start_over_display = 'none';
	if($previous_responses){
		$start_over_display = 'inline-block';
	}
?>

	<p><button id="start-over" style="display: <?php echo $start_over_display; ?>;" data-nonce="<?php echo wp_create_nonce('abandonThought'); ?>">Start New Thought</button></p>

	<div id="thought-history" class="alert alert-success" style="display: none;"></div>

	<form action="<?php the_permalink(); ?>" method="POST" autocomplete="off" id="form-activity">

		<div class="question-item form-item initial"<?php if($unfinished_thought){ echo ' style="display: none;"'; }?>>
			<label><?php the_field('question'); ?></label>
			<p>
				<textarea name="thought_0" data-question="0" class="required" required><?php 
					if($previous_responses[0]['response'] != ''){
						// Previously submitted thought
						echo $previous_responses[0]['response'];
					} else if(is_numeric($last_admin_seed)){
						// Admin seeded thought
						$initial_thought = get_field('pre_generated_responses', $activity_id);
						echo $initial_thought[$previous_responses[0]['admin_pre_seeded_thought']]['response'];
					} else if(is_numeric($last_user_seed)){
						// User seeded thought
						$initial_thought = get_field('responses', $last_user_seed);
						echo $initial_thought[0]['response'];
					}
				?></textarea>
				<div class="validation"></div>
			</p>
			<p><button class="submit submit-initial-thought self-thought" value="0">Submit</button></p>
		</div>

		<div class="form-actions">	
			<?php
				$source = '';
				if (get_query_var('source')) {
					$source = get_query_var('source');
				}
			?>	
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('thoughtSubmission'); ?>" />
			<input type="hidden" name="page" value="<?php echo $activity_id; ?>" />
			<input type="hidden" name="source" value="<?php echo $source; ?>" />		
			<input type="hidden" name="pid" value="<?php echo $unfinished_thought; ?>" />
			<input type="hidden" name="admin_seed" value="<?php echo $last_admin_seed; ?>" />
			<input type="hidden" name="user_seed" value="<?php echo $last_user_seed; ?>" />
		</div>
		
		<?php
			// Path to follow
			$further_action_display = 'none';
			$further_action_class = '';
			if($unfinished_thought && $last_path == ''):
				$further_action_display = 'block';
				$further_action_class = ' continue';
			endif;

			if( have_rows('paths') ):
			echo '<div class="further-actions'.$further_action_class.'" style="display: '.$further_action_display.';">';
				echo '<h3>Continue working with this thought</h3>';
				while( have_rows('paths') ) : the_row();
					$path = get_sub_field('title');
					echo '<p><button class="submit submit-path" value="'.get_row_index().'">'.$path.'</button><br />'.get_sub_field('description').'</p>';				
				endwhile;
			echo '</div>';
			endif;
		?>

		<div class="form-item pre-seed"<?php if($unfinished_thought){ echo ' style="display: none;"'; }?>>	
			<?php 
				// Pre Generated Responses
				if( have_rows('pre_generated_responses') ): 
				?>
					<div>
						<p><?php the_field('pre_generated_description'); ?></p>
						<?php			
							while( have_rows('pre_generated_responses') ) : the_row();
								$response = get_sub_field('response');
								echo '<button class="submit submit-initial-thought seed-admin" value="'.get_row_index().'">'.$response.'</button> ';				
							endwhile;
						?>
					</div>
				<?php 
				endif; 
			?>
		</div>


		<div class="form-steps">
			<?php
				/**
				 * Path to follow
				 */
				if( have_rows('paths') ):
					echo '<ol id="form-paths" style="list-style: none;">';
					while( have_rows('paths') ) : the_row();	
						$path = get_row_index(); // Path being followed
						$max = count(get_sub_field('questions')); // Max questions for this path
						
						echo '<li>';

						$display_path = 'none';
						$active_path = '';
						if($previous_responses){
							if($path == $last_path){
								$display_path = 'block';
								$active_path = ' active';
							}
						}

						/**
						 * Questions
						 */
						if( have_rows('questions') ):
							
							echo '<ol class="path'.$active_path.'" data-path="'.$path.'" style="display: '.$display_path.';">';

							while( have_rows('questions') ) : the_row();	

								$row = get_row_index();
								$thought_name = 'thought_'.$path.'_'.$row;

								$display = 'none';
								$row_class = '';
								if($previous_responses){
									$previous_compare = 'thought_'.$last_path.'_'.($last_question + 1);
									if($thought_name == $previous_compare){
										$display = 'block';
										$row_class .= ' continue ';
									}
								}
								
								$last_class = '';
								if($max == $row + 1){ 
									$last_class .= ' last'; 
								}
							?>
								<li 
									data-question="<?php echo $row; ?>" 
									data-reference="<?php the_sub_field('reference'); ?>" 
									data-additional-reference="<?php the_sub_field('additional_reference'); ?>" 
									class="question-item<?php echo $row_class.$last_class; ?>" style="display: <?php echo $display; ?>; list-style: none;">
										<div><?php the_sub_field('introduction'); ?></div>
										<label class="bold"><?php the_sub_field('question'); ?></label>
										<p>
											<textarea name="<?php echo $thought_name; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" class="required" required><?php 
												if($previous_responses[$row + 1]['response']){
													echo $previous_responses[$row + 1]['response'];
												}
											?></textarea>
											<div class="validation"></div>
										</p>
										<p>
											<?php if($last_class == ''): ?>
												<button class="submit submit-thought<?php echo $last_class; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>">Submit</button>
												<button class="submit continue-thought<?php echo $last_class; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" style="display: none;">Continue</button>
											<?php else: ?>
												<button class="submit complete-thought" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>">Complete</button>
											<?php endif; ?>
										</p>
								</li>
							<?php
							endwhile;
							echo '</ol>';
						endif;
						echo '</li>';
					endwhile;
					echo '</ol>';
				endif;
			?>
		</div>

		<div id="thought-end" style="display: none;">
			<hr />
				<div id="thought-summary"></div>
				<hr />
				<p><a href="<?php the_permalink(); ?>"><strong>Start over?</strong></a></p>
			<hr />
		</div>

	</form>
	
	<?php 
		$display_other_responses = 'block';

		// If returning on the path page, don't show
		if($unfinished_thought && $last_path == ''):
			$display_other_responses = 'none';
		endif;
	?>
	<div id="other-responses" style="display: <?php echo $display_other_responses; ?>">
		<h2>What Others Are Saying</h2>
		<ol id="thoughts-submitted">
			<?php 
				$return = null;
				if($previous_responses){
					$return = 1;
				}
				if($previous_responses && $last_response > 0){
					$last_response = $last_response + 1;
				}
				echo getThoughtsSubmitted( $activity_id, $last_response, $last_path, $last_admin_seed, $last_user_seed, $return ); 
			?>
		</ol>
	</div>

	</div>

<?php		
get_footer();
