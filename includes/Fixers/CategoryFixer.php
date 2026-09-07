<?php

namespace WCSD\Fixers;

use WCSD\Core\AbstractFixer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixes 'missing_category' issues by assigning the store's default
 * WooCommerce product category (falls back to "Uncategorized").
 */
class CategoryFixer extends AbstractFixer {

	public function id() {
		return 'product_category';
	}

	public function label() {
		return __( 'Assign default category', 'store-doctor-for-woocommerce' );
	}

	private function get_default_term() {
		$default_cat_id = (int) get_option( 'default_product_cat' );

		if ( $default_cat_id && term_exists( $default_cat_id, 'product_cat' ) ) {
			$term = get_term( $default_cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		$existing = get_term_by( 'name', 'Uncategorized', 'product_cat' );
		if ( $existing ) {
			return $existing;
		}

		$created = wp_insert_term( 'Uncategorized', 'product_cat' );
		if ( is_wp_error( $created ) ) {
			return null;
		}

		return get_term( $created['term_id'], 'product_cat' );
	}

	public function preview( array $object_ids ) {
		$term  = $this->get_default_term();
		$items = array();

		if ( ! $term ) {
			return $items;
		}

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$items[] = array(
				'object_id' => $product_id,
				'label'     => $product->get_name(),
				'current'   => __( 'No category', 'store-doctor-for-woocommerce' ),
				'proposed'  => $term->name,
			);
		}

		return $items;
	}

	public function apply( array $object_ids ) {
		$term = $this->get_default_term();
		if ( ! $term ) {
			return array();
		}

		$results = array();

		foreach ( $object_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$set = wp_set_object_terms( $product_id, (int) $term->term_id, 'product_cat', false );
			if ( is_wp_error( $set ) ) {
				continue;
			}

			$results[] = array(
				'object_id' => $product_id,
				'old_value' => '',
				'new_value' => (string) $term->term_id,
				'meta'      => array( 'term_id' => (int) $term->term_id ),
			);
		}

		return $results;
	}

	public function revert_one( $backup ) {
		$term_id = isset( $backup->meta['term_id'] ) ? (int) $backup->meta['term_id'] : (int) $backup->new_value;
		if ( $term_id ) {
			wp_remove_object_terms( (int) $backup->object_id, $term_id, 'product_cat' );
		}
	}
}
