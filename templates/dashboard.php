<?php
/**
 * Vars available: $latest (object|null), $history (array), $category_scores (array), $last_scan (string)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$overall = $latest ? (int) $latest->overall_score : 0;

$health_label = __( 'No scans yet', 'wc-store-doctor' );
if ( $latest ) {
	if ( $overall >= 90 ) {
		$health_label = __( 'Excellent Health', 'wc-store-doctor' );
	} elseif ( $overall >= 75 ) {
		$health_label = __( 'Good Health', 'wc-store-doctor' );
	} elseif ( $overall >= 50 ) {
		$health_label = __( 'Needs Attention', 'wc-store-doctor' );
	} else {
		$health_label = __( 'Critical', 'wc-store-doctor' );
	}
}
?>
<div class="wrap wcsd-wrap">
	<h1><?php esc_html_e( 'WooCommerce Store Doctor', 'wc-store-doctor' ); ?></h1>

	<div class="wcsd-score-card">
		<div class="wcsd-score-circle" data-score="<?php echo esc_attr( $overall ); ?>">
			<span class="wcsd-score-number"><?php echo esc_html( $overall ); ?></span>
			<span class="wcsd-score-max">/ 100</span>
		</div>
		<div class="wcsd-score-meta">
			<p class="wcsd-score-label"><?php echo esc_html( $health_label ); ?></p>
			<p class="wcsd-score-last-scan">
				<?php
				if ( $last_scan ) {
					printf(
						/* translators: %s: date/time of last scan */
						esc_html__( 'Last Scan: %s', 'wc-store-doctor' ),
						esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_scan ) ) )
					);
				} else {
					esc_html_e( 'Run your first scan to see your store health.', 'wc-store-doctor' );
				}
				?>
			</p>
			<button type="button" id="wcsd-run-scan" class="button button-primary">
				<?php esc_html_e( 'Scan Store', 'wc-store-doctor' ); ?>
			</button>
			<span id="wcsd-scan-status" class="wcsd-scan-status"></span>
		</div>
	</div>

	<h2><?php esc_html_e( 'Categories', 'wc-store-doctor' ); ?></h2>
	<div class="wcsd-categories">
		<?php if ( empty( $category_scores ) ) : ?>
			<p><?php esc_html_e( 'No category data yet — run a scan.', 'wc-store-doctor' ); ?></p>
		<?php else : ?>
			<?php foreach ( $category_scores as $scanner_id => $cat ) : ?>
				<div class="wcsd-category-row">
					<a class="wcsd-category-label" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&wcsd_scanner=' . rawurlencode( $scanner_id ) ) ); ?>">
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

	<h2><?php esc_html_e( 'Store Health History', 'wc-store-doctor' ); ?></h2>
	<div class="wcsd-history">
		<?php if ( empty( $history ) ) : ?>
			<p><?php esc_html_e( 'History will appear here after your first few scans.', 'wc-store-doctor' ); ?></p>
		<?php elseif ( count( $history ) < 2 ) : ?>
			<p><?php esc_html_e( 'Run a few more scans to see a trend graph here.', 'wc-store-doctor' ); ?></p>
		<?php else : ?>
			<div class="wcsd-chart-wrap">
				<canvas id="wcsd-history-chart" height="220"></canvas>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $history ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Score', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Critical', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Warnings', 'wc-store-doctor' ); ?></th>
						<th><?php esc_html_e( 'Suggestions', 'wc-store-doctor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_reverse( $history ) as $row ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->scanned_at ) ) ); ?></td>
							<td><?php echo esc_html( $row->overall_score ); ?>/100</td>
							<td><?php echo esc_html( $row->issues_critical ); ?></td>
							<td><?php echo esc_html( $row->issues_warning ); ?></td>
							<td><?php echo esc_html( $row->issues_suggestion ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcsd-issues' ) ); ?>" class="button">
			<?php esc_html_e( 'View Issue Center →', 'wc-store-doctor' ); ?>
		</a>
	</p>
</div>
