<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates all registered scanners and produces one full-store scan result.
 */
class Scanner {

	/** @var AbstractScanner[] */
	private $scanners = array();

	private $issue_manager;
	private $score_manager;

	public function __construct( IssueManager $issue_manager, ScoreManager $score_manager ) {
		$this->issue_manager = $issue_manager;
		$this->score_manager = $score_manager;
	}

	public function add( AbstractScanner $scanner ) {
		$this->scanners[ $scanner->id() ] = $scanner;
	}

	/** @return AbstractScanner[] */
	public function all() {
		return $this->scanners;
	}

	public function get( $id ) {
		return isset( $this->scanners[ $id ] ) ? $this->scanners[ $id ] : null;
	}

	/**
	 * Run a single scanner by id. Useful for the "re-check" action after a fix.
	 */
	public function run_one( $id ) {
		$scanner = $this->get( $id );
		if ( ! $scanner ) {
			return null;
		}
		$issues = $scanner->scan();
		$this->issue_manager->replace_for_scanner( $id, $issues );
		return $issues;
	}

	/**
	 * Run every registered scanner, store the resulting issues, compute
	 * category + overall scores, and record a health-history snapshot.
	 *
	 * @return array{overall_score:int, categories:array, counts:array}
	 */
	public function run_full_scan() {
		$all_issues       = array();
		$category_scores  = array();

		foreach ( $this->scanners as $id => $scanner ) {
			$issues = $scanner->scan();

			$this->issue_manager->replace_for_scanner( $id, $issues );

			$category_scores[ $id ] = array(
				'label' => $scanner->label(),
				'score' => $scanner->score_from_issues( $issues ),
			);

			foreach ( $issues as $issue ) {
				$issue['scanner']    = $id;
				$all_issues[]        = $issue;
			}
		}

		$overall_score = $this->score_manager->calculate_overall( $category_scores );
		$counts        = $this->count_by_severity( $all_issues );

		$this->score_manager->record_history( $overall_score, $category_scores, $counts );

		update_option( 'wcsd_last_scan', current_time( 'mysql' ) );

		return array(
			'overall_score' => $overall_score,
			'categories'    => $category_scores,
			'counts'        => $counts,
			'scanned_at'    => current_time( 'mysql' ),
		);
	}

	private function count_by_severity( array $issues ) {
		$counts = array(
			'critical'   => 0,
			'warning'    => 0,
			'suggestion' => 0,
		);

		foreach ( $issues as $issue ) {
			if ( isset( $counts[ $issue['severity'] ] ) ) {
				$counts[ $issue['severity'] ]++;
			}
		}

		return $counts;
	}
}
