<?php
/**
 * Thought Activity Container
 */

get_header();
?>

<div class="wrap narrow">

	<?php
		// General Vars
		$activity_id = get_the_ID();
		$uid = get_current_user_id();		
		$path_questions = get_field('paths');
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
					'value'		=> get_ipiden()
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

				$return_ref_1 = $path_questions[$last_path]['questions'][$last_question + 1]['reference'];
				$return_ref_2 = $path_questions[$last_path]['questions'][$last_question + 1]['additional_reference'];


			}
		}

		// Get thought history if coming back to this page unfinished
		

		// Debugging
		/*
		echo '<pre>';
		print_r($previous_responses);
		echo "<br />";
		print_r($path_questions);
		echo "<br />";		
		print_r('Last Response '.$last_response);
		echo '<br />';
		print_r('Last Path '.$last_path);
		echo '<br />';
		print_r('Last Question '.$last_question);
		echo '<br />';
		print_r('Last Admin Seed '.$last_admin_seed);
		echo '<br />';
		print_r('Last User Seed '.$last_user_seed);

		echo '<br />';
		print_r('Return Ref 1: '.$return_ref_1);
		echo '<br />';
		print_r('Return Ref 1: '.$return_ref_2);
		echo '</pre>';
		*/
		wp_reset_query();

		/**
		 * Begin Activity
		 */

		// Display start over button or not check
		$start_over_display = 'none';
		if($previous_responses){
			$start_over_display = 'block';
		}
	?>

	<div id="thought-history" class="bubble thin light-blue round-bl">
		<div class="inner" style="display: <?php echo $start_over_display; ?>;"></div>
	</div>
	<div id="start-over-container"style="display: <?php echo $start_over_display; ?>;">
		<p class="text-right ">
			<button id="start-over" class="plain" data-nonce="<?php echo wp_create_nonce('abandonThought'); ?>">Start New Thought &raquo;</button>
		</p>
	</div>

	<form action="<?php the_permalink(); ?>" method="POST" autocomplete="off" id="form-activity">
		
		<?php 
			/**
			 * Introduction
			 */
			if(!$unfinished_thought): // Hide introduction if returning with unfinished thought
				while ( have_posts() ) : the_post();
					get_template_part( 'templates/blocks/content', 'plain' );
				endwhile;
			endif;
		?>

		<div class="activity-response bubble light-blue round-bl mb-4<?php if($unfinished_thought){ echo '" style="display: none;'; }?>">
		<div class="inner">

			<div class="question-item form-item initial">
				<label><?php the_field('question'); ?></label>
				<p class="text-entry">
					<textarea name="thought_0" data-question="0" class="required" required placeholder="Your answer"><?php 
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
				<div class="text-actions">
					<button class="submit bar submit-initial-thought self-thought" value="0">Submit &raquo;</button>
				</div>
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
				<input type="hidden" name="started" value="<?php echo date('Y-m-d H:i:s'); ?>" />		
				<input type="hidden" name="pid" value="<?php echo $unfinished_thought; ?>" />
				<input type="hidden" name="admin_seed" value="<?php echo $last_admin_seed; ?>" />
				<input type="hidden" name="user_seed" value="<?php echo $last_user_seed; ?>" />
			</div>
		</div>
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
				echo '<h4 class="wow fadeInUp">Continue working with this thought</h4>';
				echo '<div class="path-selections">';
					while( have_rows('paths') ) : the_row();
						$path = get_sub_field('title');
						echo '<div class="path-selection bubble thin bubble-border round-small gray wow fadeInUp">';
						echo '<div class="inner text-dark-teal">';
							echo '<p>'.get_sub_field('description').'</p>';
							echo '<button class="submit bar submit-path" value="'.get_row_index().'">'.$path.' &raquo;</button><br />';
						echo '</div>';				
						echo '</div>';				
					endwhile;
				echo '</div>';
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
								echo '<button class="submit submit-initial-thought seed-admin round" value="'.get_row_index().'">'.$response.'</button> ';				
							endwhile;
						?>
					</div>
				<?php 
				endif; 
			?>
		</div>


		<div class="form-steps wow fadeIn">
			<?php
				/**
				 * Path to follow
				 */
				if( have_rows('paths') ):
					echo '<ol id="form-paths">';
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

								$row = get_row_index() + 1;
								$thought_name = 'thought_'.$path.'_'.$row;

								$display = 'none';
								$row_class = '';
								if($previous_responses){
									$previous_compare = 'thought_'.$last_path.'_'.($last_question + 1);
									if($thought_name == $previous_compare){
										$display = 'block';
										$row_class .= ' continue active ';
									}
								}
								/*
								echo 'Last Question '.$last_question.' / ';
								echo 'Row '.($row + 1).' / ';
								echo 'Max '.$max.'<br />';
								*/
								$last = '';
								if($max == $row){ 
									$row_class .= ' last'; 
									$last = 1;
								}
							?>
								<li 
									data-question="<?php echo $row; ?>" 
									data-reference="<?php echo get_sub_field('reference'); ?>" 
									data-additional-reference="<?php echo get_sub_field('additional_reference'); ?>" 
									class="question-item<?php echo $row_class; ?>" style="display: <?php echo $display; ?>;">

										<div><?php the_sub_field('introduction'); ?></div>

										<div class="bubble blue round-bl">
										<div class="inner">
										
											<p class="text-entry">
												<label for="<?php echo $thought_name; ?>">
													<?php the_sub_field('question'); ?>
												</label>
												<textarea name="<?php echo $thought_name; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" class="required" placeholder="Your answer" required><?php 
													if($previous_responses[$row]['response'] && $previous_responses[$row]['path'] == $path){
														echo $previous_responses[$row]['response'];
													}
												?></textarea>
												<span class="validation"></span>
											</p>
											<div class="text-actions">
												<?php if($last == ''): ?>
													<button class="submit bar submit-thought" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>">Submit &raquo;</button>
													<button class="submit bar continue-thought" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" style="display: none;">Continue &raquo;</button>
												<?php else: ?>
													<button class="submit bar submit-thought<?php echo $last_class; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>">Submit &raquo;</button>
													<button class="submit bar continue-thought<?php echo $last_class; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" style="display: none;">View summary &raquo;</button>
												<?php endif; ?>
												</div>

										</div>
										</div>
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
			<div id="thought-save">
				<?php if(!is_user_logged_in()): ?>
					<div class="bubble round blue thin mb-3">
					<div class="inner bold text-center">
						<a class="append-thought-id text-white" href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_thought_') ?>">Log in</a>
						or
						<a class="append-thought-id text-white" href="/sign-up/?action=save_thought_">register for an account</a>
						to save this thought to your account.
					</div>
					</div>
				<?php endif; ?>
			</div>
			<div id="thought-summary" class="mb-5"></div>
			<p class="text-right"><a class="button round" href="<?php the_permalink(); ?>?cb=<?php echo date('U'); ?>">Start a new thought &raquo;</a></p>
		</div>
	
	</form>

</div>


<div class="wrap narrow">
	
	<?php 
		$display_other_responses = 'block';

		// If returning on the path page, don't show
		if($unfinished_thought && $last_path == ''):
			$display_other_responses = 'none';
		endif;
	?>
	<div id="other-responses" style="display: <?php echo $display_other_responses; ?>">
		<h3 class="wow fadeIn blue">What Others Are Saying</h3>
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

<div id="temp-result-data" style="display: none;"></div>

<?php		
get_footer();
