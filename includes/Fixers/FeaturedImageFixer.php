<?php

namespace WCSD\Fixers;

use WCSD\Core\AbstractFixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixes 'missing_featured_image' issues by promoting the first gallery
 * image to be the featured image. Products with NO gallery images either
 * are skipped — there's nothing to auto-fix, they need a real upload.
 */
class FeaturedImageFixer extends AbstractFixer {

	public function id() {
		return 'featured_image';
	}

	public function label() {
		return __( 'Use first gallery image as featured image', 'store-doctor-for-woocommerce' );
	}

	public function preview( array $object_ids ) {
		$items = array();

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$gallery_ids = $product->get_gallery_image_ids();
			if ( empty( $gallery_ids ) ) {
				continue; // Nothing to promote — not auto-fixable.
			}

			$items[] = array(
				'object_id' => $product_id,
				'label'     => $product->get_name(),
				'current'   => __( 'No featured image', 'store-doctor-for-woocommerce' ),
				'proposed'  => sprintf(
					/* translators: %s: attachment file name */
					__( 'Use gallery image "%s"', 'store-doctor-for-woocommerce' ),
					get_the_title( $gallery_ids[0] )
				),
			);
		}

		return $items;
	}

	public function apply( array $object_ids ) {
		$results = array();

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$gallery_ids = $product->get_gallery_image_ids();
			if ( empty( $gallery_ids ) ) {
				continue;
			}

			$new_featured = $gallery_ids[0];
			$remaining    = array_slice( $gallery_ids, 1 );

			$product->set_image_id( $new_featured );
			$product->set_gallery_image_ids( $remaining );
			$product->save();

			$results[] = array(
				'object_id' => $product_id,
				'old_value' => '',
				'new_value' => (string) $new_featured,
				'meta'      => array( 'previous_gallery' => $gallery_ids ),
			);
		}

		return $results;
	}

	public function revert_one( $backup ) {
		$product = wc_get_product( (int) $backup->object_id );
		if ( ! $product ) {
			return;
		}

		$previous_gallery = isset( $backup->meta['previous_gallery'] ) ? $backup->meta['previous_gallery'] : array();

		$product->set_image_id( '' );
		$product->set_gallery_image_ids( $previous_gallery );
		$product->save();
	}
}
