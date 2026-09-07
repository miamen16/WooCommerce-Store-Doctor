<?php

namespace WCSD\Admin;

use WCSD\Core\IssueManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets Issue Center / Dashboard numbers link straight into WordPress's own
 * Products list (edit.php?post_type=product), pre-filtered to exactly the
 * products behind that count — no separate custom screen needed.
 *
 * URL params:
 *   wcsd_type    filter to one exact issue type, e.g. 'zero_price'
 *   wcsd_scanner filter to every open issue from one scanner, e.g. 'pricing'
 * ('wcsd_type' wins if both are present.)
 */
class ProductFilter {

	private $issue_manager;

	public function __construct( IssueManager $issue_manager ) {
		$this->issue_manager = $issue_manager;

		add_action( 'pre_get_posts', array( $this, 'filter_query' ) );
		add_action( 'admin_notices', array( $this, 'render_filter_notice' ) );
	}

	private function is_product_list_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return $screen && 'edit-product' === $screen->id;
	}

	private function get_requested_type() {
		// Nonce is verified by filter_query() / render_filter_notice() before this helper is used.
		return isset( $_GET['wcsd_type'] ) ? sanitize_key( wp_unslash( $_GET['wcsd_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private function get_requested_scanner() {
		// Nonce is verified by filter_query() / render_filter_notice() before this helper is used.
		return isset( $_GET['wcsd_scanner'] ) ? sanitize_key( wp_unslash( $_GET['wcsd_scanner'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private function has_valid_filter_nonce() {
		if ( ! isset( $_GET['_wcsd_filter_nonce'] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['_wcsd_filter_nonce'] ) );
		return (bool) wp_verify_nonce( $nonce, 'wcsd_product_filter' );
	}

	/**
	 * Resolve the current request to a product-id list, or null if no
	 * Store Doctor filter is active on this request.
	 */
	private function resolve_ids() {
		$type = $this->get_requested_type();
		if ( $type ) {
			return $this->issue_manager->get_object_ids_by_type( $type );
		}

		$scanner = $this->get_requested_scanner();
		if ( $scanner ) {
			return $this->issue_manager->get_object_ids_by_scanner( $scanner );
		}

		return null;
	}

	public function filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || ! $this->is_product_list_screen() ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ( $this->get_requested_type() || $this->get_requested_scanner() ) && ! $this->has_valid_filter_nonce() ) {
			return;
		}

		$ids = $this->resolve_ids();
		if ( null === $ids ) {
			return;
		}

		// Empty result set -> force a query that matches nothing, rather
		// than accidentally falling through to "show everything".
		$query->set( 'post__in', empty( $ids ) ? array( 0 ) : $ids );
		$query->set( 'orderby', 'post__in' );
	}

	public function render_filter_notice() {
		if ( ! $this->is_product_list_screen() ) {
			return;
		}

		$type    = $this->get_requested_type();
		$scanner = $type ? '' : $this->get_requested_scanner();

		if ( ! $type && ! $scanner ) {
			return;
		}

		if ( ! $this->has_valid_filter_nonce() ) {
			return;
		}

		$ids   = $this->resolve_ids();
		$count = is_array( $ids ) ? count( $ids ) : 0;
		$label = $type
			? ucwords( str_replace( '_', ' ', $type ) )
			: ucwords( str_replace( '_', ' ', $scanner ) ) . ' ' . __( 'issues', 'store-doctor-for-woocommerce' );

		$clear_url = remove_query_arg( array( 'wcsd_type', 'wcsd_scanner', '_wcsd_filter_nonce' ) );

		echo '<div class="notice notice-info wcsd-filter-notice"><p>';
		printf(
			/* translators: 1: number of products, 2: issue/category label */
			esc_html__( 'Store Doctor filter: showing %1$d product(s) — %2$s.', 'store-doctor-for-woocommerce' ),
			(int) $count,
			esc_html( $label )
		);
		echo ' <a href="' . esc_url( $clear_url ) . '">' . esc_html__( 'Clear filter', 'store-doctor-for-woocommerce' ) . '</a>';
		echo '</p></div>';
	}
}
