<?php
/**
 * Rendered inside Dokan's dashboard wrapper at /dashboard/store-health/.
 * Dokan itself provides the surrounding <div class="dokan-dashboard-wrap">
 * and sidebar nav — this file only needs to output the inner content, the
 * same way Dokan's own dashboard/*.php templates do.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcsd_plugin = \WCSD\Core\Plugin::instance();
$wcsd_report = $wcsd_plugin->vendor_health->get_vendor_report( get_current_user_id() );

$wcsd_overall = $wcsd_report['overall'];

$wcsd_health_label = __( 'Excellent Health', 'store-doctor-for-woocommerce' );
if ( $wcsd_overall['score'] < 50 ) {
	$wcsd_health_label = __( 'Needs Attention', 'store-doctor-for-woocommerce' );
} elseif ( $wcsd_overall['score'] < 75 ) {
	$wcsd_health_label = __( 'Good Health', 'store-doctor-for-woocommerce' );
}

dokan_get_template_part( 'global/dashboard-header', '', array(
	'header_title' => __( 'Store Health', 'store-doctor-for-woocommerce' ),
	'description'  => __( "See how your listings are doing and what's worth fixing.", 'store-doctor-for-woocommerce' ),
) );
?>

<div class="dokan-dashboard-content wcsd-vendor-health">

	<div class="wcsd-score-card">
		<div class="wcsd-score-circle" data-score="<?php echo esc_attr( $wcsd_overall['score'] ); ?>">
			<span class="wcsd-score-number"><?php echo esc_html( $wcsd_overall['score'] ); ?></span>
			<span class="wcsd-score-max">/ 100</span>
		</div>
		<div class="wcsd-score-meta">
			<p class="wcsd-score-label"><?php echo esc_html( $wcsd_health_label ); ?></p>
			<p class="wcsd-score-last-scan">
				<?php
				printf(
					/* translators: %d: number of products */
					esc_html__( 'Based on %d published product(s).', 'store-doctor-for-woocommerce' ),
					(int) $wcsd_overall['product_count']
				);
				?>
			</p>
		</div>
	</div>

	<?php if ( ! empty( $wcsd_report['categories'] ) ) : ?>
		<h3><?php esc_html_e( 'Breakdown', 'store-doctor-for-woocommerce' ); ?></h3>
		<div class="wcsd-categories">
			<?php foreach ( $wcsd_report['categories'] as $wcsd_cat ) : ?>
				<div class="wcsd-category-row">
					<span class="wcsd-category-label"><?php echo esc_html( $wcsd_cat['label'] ); ?></span>
					<div class="wcsd-category-bar">
						<div class="wcsd-category-bar-fill" style="width: <?php echo esc_attr( $wcsd_cat['score'] ); ?>%;"></div>
					</div>
					<span class="wcsd-category-score"><?php echo esc_html( $wcsd_cat['score'] ); ?>/100</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Things worth fixing', 'store-doctor-for-woocommerce' ); ?></h3>
	<?php if ( empty( $wcsd_report['issues'] ) ) : ?>
		<p><?php esc_html_e( 'No open issues on your listings right now. Nice work!', 'store-doctor-for-woocommerce' ); ?></p>
	<?php else : ?>
		<table class="dokan-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'store-doctor-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Issue', 'store-doctor-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Severity', 'store-doctor-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $wcsd_report['issues'] as $wcsd_issue ) : ?>
					<tr>
						<td><?php echo esc_html( get_the_title( $wcsd_issue->object_id ) ); ?></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $wcsd_issue->type ) ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $wcsd_issue->severity ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
