<?php
/**
 * Screen Collection Template
 */

get_header();
$layout = get_layout_array(get_query_var('layout')); // Used for A/B testing
$wrap_width = get_field('page_content_width') ? get_field('page_content_width') : 'normal';

$screen_collection_id = get_the_ID();
$org = get_query_var('org') ? get_query_var('org') : false;
$allowed_orgs = get_field('allowed_organizations');
$org_display = '';
$org_id = '';

$org_approved = false;
$form_id = function_exists( 'mha_screen_collection_get_form_id' )
	? mha_screen_collection_get_form_id( $screen_collection_id )
	: absint( get_field( 'form' ) );
$screen_order = get_field('force_screen_order');
$require_user_id = get_field('ask_for_user_id');
$disable_prescreen = (bool) get_field('disable_prescreen');

$referrer =  get_query_var('ref');
$iframe_mode = get_query_var('iframe');

$taking_form = false;
if ( $form_id && function_exists( 'mha_screen_collection_get_sc_request_context' ) ) {
	$sc_ctx = mha_screen_collection_get_sc_request_context();
	if ( $sc_ctx && (int) $sc_ctx['collection_id'] === (int) $screen_collection_id && (int) $sc_ctx['form_id'] === (int) $form_id ) {
		$taking_form = true;
	}
}
if ( ! $taking_form && $form_id && ! empty( $_POST['gform_submit'] ) && (int) $_POST['gform_submit'] === (int) $form_id ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$taking_form = true;
}
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if(in_array('screen_header_v1', $layout)): ?>
			<div class="wrap normal">
				<div class="page-heading plain">			
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</div>
			</div>
		<?php else: ?>
			<div class="page-heading mint bar">	
			<div class="wrap <?php echo $wrap_width; ?>">		
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>			
			</div>
			</div>
		<?php endif; ?>

		<div class="wrap normal">

			<?php if ( $taking_form && $form_id ) : ?>
				<div class="page-intro">
					<?php
					echo do_shortcode(
						sprintf(
							'[gravityform id="%d" title="false" description="false" ajax="false"]',
							(int) $form_id
						)
					);
					?>
				</div>
			<?php else : ?>

			<div id="screen-collection-orgs">
				<?php 
					foreach($allowed_orgs as $organization):
						if($organization['organization_id'] == $org): 
							$org_display = $organization['organization_display_name'];
							$org_id = $organization['organization_id'];						
							$org_approved = true;
							break;	
						endif; 
				 	endforeach;
					
					if(!$org):
						echo '<p class="mb-0">This tool is only available for approved organizations.</p>';
					elseif($org && !$org_approved):
						echo '<p class="mb-0">This organization is not approved to access this collection.</p>';
					else:
					?>

						<div class="page-intro">
							<?php the_content(); ?>	
							<hr />
							<p class="mb-0"><strong>Organization:</strong> <?php echo $org_display; ?></p>
						</div>
						
					<?php
					endif;
				 ?>
			</div>

			<?php if($org_approved): ?>
				<div class="spinner-border my-2" role="status">
					<span class="sr-only">Loading...</span>
				</div>
				<form action="<?php echo get_the_permalink(); ?>" method="post" class="screen-collection-user form-container loading d-none">
					<div class="row g-0">
						<div class="col-auto pr-0">
						<label class="form-label" for="user_id">User ID:</label>
							<input type="text" name="user_id" id="user_id" class="round-tr form-control" placeholder="" required value="" />
						</div>
						<div class="col-auto">
							<label class="form-label" for="submit">&nbsp;</label>
							<button type="submit" class="round-bl">Submit</button>
						</div>
					</div>
					<small id="emailHelp" class="form-text text-muted mb-4">Please provide your user ID to access this collection.</small>
					<input type="hidden" name="org" value="<?php echo $org_display; ?>" />
					<input type="hidden" name="org_id" value="<?php echo $org_id; ?>" />
					<input type="hidden" name="screen_collection" value="<?php echo $screen_collection_id; ?>" />
				</form>

				<?php
				if ( $form_id ) :
					?>
					<div id="screen-collection-prescreens-wrap" class="screen-collection-prescreens-wrap mb-4 d-none" <?php echo $disable_prescreen ? 'data-prescreen="off" ' : ''; ?>aria-hidden="true">
						<?php if ( ! $disable_prescreen ) : ?>
						<button type="button" class="button round screen-collection-prescreens-start d-none mb-3" aria-expanded="false" aria-controls="screen-collection-prescreens-inner">
							<?php esc_html_e( 'Start prescreen', 'mha_s2s' ); ?>
						</button>
						<?php endif; ?>
						<div id="screen-collection-prescreens-inner" class="screen-collection-prescreens-inner d-none">
							<div class="screen-collection-prescreens">
								<?php
								if ( $disable_prescreen ) :
									$start_url = function_exists( 'mha_screen_collection_form_start_url' )
										? mha_screen_collection_form_start_url( $screen_collection_id, $org_id, $referrer, (bool) $iframe_mode, 1 )
										: '';
									if ( $start_url ) :
										?>
										<p class="screen-collection-start mb-3">
											<a class="button round-tr" href="<?php echo esc_url( $start_url ); ?>">
												<?php
												echo esc_html( sprintf( __( 'Begin %s', 'mha_s2s' ), get_the_title( $screen_collection_id ) ) );
												?>
											</a>
										</p>
										<?php
									endif;
								else :
									echo do_shortcode(
										sprintf(
											'[screen_collection_prescreen form="%d" collection_id="%d" org_id="%s" referrer="%s" iframe_mode="%s"]',
											(int) $form_id,
											(int) $screen_collection_id,
											esc_attr( $org_id ),
											esc_attr( $referrer ),
											$iframe_mode ? 'true' : 'false'
										)
									);
								endif;
								?>
							</div>
						</div>
					</div>
					<?php
				endif;
				endif;
			endif;
			?>
		</div>
		

	</article>

<?php
get_footer();
