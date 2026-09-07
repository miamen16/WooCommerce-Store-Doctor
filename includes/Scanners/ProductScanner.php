<?php

namespace WCSD\Scanners;

use WCSD\Core\AbstractScanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks each published product for basic completeness:
 * title, description, SKU, categories, and related/cross-sell products.
 */
class ProductScanner extends AbstractScanner {

	public function id() {
		return 'product';
	}

	public function label() {
		return __( 'Products', 'store-doctor-for-woocommerce' );
	}

	public function scan() {
		$issues = array();

		$product_ids = wc_get_products( array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
		) );

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$issues = array_merge( $issues, $this->check_product( $product ) );
		}

		$this->set_checked_count( count( $product_ids ) );

		return $issues;
	}

	private function check_product( \WC_Product $product ) {
		$issues = array();
		$id     = $product->get_id();
		$body   = wp_strip_all_tags( $product->get_description() );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $body ) : strlen( $body );

		if ( '' === trim( $body ) ) {
			$issues[] = $this->make_issue(
				'missing_description',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has no description.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		} elseif ( $length < 100 ) {
			$issues[] = $this->make_issue(
				'short_description_body',
				'suggestion',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has a very short description (under 100 characters).', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		if ( ! $product->get_sku() ) {
			$issues[] = $this->make_issue(
				'missing_sku',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has no SKU.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		if ( empty( $product->get_category_ids() ) ) {
			$issues[] = $this->make_issue(
				'missing_category',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" is not assigned to any category.', 'store-doctor-for-woocommerce' ),
					$product->get_name()
				),
				'product',
				$id,
				true,
				'product_category'
			);
		}

		$has_related = ! empty( $product->get_upsell_ids() ) || ! empty( $product->get_cross_sell_ids() );
		if ( ! $has_related ) {
			$issues[] = $this->make_issue(
				'no_related_products',
				'suggestion',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has no up-sells or cross-sells configured.', 'store-doctor-for-woocommerce' ),
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
