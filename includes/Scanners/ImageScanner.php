<?php

namespace WCSD\Scanners;

use WCSD\Core\AbstractScanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks products for missing featured images and gallery images.
 */
class ImageScanner extends AbstractScanner {

	public function id() {
		return 'image';
	}

	public function label() {
		return __( 'Images', 'woocommerce-store-doctor' );
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

			if ( ! $product->get_image_id() ) {
				$issues[] = $this->make_issue(
					'missing_featured_image',
					'critical',
					sprintf(
						/* translators: %s: product name */
						__( 'Product "%s" has no featured image.', 'woocommerce-store-doctor' ),
						$product->get_name()
					),
					'product',
					$product_id,
					true,
					'featured_image'
				);
			}

			if ( empty( $product->get_gallery_image_ids() ) ) {
				$issues[] = $this->make_issue(
					'missing_gallery',
					'warning',
					sprintf(
						/* translators: %s: product name */
						__( 'Product "%s" has no gallery images.', 'woocommerce-store-doctor' ),
						$product->get_name()
					),
					'product',
					$product_id,
					false,
					null
				);
			}
		}

		$this->set_checked_count( count( $product_ids ) );

		return $issues;
	}
}
