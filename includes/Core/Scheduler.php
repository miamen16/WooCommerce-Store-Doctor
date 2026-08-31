<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires up the recurring background scan (registered on activation in
 * the main plugin file via wp_schedule_event).
 */
class Scheduler {

	private $scanner;

	public function __construct( Scanner $scanner ) {
		$this->scanner = $scanner;
	}

	public function register_hooks() {
		add_action( 'wcsd_scheduled_scan', array( $this, 'run_scheduled_scan' ) );
	}

	public function run_scheduled_scan() {
		$this->scanner->run_full_scan();
	}
}
