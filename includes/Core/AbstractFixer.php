<?php

namespace WCSD\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every fixer (CategoryFixer, FeaturedImageFixer, ...) extends this.
 *
 * A fixer never writes to the DB on its own — FixManager calls preview()
 * to show the user what will change, then apply() to actually change it
 * and record a backup row per object so the whole batch can be reverted.
 */
abstract class AbstractFixer {

	/**
	 * Unique machine name, matches the 'fixer' value scanners attach to issues.
	 */
	abstract public function id();

	/**
	 * Human label, e.g. "Assign default category".
	 */
	abstract public function label();

	/**
	 * Build a preview of what would change, WITHOUT writing anything.
	 * Skip objects that can't actually be fixed (e.g. no gallery image to
	 * promote to featured) rather than forcing every object into the list.
	 *
	 * @param int[] $object_ids
	 * @return array<int, array{object_id:int, label:string, current:string, proposed:string}>
	 */
	abstract public function preview( array $object_ids );

	/**
	 * Actually apply the fix to each object. Return one row per object that
	 * was successfully changed, with enough info for revert_one() to undo it.
	 *
	 * @param int[] $object_ids
	 * @return array<int, array{object_id:int, old_value:string, new_value:string, meta:array|null}>
	 */
	abstract public function apply( array $object_ids );

	/**
	 * Undo a single change using the stored backup row.
	 *
	 * @param object $backup Row from wp_wcsd_fix_backups (object_id, old_value, new_value, meta[decoded]).
	 */
	abstract public function revert_one( $backup );
}
