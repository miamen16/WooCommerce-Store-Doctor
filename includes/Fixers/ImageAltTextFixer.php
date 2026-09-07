<?php

namespace WCSD\Fixers;

use WCSD\Core\AbstractFixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixes 'missing_image_alt' issues by setting the featured image's alt
 * text to the product name — a safe, reversible default when the image
 * belongs to one product only.
 */
class ImageAltTextFixer extends AbstractFixer {

	/** @var array<int, bool> */
	private $shared_image_cache = array();

	public function id() {
		return 'image_alt_text';
	}

	public function label() {
		return __( 'Set image alt text to product name', 'store-doctor-for-woocommerce' );
	}

	public function preview( array $object_ids ) {
		$items = array();

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			$image_id = $product ? $product->get_image_id() : 0;

			if ( ! $image_id || $this->is_shared_image( $image_id ) || get_post_meta( $image_id, '_wp_attachment_image_alt', true ) !== '' ) {
				continue;
			}

			$items[] = array(
				'object_id' => $product_id,
				'label'     => $product->get_name(),
				'current'   => __( '(empty)', 'store-doctor-for-woocommerce' ),
				'proposed'  => $product->get_name(),
			);
		}

		return $items;
	}

	public function apply( array $object_ids ) {
		$results           = array();
		$product_image_map = array();
		$image_old_values  = array();

		// Only auto-fix images that belong to one product. Attachment alt text
		// is global, so changing a shared image to match one product would also
		// change the alt text displayed for every other product using it.
		foreach ( $object_ids as $product_id ) {
			$product  = wc_get_product( $product_id );
			$image_id = $product ? $product->get_image_id() : 0;

			if ( ! $image_id || $this->is_shared_image( $image_id ) ) {
				continue;
			}

			$current_alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( '' !== $current_alt ) {
				// The issue may have been resolved since the scan. Never overwrite
				// an alt text that now exists.
				continue;
			}

			$product_image_map[ $product_id ] = $image_id;

			if ( ! array_key_exists( $image_id, $image_old_values ) ) {
				$image_old_values[ $image_id ] = $current_alt;
			}
		}

		foreach ( $product_image_map as $product_id => $image_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

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
			return false;
		}

		$current_alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$expected_alt = isset( $backup->new_value ) ? (string) $backup->new_value : '';

		// Do not overwrite an alt text that was deliberately changed after
		// the auto-fix was applied.
		if ( $current_alt !== $expected_alt ) {
			return false;
		}

		if ( '' === $backup->old_value ) {
			delete_post_meta( $image_id, '_wp_attachment_image_alt' );
		} else {
			update_post_meta( $image_id, '_wp_attachment_image_alt', $backup->old_value );
		}

		return true;
	}

	private function is_shared_image( $image_id ) {
		$image_id = (int) $image_id;
		if ( isset( $this->shared_image_cache[ $image_id ] ) ) {
			return $this->shared_image_cache[ $image_id ];
		}

		$products = get_posts( array(
			'post_type'      => array( 'product', 'product_variation' ),
			'post_status'    => 'any',
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_thumbnail_id',
			'meta_value'     => (string) $image_id,
		) );

		$this->shared_image_cache[ $image_id ] = count( $products ) > 1;
		return $this->shared_image_cache[ $image_id ];
	}
}
