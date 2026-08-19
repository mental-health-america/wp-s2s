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
$screens = get_field( 'screens' );
if ( is_array( $screens ) ) {
	$screens = array_map(
		static function ( $s ) {
			if ( is_object( $s ) && isset( $s->ID ) ) {
				return (int) $s->ID;
			}
			return absint( $s );
		},
		$screens
	);
	$screens = array_values( array_filter( $screens ) );
} elseif ( is_object( $screens ) && isset( $screens->ID ) ) {
	$screens = array( (int) $screens->ID );
} elseif ( $screens !== null && $screens !== '' && false !== $screens ) {
	$screens = array( absint( $screens ) );
} else {
	$screens = array();
}
$screen_order = get_field('force_screen_order');
$require_user_id = get_field('ask_for_user_id');

$referrer =  get_query_var('ref');
$iframe_mode = get_query_var('iframe');
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
					<input type="hidden" name="screen_ids" value="<?php echo implode(',', $screens); ?>" />
					<input type="hidden" name="screen_collection" value="<?php echo $screen_collection_id; ?>" />
				</form>

				<?php
				if ( ! empty( $screens ) && is_array( $screens ) ) :
					?>
					<div id="screen-collection-prescreens-wrap" class="screen-collection-prescreens-wrap mb-4 d-none" aria-hidden="true">
						<button type="button" class="button round screen-collection-prescreens-start d-none mb-3" aria-expanded="false" aria-controls="screen-collection-prescreens-inner">
							<?php esc_html_e( 'Start prescreen', 'mha_s2s' ); ?>
						</button>
						<div id="screen-collection-prescreens-inner" class="screen-collection-prescreens-inner d-none">
							<div class="screen-collection-prescreens">
								<?php
								foreach ( $screens as $prescreen_screen_id ) :
									$prescreen_screen_id = absint( $prescreen_screen_id );
									if ( ! $prescreen_screen_id ) {
										continue;
									}
									echo do_shortcode(
										sprintf(
											'[screen_collection_prescreen screen="%d" org_id="%s" referrer="%s" iframe_mode="%s"]',
											$prescreen_screen_id,
											esc_attr( $org_id ),
											esc_attr( $referrer ),
											$iframe_mode ? 'true' : 'false'
										)
									);
								endforeach;
								?>
							</div>
						</div>
					</div>
					<?php
				endif;

				// Get user_id from POST or set empty (will be set via JavaScript if needed)
				$user_id_param = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';

				// Use shortcode to render screenings list
				// echo do_shortcode('[screen_collection_list screens="'.implode(',', $screens).'" screen_order="'.($screen_order ? 'true' : 'false').'" org_id="'.$org_id.'" user_id="'.$user_id_param.'" referrer="'.$referrer.'" iframe_mode="'.($iframe_mode ? 'true' : 'false').'"]');
				endif;
			?>
		</div>
		

	</article>

<?php
get_footer();
