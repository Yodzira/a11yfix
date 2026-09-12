<?php

use PHPUnit\Framework\TestCase;

/**
 * Settings sanitize/defaults.
 */
class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['__a11yfix_options'] = array();
	}

	public function test_defaults() {
		$settings = A11yFix_Settings::get();

		$this->assertTrue( $settings['master_enabled'] );
		$this->assertTrue( $settings['scan_enabled'] );
		$this->assertSame( 10, $settings['scan_max_pages'] );
		$this->assertSame( 90, $settings['retention_days'] );
		$this->assertSame( A11yFix_Repairs::defaults(), $settings['repairs'] );
	}

	public function test_sanitize_coerces_and_clamps() {
		$clean = A11yFix_Settings::sanitize(
			array(
				'master_enabled' => 'yes',   // Truthy string.
				'scan_enabled'   => null,    // Falsy.
				'scan_max_pages' => '999',
				'retention_days' => '0',
				'repairs'        => array(
					'img_alt'   => 'on',
					'html_lang' => 0,
				),
			)
		);

		$this->assertTrue( $clean['master_enabled'] );
		$this->assertFalse( $clean['scan_enabled'] );
		$this->assertSame( 50, $clean['scan_max_pages'] ); // Clamped from 999.
		$this->assertSame( 7, $clean['retention_days'] );  // Clamped from 0.
		$this->assertTrue( $clean['repairs']['img_alt'] );
		$this->assertFalse( $clean['repairs']['html_lang'] );
	}

	public function test_sanitize_unchecked_repairs_are_false() {
		$clean = A11yFix_Settings::sanitize( array() );

		$this->assertSame( array_fill_keys( array_keys( A11yFix_Repairs::defaults() ), false ), $clean['repairs'] );
	}

	public function test_save_and_load_roundtrip() {
		$saved = A11yFix_Settings::save( array( 'scan_max_pages' => 3 ) );
		$got   = A11yFix_Settings::get();

		$this->assertSame( 3, $got['scan_max_pages'] );
		$this->assertSame( $saved, $got );
	}

	public function test_garbage_input_falls_back_to_defaults() {
		$GLOBALS['__a11yfix_options']['a11yfix_settings'] = 'not an array';
		$settings = A11yFix_Settings::get();

		$this->assertSame( A11yFix_Settings::defaults(), $settings );
	}
}
