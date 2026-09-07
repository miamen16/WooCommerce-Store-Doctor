<?php

namespace WCSD\Scanners;

use WCSD\Core\AbstractScanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks pricing sanity: missing price, zero price on a paid product,
 * and sale price that is not actually lower than the regular price
 * (a common copy-paste mistake).
 */
class PricingScanner extends AbstractScanner {

	public function id() {
		return 'pricing';
	}

	public function label() {
		return __( 'Pricing', 'store-doctor-for-woocommerce' );
	}

	public function scan() {
		$issues = array();

		$product_ids = wc_get_products( array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
		) );

		$checked = 0;

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || $product->is_type( 'variable' ) ) {
				continue;
			}

			$checked++;
			$issues = array_merge( $issues, $this->check_price( $product ) );
		}

		$this->set_checked_count( $checked );

		return $issues;
	}

	private function check_price( \WC_Product $product ) {
		$issues = array();
		$id     = $product->get_id();

		$regular = $product->get_regular_price();
		$sale    = $product->get_sale_price();

		if ( '' === $regular ) {
			$issues[] = $this->make_issue(
				'missing_price',
				'critical',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has no price set.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
			return $issues;
		}

		if ( (float) $regular === 0.0 && ! $product->is_type( 'free' ) ) {
			$issues[] = $this->make_issue(
				'zero_price',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" is priced at $0.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		if ( '' !== $sale && (float) $sale >= (float) $regular ) {
			$issues[] = $this->make_issue(
				'invalid_sale_price',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has a sale price that is not lower than its regular price.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		return $issues;
	}
}
