<?php
/**
 * Vars available: $vendors (array of vendor score rows, worst-first, see VendorHealth)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'Vendor Health', 'wc-store-doctor' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Computed from the store\'s existing scan data — run a scan from the Dashboard to refresh these numbers.', 'wc-store-doctor' ); ?>
	</p>

	<?php if ( empty( $vendors ) ) : ?>
		<p><?php esc_html_e( 'No vendors found.', 'wc-store-doctor' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Vendor', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Score', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Products', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Critical', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Warnings', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Suggestions', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Action', 'wc-store-doctor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $vendors as $vendor ) : ?>
					<tr>
						<td><?php echo esc_html( $vendor['name'] ); ?></td>
						<td><?php echo esc_html( $vendor['score'] ); ?>/100</td>
						<td><?php echo esc_html( $vendor['product_count'] ); ?></td>
						<td><?php echo esc_html( $vendor['critical'] ); ?></td>
						<td><?php echo esc_html( $vendor['warning'] ); ?></td>
						<td><?php echo esc_html( $vendor['suggestion'] ); ?></td>
						<td>
							<?php if ( $vendor['product_count'] > 0 ) : ?>
								<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&author=' . (int) $vendor['vendor_id'] ) ); ?>">
									<?php esc_html_e( 'View products', 'wc-store-doctor' ); ?>
								</a>
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
