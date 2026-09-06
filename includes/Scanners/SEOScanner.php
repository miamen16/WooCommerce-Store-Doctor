<?php

namespace WCSD\Scanners;

use WCSD\Core\AbstractScanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic on-page SEO checks that don't require a dedicated SEO plugin:
 * short product title, missing image alt text, missing short description
 * (used as the "excerpt"/meta description fallback by many themes).
 */
class SEOScanner extends AbstractScanner {

	public function id() {
		return 'seo';
	}

	public function label() {
		return __( 'SEO', 'woocommerce-store-doctor' );
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

			$issues = array_merge( $issues, $this->check_seo( $product ) );
		}

		$this->set_checked_count( count( $product_ids ) );

		return $issues;
	}

	private function check_seo( \WC_Product $product ) {
		$issues = array();
		$id     = $product->get_id();

		if ( strlen( $product->get_name() ) < 10 ) {
			$issues[] = $this->make_issue(
				'short_title',
				'suggestion',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has a very short title, which may hurt search visibility.', 'woocommerce-store-doctor' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		if ( '' === trim( wp_strip_all_tags( $product->get_short_description() ) ) ) {
			$issues[] = $this->make_issue(
				'missing_short_description',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" has no short description (often used as the meta description).', 'woocommerce-store-doctor' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		}

		$image_id = $product->get_image_id();
		if ( $image_id && ! get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) {
			$issues[] = $this->make_issue(
				'missing_image_alt',
				'warning',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" featured image has no alt text.', 'woocommerce-store-doctor' ),
					$product->get_name()
				),
				'product',
				$id,
				true,
				'image_alt_text'
			);
		}

		return $issues;
	}
}
