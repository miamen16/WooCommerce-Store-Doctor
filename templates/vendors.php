<?php
/**
 * Vars available: $vendors (array of vendor score rows, worst-first, see VendorHealth)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'Vendor Health', 'woocommerce-store-doctor' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Computed from the store\'s existing scan data — run a scan from the Dashboard to refresh these numbers.', 'woocommerce-store-doctor' ); ?>
	</p>

	<?php if ( empty( $vendors ) ) : ?>
		<p><?php esc_html_e( 'No vendors found.', 'woocommerce-store-doctor' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Vendor', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Score', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Products', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Critical', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Warnings', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Suggestions', 'woocommerce-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Action', 'woocommerce-store-doctor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $vendors as $wcsd_vendor ) : ?>
					<tr>
						<td><?php echo esc_html( $wcsd_vendor['name'] ); ?></td>
						<td><?php echo esc_html( $wcsd_vendor['score'] ); ?>/100</td>
						<td><?php echo esc_html( $wcsd_vendor['product_count'] ); ?></td>
						<td><?php echo esc_html( $wcsd_vendor['critical'] ); ?></td>
						<td><?php echo esc_html( $wcsd_vendor['warning'] ); ?></td>
						<td><?php echo esc_html( $wcsd_vendor['suggestion'] ); ?></td>
						<td>
							<?php if ( $wcsd_vendor['product_count'] > 0 ) : ?>
								<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&author=' . (int) $wcsd_vendor['vendor_id'] ) ); ?>">
									<?php esc_html_e( 'View products', 'woocommerce-store-doctor' ); ?>
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