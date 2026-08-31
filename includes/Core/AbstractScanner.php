<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every scanner (ProductScanner, ImageScanner, ...) extends this.
 *
 * A scanner's only job is: look at the store, and return an array of issues.
 * It must NOT write to the DB directly — the Scanner engine collects results
 * from every registered scanner and hands them to the IssueManager as a batch.
 */
abstract class AbstractScanner {

	/**
	 * How many objects (usually products) this scanner actually evaluated
	 * during scan(). Used to normalize the score so stores with hundreds
	 * of products don't get an unfairly harsh score just because raw issue
	 * counts are naturally larger. Scanners set this via set_checked_count()
	 * at the end of scan().
	 */
	protected $checked_count = 0;

	/**
	 * Unique machine name, e.g. 'product', 'image', 'inventory'.
	 * Used as the "scanner" column in wp_wcsd_issues.
	 */
	abstract public function id();

	/**
	 * Human label shown in the dashboard category breakdown, e.g. "Images".
	 */
	abstract public function label();

	/**
	 * Run the scan and return a flat array of issue arrays. Each issue:
	 * [
	 *   'type'        => string   machine-readable type, e.g. 'missing_gallery'
	 *   'severity'    => 'critical'|'warning'|'suggestion'
	 *   'object_type' => 'product'|'order'|'customer'|null
	 *   'object_id'   => int|null
	 *   'message'     => string   human-readable message
	 *   'fixable'     => bool
	 *   'fixer'       => string|null  machine name of the Fixer that can resolve it
	 *   'meta'        => array|null   extra data the fixer/UI might need
	 * ]
	 *
	 * Implementations MUST call set_checked_count() with the number of
	 * objects actually evaluated (not just wc_get_products()'s raw count —
	 * if some objects are skipped, e.g. variable products, only count the
	 * ones actually checked) so the score below is normalized correctly.
	 *
	 * @return array<int, array>
	 */
	abstract public function scan();

	/**
	 * Record how many objects this scan actually evaluated. Call this at
	 * the end of scan(), once, with the final count.
	 */
	protected function set_checked_count( $count ) {
		$this->checked_count = (int) $count;
	}

	public function get_checked_count() {
		return $this->checked_count;
	}

	/**
	 * Score for this category, 0-100.
	 *
	 * Normalized by how many objects were checked, not raw issue count —
	 * otherwise a store with hundreds of products floors to 0 the moment a
	 * few dozen share the same minor issue, even though most stores that
	 * size will always have *some* open issues. Formula:
	 *
	 *     score = 100 / (1 + average_weighted_issues_per_object)
	 *
	 * This gives a smooth curve instead of a hard cliff: an average of 1
	 * weighted issue per product still scores 50, an average of 3 scores
	 * 25, and it only approaches 0 as issues pile up per product rather
	 * than as the *store* grows. Scanners can override this for custom
	 * logic (e.g. weighting stock-outs more heavily) — the important part
	 * is dividing by checked_count somewhere so the score doesn't just
	 * track store size.
	 *
	 * @param array $issues Issues returned by scan() for this scanner.
	 */
	public function score_from_issues( array $issues ) {
		if ( empty( $issues ) ) {
			return 100;
		}

		$severities = wp_list_pluck( $issues, 'severity' );
		$weighted   = ScoreFormula::weighted_sum( $severities );

		return ScoreFormula::score( $weighted, $this->checked_count );
	}

	/**
	 * Helper for building a well-formed issue array so scanners don't
	 * repeat array-shape boilerplate.
	 */
	protected function make_issue( $type, $severity, $message, $object_type = null, $object_id = null, $fixable = false, $fixer = null, $meta = null ) {
		return array(
			'type'        => $type,
			'severity'    => $severity,
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'message'     => $message,
			'fixable'     => (bool) $fixable,
			'fixer'       => $fixer,
			'meta'        => $meta,
		);
	}
}
