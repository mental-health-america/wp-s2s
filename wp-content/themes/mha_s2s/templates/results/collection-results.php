<?php
/**
 * Screen collection results layout.
 *
 * Expected $args:
 * - collection_id (int)
 * - module_results (array from mha_get_collection_module_results)
 * - settings (array from mha_get_collection_results_settings)
 * - user_screen_result (array)
 * - user_screen_id (string)
 * - iframe_var (mixed)
 *
 * @package mha_s2s
 */

$collection_id       = isset( $args['collection_id'] ) ? absint( $args['collection_id'] ) : 0;
$module_results      = isset( $args['module_results'] ) && is_array( $args['module_results'] ) ? $args['module_results'] : array();
$settings            = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
$user_screen_result  = isset( $args['user_screen_result'] ) && is_array( $args['user_screen_result'] ) ? $args['user_screen_result'] : array();
$user_screen_id      = isset( $args['user_screen_id'] ) ? $args['user_screen_id'] : '';
$iframe_var          = isset( $args['iframe_var'] ) ? $args['iframe_var'] : null;

$modules        = isset( $module_results['modules'] ) ? $module_results['modules'] : array();
$positive       = isset( $module_results['positive'] ) ? $module_results['positive'] : array();
$negative       = isset( $module_results['negative'] ) ? $module_results['negative'] : array();
$recommended    = isset( $module_results['recommended'] ) ? $module_results['recommended'] : array();
$default_index  = isset( $module_results['default_module_index'] ) ? (int) $module_results['default_module_index'] : 0;

$positive_prefix = isset( $settings['positive_summary_prefix'] ) ? $settings['positive_summary_prefix'] : '';
$negative_prefix = isset( $settings['negative_summary_prefix'] ) ? $settings['negative_summary_prefix'] : '';
$empty_positive  = isset( $settings['empty_positive_message'] ) ? $settings['empty_positive_message'] : '';
$share_message   = isset( $settings['share_results_message'] ) ? $settings['share_results_message'] : '';
$rec_heading     = isset( $settings['recommended_screens_heading'] ) ? $settings['recommended_screens_heading'] : 'Screens to take next';
$show_rec        = ! empty( $settings['show_recommended_screens'] );
$resources       = isset( $settings['results_resources'] ) && is_array( $settings['results_resources'] ) ? $settings['results_resources'] : array();

$screen_title = ! empty( $user_screen_result['screen_id'] ) ? get_the_title( $user_screen_result['screen_id'] ) : '';
$link_target  = $iframe_var ? ' target="_blank" rel="noopener noreferrer"' : '';
?>

<div class="wrap narrow">
<article class="screen screen-result screen-collection-result">

	<div class="bubble thin teal round-small-bl mb-4">
		<div class="inner">
			<h1 class="subtitle thin montserrat block pb-1">
				Your Results &mdash; <span id="screen-name"><?php echo esc_html( $screen_title ); ?></span>
			</h1>
			<?php if ( ! empty( $positive ) ) : ?>
				<div class="collection-keyword-summary white">
					<p class="mb-2"><?php echo esc_html( $positive_prefix ); ?></p>
					<ul class="mb-0 pl-3">
						<?php foreach ( $positive as $mod ) : ?>
							<li><?php echo esc_html( $mod['symptom_label'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php else : ?>
				<p class="white mb-0"><?php echo esc_html( $empty_positive ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $negative ) ) : ?>
		<div class="mb-4">
			<button
				id="collection-negative-toggle"
				class="button mint round thin"
				type="button"
				data-toggle="collapse"
				data-target="#collection-negative-summary"
				aria-expanded="false"
				aria-controls="collection-negative-summary"
			>
				Things you don't seem to be struggling with (<?php echo esc_html( (string) count( $negative ) ); ?>)
			</button>
			<div class="bubble thick light-teal bubble-border round-tl montserrat mt-3 collapse anchor-content" id="collection-negative-summary">
				<div class="inner small">
					<p class="mb-2"><?php echo esc_html( $negative_prefix ); ?></p>
					<ul class="mb-0 pl-3">
						<?php foreach ( $negative as $mod ) : ?>
							<li><?php echo esc_html( $mod['symptom_label'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div id="screen-result-buttons" class="button-grid pt-3 pb-3 pl-0 pr-0 pl-md-5 pr-md-5">
		<button id="screen-answers" class="button mint round thin" type="button" data-toggle="collapse" data-target="#your-answers" aria-expanded="false" aria-controls="your-answers">
			<?php esc_html_e( 'Your Answers', 'mha_s2s' ); ?>
		</button>
		<?php
		get_template_part(
			'templates/results/action',
			'email_button',
			array( 'espanol' => false )
		);
		?>
	</div>

	<div id="screen-result-content" class="pt-4">
		<?php
		get_template_part(
			'templates/results/action',
			'email_display',
			array(
				'width'          => 'normal',
				'show'           => 0,
				'screen_id'      => isset( $user_screen_result['screen_id'] ) ? $user_screen_result['screen_id'] : '',
				'user_screen_id' => $user_screen_id,
				'espanol'        => false,
				'entry_id'       => isset( $user_screen_result['result_id'] ) ? $user_screen_result['result_id'] : '',
			)
		);
		?>

		<div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="your-answers">
			<div class="inner small">
				<div class="container-fluid p-0 noto">
					<h3 class="section-title dark-teal mb-4"><?php esc_html_e( 'Your Answers', 'mha_s2s' ); ?></h3>

					<?php if ( ! empty( $modules ) ) : ?>
						<div class="collection-module-answers" data-default-index="<?php echo esc_attr( (string) $default_index ); ?>">
							<label class="d-md-none mb-2 bold" for="collection-module-select">Module</label>
							<select id="collection-module-select" class="form-control mb-3 d-md-none" aria-label="Select module">
								<?php foreach ( $modules as $i => $mod ) : ?>
									<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $i, $default_index ); ?>>
										<?php echo esc_html( $mod['module_label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>

							<ul class="nav nav-pills flex-wrap mb-3 d-none d-md-flex collection-module-tabs" role="tablist">
								<?php foreach ( $modules as $i => $mod ) : ?>
									<li class="nav-item mb-2 mr-2" role="presentation">
										<button
											type="button"
											class="button round thin collection-module-tab <?php echo $i === $default_index ? 'teal' : 'mint'; ?>"
											data-module-index="<?php echo esc_attr( (string) $i ); ?>"
											aria-selected="<?php echo $i === $default_index ? 'true' : 'false'; ?>"
										>
											<?php echo esc_html( $mod['module_label'] ); ?>
										</button>
									</li>
								<?php endforeach; ?>
							</ul>

							<?php foreach ( $modules as $i => $mod ) : ?>
								<div
									class="collection-module-panel"
									data-module-index="<?php echo esc_attr( (string) $i ); ?>"
									<?php echo $i === $default_index ? '' : ' hidden'; ?>
								>
									<?php
									if ( ! empty( $mod['your_answers_html'] ) ) {
										echo $mod['your_answers_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html in plugin helper.
									} else {
										echo '<p class="text-gray mb-0">No answers recorded for this module.</p>';
									}
									?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="text-gray mb-0">No module answers are available.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

</article>
</div>

<div class="wrap normal next-steps-container">
	<div class="wrap narrow">
		<h2 class="section-title dark-blue bold"><?php esc_html_e( 'Next Steps', 'mha_s2s' ); ?></h2>

		<?php if ( $share_message !== '' ) : ?>
			<div class="bubble thick light-blue bubble-border round-tl montserrat mb-4">
				<div class="inner small">
					<p class="mb-0"><?php echo esc_html( $share_message ); ?></p>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $show_rec && ! empty( $recommended ) ) : ?>
			<h3 class="section-title cerulean small bold mb-3"><?php echo esc_html( $rec_heading ); ?></h3>
			<ul class="collection-recommended-screens mb-5 pl-3">
				<?php foreach ( $recommended as $mod ) : ?>
					<?php
					$screen_id = absint( $mod['recommended_screen'] );
					$url       = $screen_id ? get_permalink( $screen_id ) : '';
					if ( ! $url ) {
						continue;
					}
					?>
					<li class="mb-2">
						<a class="dark-blue bold" href="<?php echo esc_url( $url ); ?>"<?php echo $link_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php echo esc_html( $mod['module_label'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $resources ) ) : ?>
			<h3 class="section-title cerulean small bold mb-3"><?php esc_html_e( 'Articles and resources', 'mha_s2s' ); ?></h3>
			<ul class="collection-results-resources mb-5 pl-0 list-unstyled">
				<?php foreach ( $resources as $resource_id ) : ?>
					<?php
					$resource_id = absint( $resource_id );
					if ( ! $resource_id || 'publish' !== get_post_status( $resource_id ) ) {
						continue;
					}
					$url   = get_permalink( $resource_id );
					$title = get_the_title( $resource_id );
					if ( ! $url || '' === $title ) {
						continue;
					}
					?>
					<li class="mb-3">
						<a class="dark-blue bold" href="<?php echo esc_url( $url ); ?>"<?php echo $link_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php echo esc_html( $title ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
