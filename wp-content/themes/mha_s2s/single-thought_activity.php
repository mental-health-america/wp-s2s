<?php
/**
 * Thought Activity Container
 */

get_header();

	// Introduction
	while ( have_posts() ) : the_post();
		get_template_part( 'templates/blocks/content', 'page' );
	endwhile;
	?>

	<div id="thought-history" class="alert alert-success" style="display: none;"></div>

	<form action="<?php the_permalink(); ?>" method="POST" autocomplete="off" id="form-activity">

		<div class="form-item initial">
			<label><?php the_field('question'); ?></label>
			<p><textarea name="thought_0" data-question="0" class="required"></textarea></p>
			<p><button class="submit submit-initial-thought user-thought" value="0">Submit</button></p>
		</div>

		<div class="form-actions">	
			<?php 			
				$source = '';
				if (get_query_var('source')) {
					$source = get_query_var('source');
				}
			?>	
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('thoughtSubmission'); ?>" />
			<input type="hidden" name="page" value="<?php echo get_the_ID(); ?>" />
			<input type="hidden" name="source" value="<?php echo $source; ?>" />
			<input type="hidden" name="uid" value="<?php get_current_user_id(); ?>" />			
			<input type="hidden" name="pid" value="" />			
			<input type="hidden" name="ipiden" value="<?php echo md5($_SERVER['REMOTE_ADDR']); ?>" />			
		</div>
		
		<?php
			// Path to follow
			if( have_rows('paths') ):
			echo '<div class="further-actions" style="display: none;"><h3>Continue working with this thought</h3>';
				while( have_rows('paths') ) : the_row();
					$path = get_sub_field('title');
					echo '<p><button class="submit submit-path" value="'.get_row_index().'">'.$path.'</button><br />'.get_sub_field('description').'</p>';				
				endwhile;
			echo '</div>';
			endif;
		?>

		<div class="form-item pre-seed">	
			<?php 
				// Pre Generated Responses
				if( have_rows('pre_generated_responses') ): 
				?>
					<p>
						<p><?php the_field('pre_generated_description'); ?></p>
						<?php			
							while( have_rows('pre_generated_responses') ) : the_row();
								$response = get_sub_field('response');
								echo '<button class="submit submit-initial-thought seed-manual" value="'.get_row_index().'">'.$response.'</button> ';				
							endwhile;
						?>
					</p>
				<?php 
				endif; 
			?>
		</div>


		<div class="form-steps">
			<?php
				// Path to follow
				if( have_rows('paths') ):
					echo '<ol id="form-paths" style="list-style: none;">';
					while( have_rows('paths') ) : the_row();	
						$path = get_row_index(); // Path being followed
						$max = count(get_sub_field('questions')); // Max questions for this path
						echo '<li>';
						if( have_rows('questions') ):
							echo '<ol class="path" data-path="'.$path.'"style="display: none;">';
							while( have_rows('questions') ) : the_row();	
							?>
								<li 
									data-question="<?php echo get_row_index(); ?>" 
									data-reference="<?php the_sub_field('reference'); ?>" 
									data-additional-reference="<?php the_sub_field('additional_reference'); ?>" 
									class="question-item<?php if(get_row_index() == $max){ echo ' last'; } ?>" style="display: none; list-style: none;">
										<div><?php the_sub_field('introduction'); ?></div>
										<label class="bold"><?php the_sub_field('question'); ?></label>
										<p><textarea name="thought_<?php echo $path; ?>_<?php echo get_row_index(); ?>" data-question="<?php echo get_row_index(); ?>" data-path="<?php echo $path; ?>" class="required"></textarea></p>
										<p><button class="submit submit-thought" data-question="<?php echo get_row_index(); ?>" data-path="<?php echo $path; ?>">Submit</button></p>
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
			<a href="<?php the_permalink(); ?>">Start over?</a>
		</div>

	</form>

	<?php
		$activity_id = get_the_ID();
		$admin_seeds = get_field('pre_generated_responses');
		$unique_admin_seeds = [];
		$unique_user_seeds = [];

		$args = array(
			"post_type" 	 => 'thought',
			"post_status" 	 => 'publish',
			"order"			 => 'DESC',
			"orderby" 		 => 'date',
			"posts_per_page" => 50,
			"meta_key" 		 => 'activity',
			"meta_value"     => $activity_id,
			/*
			'meta_query' 	 => array(
				'relation'   => 'AND',
				array(
					'key' 	 	=> 'activity',
					'value'  	=> $activity_id,
					'compare'	=> '='
				),
				array(
					'key'		=> 'responses_$_response', // Avoid empty entries (Yeah it's an edge case)
					'value'  	=> '',
					'compare'	=> '!='
				)
			),
			*/
		);
		$loop = new WP_Query($args);
		if($loop->have_posts()):
			echo '<ol id="thoughts-submitted">';
			while($loop->have_posts()) : $loop->the_post();

				$thoughts = get_field('responses');
				
				if($thoughts[0]['response']) { 

					// Entered response					
					echo '<li><span class="thought-text" data-pid="'.get_the_ID().'">'.$thoughts[0]['response'].'</span>'; 
					echo '<br /><button class="thought-like" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.get_the_ID().'" data-row="0">Like</button>';
					echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.get_the_ID().'" data-row="0">Flag</button>';
					echo ' <button class="submit submit-initial-thought seed-user submitted-thought" value="'.get_the_ID().'">Explore this thought</button></li>';

				} elseif($thoughts[0]['admin_pre_seeded_thought']) { 
					
					// Admin seeded response					
					if(in_array($thoughts[0]['admin_pre_seeded_thought'], $unique_admin_seeds)){
						continue;
					}
					echo '<li><span class="thought-text">'.$thoughts[0]['admin_pre_seeded_thought'].'</span>'; 
					echo '<br /><button class="thought-like" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$activity_id.'" data-row="'.$thoughts[0]['admin_pre_seeded_thought'].'">Like</button>';
					echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$activity_id.'" data-row="'.$thoughts[0]['admin_pre_seeded_thought'].'">Flag</button>';
					echo ' <button class="submit submit-initial-thought seed-manual submitted-thought" value="'.$thoughts[0]['admin_pre_seeded_thought'].'">Explore this thought</button></li>';

					$unique_admin_seeds[] = $thoughts[0]['admin_pre_seeded_thought'];
				
				} elseif($thoughts[0]['user_pre_seeded_thought']) { 
					
					// User seeded response					
					if(in_array($thoughts[0]['user_pre_seeded_thought'], $unique_user_seeds)){
						continue;
					}

					$user_thought_id = $thoughts[0]['user_pre_seeded_thought'];
					$user_thoughts = get_field('responses',$user_thought_id);

					echo '<li><span class="thought-text">'.$user_thoughts[0]['response'].'</span>'; 
					echo '<br /><button class="thought-like" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$user_thought_id.'" data-row="0">Like</button>';
					echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$user_thought_id.'" data-row="0">Flag</button>';
					echo ' <button class="submit submit-initial-thought seed-manual submitted-thought" value="'.$user_thought_id.'">Explore this thought</button></li>';

					$unique_user_seeds[] = $thoughts[0]['user_pre_seeded_thought'];

				}
			endwhile; 
			echo '</ol>';
		endif; 
	?>

	</div>

<?php		
get_footer();
