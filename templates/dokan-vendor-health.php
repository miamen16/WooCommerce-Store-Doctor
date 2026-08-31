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

$plugin = \WCSD\Core\Plugin::instance();
$report = $plugin->vendor_health->get_vendor_report( get_current_user_id() );

$overall = $report['overall'];

$health_label = __( 'Excellent Health', 'wc-store-doctor' );
if ( $overall['score'] < 50 ) {
	$health_label = __( 'Needs Attention', 'wc-store-doctor' );
} elseif ( $overall['score'] < 75 ) {
	$health_label = __( 'Good Health', 'wc-store-doctor' );
}

dokan_get_template_part( 'global/dashboard-header', '', array(
	'header_title' => __( 'Store Health', 'wc-store-doctor' ),
	'description'  => __( "See how your listings are doing and what's worth fixing.", 'wc-store-doctor' ),
) );
?>

<div class="dokan-dashboard-content wcsd-vendor-health">

	<div class="wcsd-score-card">
		<div class="wcsd-score-circle" data-score="<?php echo esc_attr( $overall['score'] ); ?>">
			<span class="wcsd-score-number"><?php echo esc_html( $overall['score'] ); ?></span>
			<span class="wcsd-score-max">/ 100</span>
		</div>
		<div class="wcsd-score-meta">
			<p class="wcsd-score-label"><?php echo esc_html( $health_label ); ?></p>
			<p class="wcsd-score-last-scan">
				<?php
				printf(
					/* translators: %d: number of products */
					esc_html__( 'Based on %d published product(s).', 'wc-store-doctor' ),
					(int) $overall['product_count']
				);
				?>
			</p>
		</div>
	</div>

	<?php if ( ! empty( $report['categories'] ) ) : ?>
		<h3><?php esc_html_e( 'Breakdown', 'wc-store-doctor' ); ?></h3>
		<div class="wcsd-categories">
			<?php foreach ( $report['categories'] as $cat ) : ?>
				<div class="wcsd-category-row">
					<span class="wcsd-category-label"><?php echo esc_html( $cat['label'] ); ?></span>
					<div class="wcsd-category-bar">
						<div class="wcsd-category-bar-fill" style="width: <?php echo esc_attr( $cat['score'] ); ?>%;"></div>
					</div>
					<span class="wcsd-category-score"><?php echo esc_html( $cat['score'] ); ?>/100</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Things worth fixing', 'wc-store-doctor' ); ?></h3>
	<?php if ( empty( $report['issues'] ) ) : ?>
		<p><?php esc_html_e( 'No open issues on your listings right now. Nice work!', 'wc-store-doctor' ); ?></p>
	<?php else : ?>
		<table class="dokan-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Issue', 'wc-store-doctor' ); ?></th>
					<th><?php esc_html_e( 'Severity', 'wc-store-doctor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $report['issues'] as $issue ) : ?>
					<tr>
						<td><?php echo esc_html( get_the_title( $issue->object_id ) ); ?></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $issue->type ) ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $issue->severity ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
