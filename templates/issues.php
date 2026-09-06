<?php
/**
 * Vars available:
 *   $grouped (array of row objects: type, severity, scanner, total, fixable)
 *   $batches (array of row objects: batch_id, fixer, total, reverted_count, created_at)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcsd_severity_labels = array(
	'critical'   => __( 'Critical', 'woocommerce-store-doctor' ),
	'warning'    => __( 'Warning', 'woocommerce-store-doctor' ),
	'suggestion' => __( 'Suggestion', 'woocommerce-store-doctor' ),
);

$wcsd_severity_icons = array(
	'critical'   => '🔴',
	'warning'    => '🟠',
	'suggestion' => '🟡',
);
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'Issue Center', 'woocommerce-store-doctor' ); ?></h1>

	<?php if ( empty( $grouped ) ) : ?>
		<p><?php esc_html_e( 'No open issues found. Run a scan from the Dashboard to check your store.', 'woocommerce-store-doctor' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Issue', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Products', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Severity', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Action', 'woocommerce-store-doctor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $grouped as $wcsd_row ) : ?>
					<tr>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $wcsd_row->type ) ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=product&wcsd_type=' . rawurlencode( $wcsd_row->type ) ), 'wcsd_product_filter', '_wcsd_filter_nonce' ) ); ?>">
								<?php echo esc_html( $wcsd_row->total ); ?>
							</a>
						</td>
						<td>
							<?php
							$wcsd_icon = isset( $wcsd_severity_icons[ $wcsd_row->severity ] ) ? $wcsd_severity_icons[ $wcsd_row->severity ] : '';
							echo esc_html( $wcsd_icon . ' ' . ( $wcsd_severity_labels[ $wcsd_row->severity ] ?? $wcsd_row->severity ) );
							?>
						</td>
						<td>
							<?php if ( ! empty( $wcsd_row->fixable ) ) : ?>
								<button
									type="button"
									class="button button-primary wcsd-fix-button"
									data-issue-type="<?php echo esc_attr( $wcsd_row->type ); ?>"
									data-issue-label="<?php echo esc_attr( ucwords( str_replace( '_', ' ', $wcsd_row->type ) ) ); ?>"
								>
									<?php esc_html_e( 'Fix', 'woocommerce-store-doctor' ); ?>
								</button>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'Manual review', 'woocommerce-store-doctor' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Recent Fixes', 'woocommerce-store-doctor' ); ?></h2>
	<div class="wcsd-recent-fixes">
		<?php if ( empty( $batches ) ) : ?>
			<p><?php esc_html_e( 'No fixes applied yet.', 'woocommerce-store-doctor' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'woocommerce-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Fixer', 'woocommerce-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Items', 'woocommerce-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Status', 'woocommerce-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Action', 'woocommerce-store-doctor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $batches as $wcsd_batch ) : ?>
						<?php $wcsd_fully_reverted = (int) $wcsd_batch->reverted_count >= (int) $wcsd_batch->total; ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $wcsd_batch->created_at ) ) ); ?></td>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $wcsd_batch->fixer ) ) ); ?></td>
							<td><?php echo esc_html( $wcsd_batch->total ); ?></td>
							<td>
								<?php
								if ( $wcsd_fully_reverted ) {
									esc_html_e( 'Reverted', 'woocommerce-store-doctor' );
								} elseif ( (int) $wcsd_batch->reverted_count > 0 ) {
									printf(
										/* translators: %d: number of items reverted */
										esc_html__( 'Partially reverted (%d)', 'woocommerce-store-doctor' ),
										(int) $wcsd_batch->reverted_count
									);
								} else {
									esc_html_e( 'Applied', 'woocommerce-store-doctor' );
								}
								?>
							</td>
							<td>
								<?php if ( ! $wcsd_fully_reverted ) : ?>
									<button type="button" class="button wcsd-revert-button" data-batch-id="<?php echo esc_attr( $wcsd_batch->batch_id ); ?>">
										<?php esc_html_e( 'Revert', 'woocommerce-store-doctor' ); ?>
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
			<p class="wcsd-modal-loading"><?php esc_html_e( 'Loading preview…', 'woocommerce-store-doctor' ); ?></p>
		</div>
		<div class="wcsd-modal-actions">
			<button type="button" class="button" id="wcsd-fix-modal-cancel"><?php esc_html_e( 'Cancel', 'woocommerce-store-doctor' ); ?></button>
			<button type="button" class="button button-primary" id="wcsd-fix-modal-apply" disabled><?php esc_html_e( 'Apply Changes', 'woocommerce-store-doctor' ); ?></button>
		</div>
	</div>
</div>
