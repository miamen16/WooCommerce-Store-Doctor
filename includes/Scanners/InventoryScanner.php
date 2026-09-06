<?php

namespace WCSD\Scanners;

use WCSD\Core\AbstractScanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks stock management state: out-of-stock, low-stock, and products
 * with stock management disabled entirely (which hides them from
 * inventory reports and can lead to overselling).
 */
class InventoryScanner extends AbstractScanner {

	public function id() {
		return 'inventory';
	}

	public function label() {
		return __( 'Inventory', 'woocommerce-store-doctor' );
	}

	public function scan() {
		$issues = array();

		$product_ids = wc_get_products( array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
		) );

		$low_stock_threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
		$checked             = 0;

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || $product->is_type( 'variable' ) ) {
				continue; // Variations are checked individually below.
			}

			$checked++;
			$issues = array_merge( $issues, $this->check_stock( $product, $low_stock_threshold ) );
		}

		$this->set_checked_count( $checked );

		return $issues;
	}

	private function check_stock( \WC_Product $product, $low_stock_threshold ) {
		$issues = array();
		$id     = $product->get_id();

		if ( ! $product->managing_stock() ) {
			$issues[] = $this->make_issue(
				'stock_not_managed',
				'suggestion',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" does not have stock management enabled.', 'woocommerce-store-doctor' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
			return $issues;
		}

		$stock = $product->get_stock_quantity();

		if ( 'outofstock' === $product->get_stock_status() ) {
			$issues[] = $this->make_issue(
				'out_of_stock',
				'critical',
				sprintf(
					/* translators: %s: product name */
					__( 'Product "%s" is out of stock.', 'woocommerce-store-doctor' ),
					$product->get_name()
				),
				'product',
				$id,
				false
			);
		} elseif ( null !== $stock && $stock <= $low_stock_threshold ) {
			$issues[] = $this->make_issue(
				'low_stock',
				'critical',
				sprintf(
					/* translators: 1: product name, 2: remaining stock quantity */
					__( 'Product "%1$s" is low on stock (%2$d remaining).', 'woocommerce-store-doctor' ),
					$product->get_name(),
					$stock
				),
				'product',
				$id,
				false
			);
		}

		return $issues;
	}
}
