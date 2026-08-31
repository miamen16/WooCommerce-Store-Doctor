<?php
/**
 * Vars available:
 *   $grouped (array of row objects: type, severity, scanner, total, fixable)
 *   $batches (array of row objects: batch_id, fixer, total, reverted_count, created_at)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$severity_labels = array(
	'critical'   => __( 'Critical', 'wc-store-doctor' ),
	'warning'    => __( 'Warning', 'wc-store-doctor' ),
	'suggestion' => __( 'Suggestion', 'wc-store-doctor' ),
);

$severity_icons = array(
	'critical'   => '🔴',
	'warning'    => '🟠',
	'suggestion' => '🟡',
);
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'Issue Center', 'wc-store-doctor' ); ?></h1>

	<?php if ( empty( $grouped ) ) : ?>
		<p><?php esc_html_e( 'No open issues found. Run a scan from the Dashboard to check your store.', 'wc-store-doctor' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Issue', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Products', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Severity', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Action', 'wc-store-doctor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $grouped as $row ) : ?>
					<tr>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $row->type ) ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&wcsd_type=' . rawurlencode( $row->type ) ) ); ?>">
								<?php echo esc_html( $row->total ); ?>
							</a>
						</td>
						<td>
							<?php
							$icon = isset( $severity_icons[ $row->severity ] ) ? $severity_icons[ $row->severity ] : '';
							echo esc_html( $icon . ' ' . ( $severity_labels[ $row->severity ] ?? $row->severity ) );
							?>
						</td>
						<td>
							<?php if ( ! empty( $row->fixable ) ) : ?>
								<button
									type="button"
									class="button button-primary wcsd-fix-button"
									data-issue-type="<?php echo esc_attr( $row->type ); ?>"
									data-issue-label="<?php echo esc_attr( ucwords( str_replace( '_', ' ', $row->type ) ) ); ?>"
								>
									<?php esc_html_e( 'Fix', 'wc-store-doctor' ); ?>
								</button>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Manual review', 'wc-store-doctor' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Recent Fixes', 'wc-store-doctor' ); ?></h2>
	<div class="wcsd-recent-fixes">
		<?php if ( empty( $batches ) ) : ?>
			<p><?php esc_html_e( 'No fixes applied yet.', 'wc-store-doctor' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Fixer', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Items', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wc-store-doctor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $batches as $batch ) : ?>
						<?php $fully_reverted = (int) $batch->reverted_count >= (int) $batch->total; ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $batch->created_at ) ) ); ?></td>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $batch->fixer ) ) ); ?></td>
							<td><?php echo esc_html( $batch->total ); ?></td>
							<td>
								<?php
								if ( $fully_reverted ) {
									esc_html_e( 'Reverted', 'wc-store-doctor' );
								} elseif ( (int) $batch->reverted_count > 0 ) {
									printf(
										/* translators: %d: number of items reverted */
										esc_html__( 'Partially reverted (%d)', 'wc-store-doctor' ),
										(int) $batch->reverted_count
									);
								} else {
									esc_html_e( 'Applied', 'wc-store-doctor' );
								}
								?>
							</td>
							<td>
								<?php if ( ! $fully_reverted ) : ?>
									<button type="button" class="button wcsd-revert-button" data-batch-id="<?php echo esc_attr( $batch->batch_id ); ?>">
										<?php esc_html_e( 'Revert', 'wc-store-doctor' ); ?>
									</button>
								<?php else : ?>
									<span class="description">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<div id="wcsd-fix-modal" class="wcsd-modal-overlay" style="display:none;">
	<div class="wcsd-modal">
		<h2 id="wcsd-fix-modal-title"></h2>
		<div id="wcsd-fix-modal-body">
			<p class="wcsd-modal-loading"><?php esc_html_e( 'Loading preview…', 'wc-store-doctor' ); ?></p>
		</div>
		<div class="wcsd-modal-actions">
			<button type="button" class="button" id="wcsd-fix-modal-cancel"><?php esc_html_e( 'Cancel', 'wc-store-doctor' ); ?></button>
			<button type="button" class="button button-primary" id="wcsd-fix-modal-apply" disabled><?php esc_html_e( 'Apply Changes', 'wc-store-doctor' ); ?></button>
		</div>
	</div>
</div>
