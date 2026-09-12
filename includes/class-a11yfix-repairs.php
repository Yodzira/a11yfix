<?php
/**
 * Repair registry.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists repairs, resolves the enabled subset, provides defaults.
 */
class A11yFix_Repairs {

	/**
	 * All repairs in application order.
	 *
	 * @return A11yFix_Repair[]
	 */
	public static function all() {
		return array(
			new A11yFix_Repair_Img_Alt(),
			new A11yFix_Repair_Html_Lang(),
			new A11yFix_Repair_Table_Scope(),
			new A11yFix_Repair_Control_Name(),
			new A11yFix_Repair_Iframe_Title(),
		);
	}

	/**
	 * Default on/off per repair id: safe ON, risky OFF.
	 *
	 * @return array<string,bool>
	 */
	public static function defaults() {
		$map = array();
		foreach ( self::all() as $repair ) {
			$map[ $repair->id() ] = ! $repair->risky();
		}

		return $map;
	}

	/**
	 * Enabled repairs, in application order.
	 *
	 * @param array<string,bool> $map Repair id => enabled.
	 * @return A11yFix_Repair[]
	 */
	public static function enabled( array $map ) {
		$out = array();
		foreach ( self::all() as $repair ) {
			$id = $repair->id();
			if ( array_key_exists( $id, $map ) ? (bool) $map[ $id ] : ! $repair->risky() ) {
				$out[] = $repair;
			}
		}

		return $out;
	}
}
