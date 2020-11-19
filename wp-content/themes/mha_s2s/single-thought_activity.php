<?php
/**
 * Thought Activity Container
 */

get_header();

	// Introduction
	while ( have_posts() ) : the_post();
		get_template_part( 'templates/blocks/content', 'page' );
	endwhile;

	// General Vars
	$activity_id = get_the_ID();
	$uid = get_current_user_id();
	if(!$uid){
		$uid = 4;
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
				'key'		=> 'abandoned', // TODO: Update this in case "reopening" thoughts is ever a thing
				'compare'   => 'NOT EXISTS',
			)
		)
	);
	$loop = new WP_Query($args);
	while($loop->have_posts()) : $loop->the_post();
		$unfinished_thought = get_the_ID();
	endwhile;
	
	$previous_responses = '';
	$last_response = '';
	$last_path = '';
	$last_question = '';
	if($unfinished_thought){
		if(get_field('responses', $unfinished_thought)){
			$previous_responses = get_field('responses', $unfinished_thought);
			$last_response = array_key_last($previous_responses);
			$last_path = $previous_responses[$last_response]['path'];
			$last_question = $previous_responses[$last_response]['question'];
		}
	}

	// Debugging
	echo '<pre>';
	//print_r($previous_responses);
	print_r('Response '.$last_response);
	echo '<br />';
	print_r('Path '.$last_path);
	echo '<br />';
	print_r('Question '.$last_question);
	echo '</pre>';
	wp_reset_query();
?>

	<div id="thought-history" class="alert alert-success" style="display: none;"></div>

	<form action="<?php the_permalink(); ?>" method="POST" autocomplete="off" id="form-activity">

		<div class="question-item form-item initial"<?php if($unfinished_thought){ echo ' style="display: none;"'; }?>>
			<label><?php the_field('question'); ?></label>
			<p>
				<textarea name="thought_0" data-question="0" class="required"><?php 
					if($previous_responses[0]['response']){
						// Previously submitted thought
						echo $previous_responses[0]['response'];
					} else if($previous_responses[0]['admin_pre_seeded_thought']){
						// Admin seeded thought
						$initial_thought = get_field('pre_generated_responses', $activity_id);
						echo $initial_thought[$previous_responses[0]['admin_pre_seeded_thought']]['response'];
					} else if($previous_responses[0]['user_pre_seeded_thought']){
						// User seeded thought
						$initial_thought = get_field('responses', $previous_responses[0]['user_pre_seeded_thought']);
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
											<textarea name="<?php echo $thought_name; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>" class="required"><?php 
												if($previous_responses[$row + 1]['response']){
													echo $previous_responses[$row + 1]['response'];
												}
											?></textarea>
											<div class="validation"></div>
										</p>
										<p><button class="submit submit-thought<?php echo $last_class; ?>" data-question="<?php echo $row; ?>" data-path="<?php echo $path; ?>">Submit</button></p>
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
	
	<ol id="thoughts-submitted">
		<?php echo getThoughtsSubmitted(); ?>
	</ol>

	</div>

<?php		
get_footer();
