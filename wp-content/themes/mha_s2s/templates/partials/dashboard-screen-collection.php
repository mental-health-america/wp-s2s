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

$dash = function_exists( 'mha_s2s_dashboard_screen_collection_org_entries' )
	? mha_s2s_dashboard_screen_collection_org_entries()
	: array(
		'ok'      => false,
		'message' => 'unavailable',
		'rows'    => array(),
	);

if ( 'no_organization' !== $dash['message'] && get_field('view_organization_dashboard', 'user_' . get_current_user_id()) ) :
?>
<div id="dashboard-screen-collection" class="pt-5 mt-5">

	<h2 class="pt-3 mb-4 heading"><?php esc_html_e( 'Organization dashboard', 'mhas2s' ); ?></h2>

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
				/* translators: %s: organization term name */
				esc_html__( 'No form submissions were found where “SC Organization” matches your organization (%s).', 'mhas2s' ),
				esc_html( $dash['term_name'] )
			);
			?>
		</p>
	<?php elseif ( ! empty( $dash['ok'] ) && ! empty( $dash['rows'] ) ) :
		$score_result_columns = isset( $dash['score_result_columns'] ) && is_array( $dash['score_result_columns'] )
			? $dash['score_result_columns']
			: array();
		$has_start_time_column = ! empty( $dash['has_start_time_column'] );
		?>
		<p class="mb-3 text-muted">
			<?php
			printf(
				/* translators: %s: organization term name */
				esc_html__( 'Showing submissions for organization: %s', 'mhas2s' ),
				esc_html( $dash['term_name'] )
			);
			?>
		</p>
		<div class="table-responsive">
			<table class="table table-striped table-bordered w-100">
				<thead class="thead-light">
					<tr>
						<th scope="col"><?php esc_html_e( 'Submitted', 'mhas2s' ); ?></th>
						<?php if ( $has_start_time_column ) : ?>
							<th scope="col"><?php esc_html_e( 'Start Time', 'mhas2s' ); ?></th>
						<?php endif; ?>
						<th scope="col"><?php esc_html_e( 'SC User', 'mhas2s' ); ?></th>
						<?php foreach ( $score_result_columns as $sr_heading ) : ?>
							<th scope="col"><?php echo esc_html( $sr_heading ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $dash['rows'] as $row ) : ?>
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
							<td><?php echo esc_html( (string) $row['sc_user'] ); ?></td>
							<?php foreach ( $score_result_columns as $sr_heading ) : ?>
								<td><?php echo esc_html( isset( $sr_cells[ $sr_heading ] ) ? (string) $sr_cells[ $sr_heading ] : '' ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<p class="text-muted"><?php esc_html_e( 'Unable to load organization dashboard data.', 'mhas2s' ); ?></p>
	<?php endif; ?>

</div>
<?php endif; ?>