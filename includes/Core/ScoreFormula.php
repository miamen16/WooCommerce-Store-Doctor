<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single place the "issues -> 0-100 score" math lives, so
 * AbstractScanner (whole-store categories) and VendorHealth (per-vendor,
 * for Dokan marketplaces) can't drift apart into two different formulas.
 */
class ScoreFormula {

	const WEIGHT_CRITICAL   = 8;
	const WEIGHT_WARNING    = 3;
	const WEIGHT_SUGGESTION = 1;

	/**
	 * Sum of severity weights across a set of issues (each issue array
	 * must have a 'severity' key, or pass raw severity strings).
	 *
	 * @param array $severities Array of 'critical'|'warning'|'suggestion' strings.
	 */
	public static function weighted_sum( array $severities ) {
		$weighted = 0;

		foreach ( $severities as $severity ) {
			switch ( $severity ) {
				case 'critical':
					$weighted += self::WEIGHT_CRITICAL;
					break;
				case 'warning':
					$weighted += self::WEIGHT_WARNING;
					break;
				default:
					$weighted += self::WEIGHT_SUGGESTION;
			}
		}

		return $weighted;
	}

	/**
	 * score = 100 / (1 + average_weighted_issues_per_object)
	 *
	 * Normalized by how many objects were checked so a vendor/store with
	 * hundreds of products doesn't floor to 0 just because a modest
	 * fraction share one minor issue. See AbstractScanner for the full
	 * rationale — this is the same formula, factored out so VendorHealth
	 * (Phase 9) scores mean the same thing as the store-wide category
	 * scores (Phase 3).
	 *
	 * @param int $weighted_sum Sum of severity weights (see weighted_sum()).
	 * @param int $checked_count How many objects were evaluated (min 1 used internally).
	 */
	public static function score( $weighted_sum, $checked_count ) {
		if ( 0 === $weighted_sum ) {
			return 100;
		}

		$denominator = max( 1, (int) $checked_count );
		$average     = $weighted_sum / $denominator;

		return (int) round( 100 / ( 1 + $average ) );
	}
}
