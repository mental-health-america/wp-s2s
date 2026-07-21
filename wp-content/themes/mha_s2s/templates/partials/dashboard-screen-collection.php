<?php
/**
 * Organization dashboard: entries for screen collections that allow the user's organization,
 * where the Gravity Forms "SC Organization" field matches the org (display name or term name).
 *
 * @package mha_s2s
 */

if ( ! is_user_logged_in() ) {
	return;
}

$date_range = function_exists( 'mha_s2s_dashboard_resolve_org_date_range' )
	? mha_s2s_dashboard_resolve_org_date_range()
	: array(
		'start_date' => wp_date( 'Y' ) . '-01-01',
		'end_date'   => wp_date( 'Y' ) . '-12-31',
	);

$dash = function_exists( 'mha_s2s_dashboard_screen_collection_org_entries' )
	? mha_s2s_dashboard_screen_collection_org_entries( null, $date_range )
	: array(
		'ok'         => false,
		'message'    => 'unavailable',
		'rows'       => array(),
		'start_date' => $date_range['start_date'],
		'end_date'   => $date_range['end_date'],
	);

$dash_start = isset( $dash['start_date'] ) ? (string) $dash['start_date'] : $date_range['start_date'];
$dash_end   = isset( $dash['end_date'] ) ? (string) $dash['end_date'] : $date_range['end_date'];

if ( 'no_organization' !== $dash['message'] && get_field( 'view_organization_dashboard', 'user_' . get_current_user_id() ) ) :
	$show_aggregated = (bool) get_field( 'display_aggregated_data', 'user_' . get_current_user_id() );
	$export_mode     = $show_aggregated ? 'aggregated' : 'rows';
	$export_nonce    = wp_create_nonce( 'mha_org_dash_export' );
	?>
<div id="dashboard-screen-collection" class="pt-5 mt-5">
	
	<h2 class="pt-3 mb-4 heading"><?php esc_html_e( isset( $dash['term_name'] ) ? (string) $dash['term_name'].' Results Dashboard' : 'Results Dashboard', 'mhas2s' ); ?></h2>
	
	<form method="get" class="form-inline flex-wrap align-items-end mb-3 org-dashboard-date-filter" action="">
		<?php
		// Preserve other query vars on My Account if present (reset pagination on Apply).
		foreach ( $_GET as $qk => $qv ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $qk, array( 'org_dash_start', 'org_dash_end', 'org_dash_paged' ), true ) ) {
				continue;
			}
			if ( ! is_scalar( $qv ) ) {
				continue;
			}
			printf(
				'<input type="hidden" name="%s" value="%s" />',
				esc_attr( (string) $qk ),
				esc_attr( (string) $qv )
			);
		}
		?>
		<div class="form-group mr-3 mb-2">
			<label class="mr-2" for="org_dash_start"><?php esc_html_e( 'From', 'mhas2s' ); ?></label>
			<input type="date" class="form-control" id="org_dash_start" name="org_dash_start" value="<?php echo esc_attr( $dash_start ); ?>" />
		</div>
		<div class="form-group mr-3 mb-2">
			<label class="mr-2" for="org_dash_end"><?php esc_html_e( 'To', 'mhas2s' ); ?></label>
			<input type="date" class="form-control" id="org_dash_end" name="org_dash_end" value="<?php echo esc_attr( $dash_end ); ?>" />
		</div>
		<button type="submit" class="btn teal mb-2 round-bl bold"><?php esc_html_e( 'Apply', 'mhas2s' ); ?></button>
	</form>

	<?php if ( ! empty( $dash['message'] ) && 'unavailable' === $dash['message'] ) : ?>
		<p class="text-muted"><?php esc_html_e( 'This section is temporarily unavailable.', 'mhas2s' ); ?></p>
	<?php elseif ( ! empty( $dash['message'] ) && 'invalid_term' === $dash['message'] ) : ?>
		<p class="text-muted"><?php esc_html_e( 'Your assigned organization could not be loaded.', 'mhas2s' ); ?></p>
	<?php elseif ( ! empty( $dash['ok'] ) && ! empty( $dash['message'] ) && 'no_collections' === $dash['message'] ) : ?>
		<p class="mb-2">
			<?php
			printf(
				/* translators: %s: organization term name */
				esc_html__( 'No published screen collections list your organization (%s) under allowed organizations, or no matching forms expose an “SC Organization” field.', 'mhas2s' ),
				esc_html( $dash['term_name'] )
			);
			?>
		</p>
	<?php elseif ( ! empty( $dash['ok'] ) && empty( $dash['rows'] ) ) : ?>
		<p class="mb-2">
			<?php
			printf(
				/* translators: 1: organization term name, 2: start date, 3: end date */
				esc_html__( 'No form submissions were found where “SC Organization” matches your organization (%1$s) between %2$s and %3$s.', 'mhas2s' ),
				esc_html( $dash['term_name'] ),
				esc_html( $dash_start ),
				esc_html( $dash_end )
			);
			?>
		</p>
	<?php elseif ( ! empty( $dash['ok'] ) && ! empty( $dash['rows'] ) ) :
		$score_result_columns = isset( $dash['score_result_columns'] ) && is_array( $dash['score_result_columns'] )
			? $dash['score_result_columns']
			: array();
		$has_start_time_column = ! empty( $dash['has_start_time_column'] );

		if ( $show_aggregated && function_exists( 'mha_s2s_dashboard_aggregate_per_test_scores' ) ) :
			$agg = mha_s2s_dashboard_aggregate_per_test_scores( $dash );
			?>
		<div class="d-flex flex-wrap align-items-start justify-content-between mb-3 org-dashboard-toolbar">
			<p class="mb-2 text-muted mr-3">
				<?php
				printf(
					/* translators: 1: submission count, 2: start date, 3: end date */
					esc_html__( 'Showing %1$d submissions (%2$s to %3$s)', 'mhas2s' ),
					(int) $agg['submission_count'],
					esc_html( $dash_start ),
					esc_html( $dash_end )
				);
				?>
			</p>
			<div class="org-dashboard-export-wrap text-right mb-2">
				<button
					type="button"
					id="org-dash-export-csv"
					class="bar teal"
					data-mode="<?php echo esc_attr( $export_mode ); ?>"
					data-start="<?php echo esc_attr( $dash_start ); ?>"
					data-end="<?php echo esc_attr( $dash_end ); ?>"
					data-nonce="<?php echo esc_attr( $export_nonce ); ?>"
				><?php esc_html_e( 'Export CSV', 'mhas2s' ); ?></button>
				<div id="org-dash-export-progress" class="mt-2" style="display:none;">
					<div class="progress ml-auto" style="height: 8px; max-width: 320px;">
						<div id="org-dash-export-progress-bar" class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<span id="org-dash-export-progress-label" class="small text-muted ml-1">0%</span>
				</div>
				<div id="org-dash-export-download" class="mt-2 small" style="display:none;"></div>
				<div id="org-dash-export-error" class="mt-2 small text-danger" style="display:none;"></div>
			</div>
		</div>

		<div id="org-dashboard-aggregated" class="org-dashboard-aggregated">
			<div class="row">
				<?php foreach ( $agg['tests'] as $test ) : ?>
					<?php
					$test_id    = isset( $test['id'] ) ? (string) $test['id'] : '';
					$test_title = isset( $test['title'] ) ? (string) $test['title'] : '';
					$test_n     = isset( $test['n'] ) ? (int) $test['n'] : 0;
					$test_mean  = isset( $test['mean'] ) && null !== $test['mean'] ? $test['mean'] : null;
					$canvas_id  = 'org-agg-' . sanitize_html_class( $test_id );
					?>
					<div class="col-md-3 mb-4">
						<div class="org-agg-test-card h-100">
							<h3 class="h6 mb-1"><?php echo esc_html( $test_title ); ?></h3>
							<?php if ( $test_n > 0 && null !== $test_mean ) : ?>
								<p class="small text-muted mb-2">
									<?php
									printf(
										/* translators: %s: average score */
										esc_html__( 'Average: %s', 'mhas2s' ),
										esc_html( (string) $test_mean )
									);
									?>
								</p>
								<div class="position-relative" style="height: 180px;">
									<canvas id="<?php echo esc_attr( $canvas_id ); ?>" aria-label="<?php echo esc_attr( $test_title ); ?>"></canvas>
								</div>
							<?php else : ?>
								<p class="small text-muted mb-0"><?php esc_html_e( 'No score data in this range.', 'mhas2s' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<script>
		(function () {
			if (typeof Chart === 'undefined') {
				return;
			}
			var agg = <?php echo wp_json_encode( $agg ); ?>;
			var brandTeal = '#199aa0';
			var brandBlue = '#055596';
			var gridColor = '#aec7dc';
			var tickOpts = {
				fontFamily: 'Montserrat',
				fontColor: brandBlue,
				fontStyle: 'bold',
				fontSize: 10
			};
			(agg.tests || []).forEach(function (test) {
				if (!test || !test.n || !test.labels || !test.labels.length) {
					return;
				}
				var el = document.getElementById('org-agg-' + test.id);
				if (!el) {
					return;
				}
				new Chart(el.getContext('2d'), {
					type: 'bar',
					data: {
						labels: test.labels,
						datasets: [{
							label: 'Count',
							data: test.counts,
							backgroundColor: brandTeal,
							borderColor: brandTeal,
							borderWidth: 0
						}]
					},
					options: {
						title: { display: false },
						legend: { display: false },
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							xAxes: [{
								gridLines: { display: false, color: gridColor },
								ticks: tickOpts,
								scaleLabel: {
									display: true,
									labelString: 'Score',
									fontFamily: 'Montserrat',
									fontColor: brandBlue,
									fontSize: 10
								}
							}],
							yAxes: [{
								gridLines: { color: gridColor, drawBorder: false },
								ticks: Object.assign({ beginAtZero: true, precision: 0, padding: 6 }, tickOpts),
								scaleLabel: {
									display: true,
									labelString: 'Count',
									fontFamily: 'Montserrat',
									fontColor: brandBlue,
									fontSize: 10
								}
							}]
						}
					}
				});
			});
		})();
		</script>
		<?php else :
			$per_page   = 50;
			$total_rows = count( $dash['rows'] );
			$paged      = isset( $_GET['org_dash_paged'] ) ? max( 1, absint( $_GET['org_dash_paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$max_pages  = max( 1, (int) ceil( $total_rows / $per_page ) );
			if ( $paged > $max_pages ) {
				$paged = $max_pages;
			}
			$offset    = ( $paged - 1 ) * $per_page;
			$page_rows = array_slice( $dash['rows'], $offset, $per_page );
			$from_n    = $total_rows ? $offset + 1 : 0;
			$to_n      = min( $offset + $per_page, $total_rows );

			$pagination_base = remove_query_arg( 'org_dash_paged' );
			?>
		<div class="d-flex flex-wrap align-items-start justify-content-between mb-3 org-dashboard-toolbar">
			<p class="mb-2 text-muted mr-3">
				<?php
				printf(
					/* translators: 1: start index, 2: end index, 3: total, 4: start date, 5: end date */
					esc_html__( 'Showing %1$d–%2$d of %3$d submissions (%4$s to %5$s)', 'mhas2s' ),
					(int) $from_n,
					(int) $to_n,
					(int) $total_rows,
					esc_html( $dash_start ),
					esc_html( $dash_end )
				);
				?>
			</p>
			<div class="org-dashboard-export-wrap text-right mb-2">
				<button
					type="button"
					id="org-dash-export-csv"
					class="bar teal"
					data-mode="<?php echo esc_attr( $export_mode ); ?>"
					data-start="<?php echo esc_attr( $dash_start ); ?>"
					data-end="<?php echo esc_attr( $dash_end ); ?>"
					data-nonce="<?php echo esc_attr( $export_nonce ); ?>"
				><?php esc_html_e( 'Export CSV', 'mhas2s' ); ?></button>
				<div id="org-dash-export-progress" class="mt-2" style="display:none;">
					<div class="progress ml-auto" style="height: 8px; max-width: 320px;">
						<div id="org-dash-export-progress-bar" class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<span id="org-dash-export-progress-label" class="small text-muted ml-1">0%</span>
				</div>
				<div id="org-dash-export-download" class="mt-2 small" style="display:none;"></div>
				<div id="org-dash-export-error" class="mt-2 small text-danger" style="display:none;"></div>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-striped table-bordered w-100">
				<thead class="thead-light">
					<tr>
						<th scope="col"><?php esc_html_e( 'Submitted', 'mhas2s' ); ?></th>
						<?php if ( $has_start_time_column ) : ?>
							<th scope="col"><?php esc_html_e( 'Start Time', 'mhas2s' ); ?></th>
						<?php endif; ?>
						<th scope="col" class="org-dash-sc-user" style="max-width: 150px;"><?php esc_html_e( 'SC User', 'mhas2s' ); ?></th>
						<?php foreach ( $score_result_columns as $sr_heading ) : ?>
							<th scope="col"><?php echo esc_html( $sr_heading ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $page_rows as $row ) : ?>
						<?php
						$sr_cells = isset( $row['score_result'] ) && is_array( $row['score_result'] )
							? $row['score_result']
							: array();
						?>
						<tr>
							<td><?php echo esc_html( isset( $row['date_created_display'] ) ? (string) $row['date_created_display'] : '' ); ?></td>
							<?php if ( $has_start_time_column ) : ?>
								<td><?php echo esc_html( isset( $row['start_time_display'] ) ? (string) $row['start_time_display'] : '' ); ?></td>
							<?php endif; ?>
							<td class="org-dash-sc-user" title="<?php echo esc_attr( (string) $row['sc_user'] ); ?>">
								<span style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: bottom;"><?php echo esc_html( (string) $row['sc_user'] ); ?></span>
							</td>
							<?php foreach ( $score_result_columns as $sr_heading ) : ?>
								<td><?php echo esc_html( isset( $sr_cells[ $sr_heading ] ) ? (string) $sr_cells[ $sr_heading ] : '' ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( $max_pages > 1 ) : ?>
			<nav class="org-dashboard-pagination mt-3 d-flex align-items-center" aria-label="<?php esc_attr_e( 'Organization dashboard pagination', 'mhas2s' ); ?>">
				<?php if ( $paged > 1 ) : ?>
					<a class="button cerulean tiny round-bl mr-2" href="<?php echo esc_url( add_query_arg( 'org_dash_paged', $paged - 1, $pagination_base ) ); ?>"><?php esc_html_e( 'Previous', 'mhas2s' ); ?></a>
				<?php endif; ?>
				<span class="small text-muted mr-2">
					<?php
					printf(
						/* translators: 1: current page, 2: total pages */
						esc_html__( 'Page %1$d of %2$d', 'mhas2s' ),
						(int) $paged,
						(int) $max_pages
					);
					?>
				</span>
				<?php if ( $paged < $max_pages ) : ?>
					<a class="button cerulean tiny round-bl" href="<?php echo esc_url( add_query_arg( 'org_dash_paged', $paged + 1, $pagination_base ) ); ?>"><?php esc_html_e( 'Next', 'mhas2s' ); ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
		<?php endif; ?>
	<?php else : ?>
		<p class="text-muted"><?php esc_html_e( 'Unable to load organization dashboard data.', 'mhas2s' ); ?></p>
	<?php endif; ?>

</div>
<script>
(function ($) {
	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

	function setProgress(pct) {
		$('#org-dash-export-progress').show();
		$('#org-dash-export-progress-bar').css('width', pct + '%').attr('aria-valuenow', pct);
		$('#org-dash-export-progress-label').text(pct + '%');
	}

	function exportLoop(payload) {
		$.ajax({
			type: 'POST',
			url: ajaxUrl,
			data: payload,
			success: function (res) {
				if (!res || !res.success || !res.data) {
					var err = (res && res.data && res.data.error) ? res.data.error : 'Export failed.';
					$('#org-dash-export-error').text(err).show();
					$('#org-dash-export-csv').prop('disabled', false).text('Export CSV');
					return;
				}
				var data = res.data;
				setProgress(data.percent || 0);
				if (data.next_page) {
					payload.paged = data.next_page;
					payload.filename = data.filename;
					exportLoop(payload);
					return;
				}
				$('#org-dash-export-csv').prop('disabled', false).text('Export CSV');
				if (data.download) {
					$('#org-dash-export-download')
						.html('<strong>Download:</strong> <a download href="' + data.download + '">' + (data.filename || 'CSV') + '</a>')
						.show();
				}
			},
			error: function () {
				$('#org-dash-export-error').text('Export request failed.').show();
				$('#org-dash-export-csv').prop('disabled', false).text('Export CSV');
			}
		});
	}

	$(document).on('click', '#org-dash-export-csv', function () {
		var $btn = $(this);
		$('#org-dash-export-error').hide().text('');
		$('#org-dash-export-download').hide().empty();
		$btn.prop('disabled', true).text('Processing...');
		setProgress(0);
		exportLoop({
			action: 'mha_s2s_org_dashboard_export_csv',
			nonce: $btn.data('nonce'),
			mode: $btn.data('mode'),
			start_date: $btn.data('start'),
			end_date: $btn.data('end'),
			paged: 1
		});
	});
})(jQuery);
</script>
<?php endif; ?>
