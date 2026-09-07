<?php
/**
 * Vars available: $latest (object|null), $history (array), $category_scores (array), $last_scan (string)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcsd_overall = $latest ? (int) $latest->overall_score : 0;

$wcsd_health_label = __( 'No scans yet', 'store-doctor-for-woocommerce' );
if ( $latest ) {
	if ( $wcsd_overall >= 90 ) {
		$wcsd_health_label = __( 'Excellent Health', 'store-doctor-for-woocommerce' );
	} elseif ( $wcsd_overall >= 75 ) {
		$wcsd_health_label = __( 'Good Health', 'store-doctor-for-woocommerce' );
	} elseif ( $wcsd_overall >= 50 ) {
		$wcsd_health_label = __( 'Needs Attention', 'store-doctor-for-woocommerce' );
	} else {
		$wcsd_health_label = __( 'Critical', 'store-doctor-for-woocommerce' );
	}
}
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'Store Doctor for WooCommerce', 'store-doctor-for-woocommerce' ); ?></h1>

	<div class="wcsd-score-card">
		<div class="wcsd-score-circle" data-score="<?php echo esc_attr( $wcsd_overall ); ?>">
			<span class="wcsd-score-number"><?php echo esc_html( $wcsd_overall ); ?></span>
			<span class="wcsd-score-max">/ 100</span>
		</div>
		<div class="wcsd-score-meta">
			<p class="wcsd-score-label"><?php echo esc_html( $wcsd_health_label ); ?></p>
			<p class="wcsd-score-last-scan">
				<?php
				if ( $last_scan ) {
					printf(
						/* translators: %s: date/time of last scan */
						esc_html__( 'Last Scan: %s', 'store-doctor-for-woocommerce' ),
						esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_scan ) ) )
					);
				} else {
					esc_html_e( 'Run your first scan to see your store health.', 'store-doctor-for-woocommerce' );
				}
				?>
			</p>
			<button type="button" id="wcsd-run-scan" class="button button-primary">
				<?php esc_html_e( 'Scan Store', 'store-doctor-for-woocommerce' ); ?>
			</button>
			<span id="wcsd-scan-status" class="wcsd-scan-status"></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Categories', 'store-doctor-for-woocommerce' ); ?></h2>
	<div class="wcsd-categories">
		<?php if ( empty( $category_scores ) ) : ?>
			<p><?php esc_html_e( 'No category data yet — run a scan.', 'store-doctor-for-woocommerce' ); ?></p>
		<?php else : ?>
			<?php foreach ( $category_scores as $wcsd_scanner_id => $cat ) : ?>
				<div class="wcsd-category-row">
					<a class="wcsd-category-label" href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=product&wcsd_scanner=' . rawurlencode( $wcsd_scanner_id ) ), 'wcsd_product_filter', '_wcsd_filter_nonce' ) ); ?>">
						<?php echo esc_html( $cat['label'] ); ?>
					</a>
					<div class="wcsd-category-bar">
						<div class="wcsd-category-bar-fill" style="width: <?php echo esc_attr( $cat['score'] ); ?>%;"></div>
					</div>
					<span class="wcsd-category-score"><?php echo esc_html( $cat['score'] ); ?>/100</span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<h2><?php esc_html_e( 'Store Health History', 'store-doctor-for-woocommerce' ); ?></h2>
	<div class="wcsd-history">
		<?php if ( empty( $history ) ) : ?>
			<p><?php esc_html_e( 'History will appear here after your first few scans.', 'store-doctor-for-woocommerce' ); ?></p>
		<?php elseif ( count( $history ) < 2 ) : ?>
			<p><?php esc_html_e( 'Run a few more scans to see a trend graph here.', 'store-doctor-for-woocommerce' ); ?></p>
		<?php else : ?>
			<div class="wcsd-chart-wrap">
				<canvas id="wcsd-history-chart" height="220"></canvas>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $history ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'store-doctor-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Score', 'store-doctor-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Critical', 'store-doctor-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Warnings', 'store-doctor-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Suggestions', 'store-doctor-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_reverse( $history ) as $wcsd_row ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $wcsd_row->scanned_at ) ) ); ?></td>
							<td><?php echo esc_html( $wcsd_row->overall_score ); ?>/100</td>
							<td><?php echo esc_html( $wcsd_row->issues_critical ); ?></td>
							<td><?php echo esc_html( $wcsd_row->issues_warning ); ?></td>
							<td><?php echo esc_html( $wcsd_row->issues_suggestion ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcsd-issues' ) ); ?>" class="button">
			<?php esc_html_e( 'View Issue Center →', 'store-doctor-for-woocommerce' ); ?>
		</a>
	</p>
</div>
