<?php
/**
 * Single application point for repairs: live HTML in, changes + HTML out.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs enabled repairs over an HTML string. Identity guarantee: when no
 * repair changes anything, the ORIGINAL string is returned untouched —
 * a repaired page differs from the original only by recorded changes.
 */
class A11yFix_Preview {

	/**
	 * Apply repairs.
	 *
	 * @param string             $html  Raw HTML.
	 * @param array<string,bool> $map   Enabled map (missing = repair default).
	 * @param array              $ctx   Injectables for repairs.
	 * @return array {html: string, changes: A11yFix_Change[]}
	 */
	public static function run( $html, array $map = array(), array $ctx = array() ) {
		$changes = array();

		$enabled = A11yFix_Repairs::enabled( $map );
		if ( ! $enabled ) {
			return array(
				'html'    => $html,
				'changes' => $changes,
			);
		}

		$dom = A11yFix_Parser::load( $html );
		if ( ! $dom ) {
			return array(
				'html'    => $html,
				'changes' => $changes,
			);
		}

		foreach ( $enabled as $repair ) {
			foreach ( $repair->apply( $dom, $ctx ) as $change ) {
				$changes[] = $change;
			}
		}

		return array(
			// Zero changes -> original bytes, never a re-serialization.
			'html'    => $changes ? A11yFix_Parser::save( $dom ) : $html,
			'changes' => $changes,
		);
	}
}
