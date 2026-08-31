<?php

namespace WCSD\Fixers;

use WCSD\Core\AbstractFixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixes 'missing_image_alt' issues by setting the featured image's alt
 * text to the product name — a safe, reversible default.
 */
class ImageAltTextFixer extends AbstractFixer {

	public function id() {
		return 'image_alt_text';
	}

	public function label() {
		return __( 'Set image alt text to product name', 'wc-store-doctor' );
	}

	public function preview( array $object_ids ) {
		$items = array();

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->get_image_id() ) {
				continue;
			}

			$items[] = array(
				'object_id' => $product_id,
				'label'     => $product->get_name(),
				'current'   => __( '(empty)', 'wc-store-doctor' ),
				'proposed'  => $product->get_name(),
			);
		}

		return $items;
	}

	public function apply( array $object_ids ) {
		$results          = array();
		$product_image_map = array();
		$image_old_values = array();

		// First pass: resolve product -> image, and snapshot each DISTINCT
		// image's original alt text BEFORE any writes happen. This matters
		// because multiple products can share the same featured image (common
		// in seeded/demo catalogs) — reading "old value" mid-loop would pick
		// up a value another product in this same batch just wrote, not the
		// true original, which then corrupts revert.
		foreach ( $object_ids as $product_id ) {
			$product  = wc_get_product( $product_id );
			$image_id = $product ? $product->get_image_id() : 0;

			if ( ! $image_id ) {
				continue;
			}

			$product_image_map[ $product_id ] = $image_id;

			if ( ! array_key_exists( $image_id, $image_old_values ) ) {
				$image_old_values[ $image_id ] = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			}
		}

		// Second pass: write the new alt text using the true pre-change snapshot.
		foreach ( $product_image_map as $product_id => $image_id ) {
			$product = wc_get_product( $product_id );
			$new_alt = $product->get_name();

			update_post_meta( $image_id, '_wp_attachment_image_alt', $new_alt );

			$results[] = array(
				'object_id' => $product_id,
				'old_value' => (string) $image_old_values[ $image_id ],
				'new_value' => $new_alt,
				'meta'      => array( 'image_id' => $image_id ),
			);
		}

		return $results;
	}

	public function revert_one( $backup ) {
		$image_id = isset( $backup->meta['image_id'] ) ? (int) $backup->meta['image_id'] : 0;
		if ( ! $image_id ) {
			return;
		}

		if ( '' === $backup->old_value ) {
			delete_post_meta( $image_id, '_wp_attachment_image_alt' );
		} else {
			update_post_meta( $image_id, '_wp_attachment_image_alt', $backup->old_value );
		}
	}
}
