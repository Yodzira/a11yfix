<?php

use PHPUnit\Framework\TestCase;

/**
 * Registry defaults and the preview pipeline (identity guarantee).
 */
class RegistryPreviewTest extends TestCase {

	public function test_defaults_safe_on_risky_off() {
		$defaults = A11yFix_Repairs::defaults();

		$this->assertTrue( $defaults['img_alt'] );
		$this->assertTrue( $defaults['html_lang'] );
		$this->assertTrue( $defaults['table_scope'] );
		$this->assertFalse( $defaults['control_name'] );
		$this->assertFalse( $defaults['iframe_title'] );
	}

	public function test_enabled_respects_map() {
		$enabled = A11yFix_Repairs::enabled(
			array(
				'img_alt'    => false,
				'html_lang'  => true,
				'table_scope' => false,
				'control_name' => true,
			)
		);

		$this->assertSame( array( 'html_lang', 'control_name' ), array_map( static function ( $r ) {
			return $r->id();
		}, $enabled ) );
	}

	public function test_preview_without_changes_returns_original_bytes() {
		$html = '<html lang="en"><body><h1>Fine</h1><img src="/a.jpg" alt="A"></body></html>';

		$result = A11yFix_Preview::run( $html, A11yFix_Repairs::defaults() );

		$this->assertSame( $html, $result['html'] );
		$this->assertCount( 0, $result['changes'] );
	}

	public function test_preview_applies_changes_and_records_them() {
		$html = '<html><body><img src="/a.jpg"></body></html>';

		$result = A11yFix_Preview::run(
			$html,
			array( 'img_alt' => true ),
			array( 'alt_lookup' => static function () {
				return 'Logo';
			} )
		);

		$this->assertCount( 1, $result['changes'] );
		$this->assertNotSame( $html, $result['html'] );
		$this->assertStringContainsString( 'alt="Logo"', $result['html'] );
		$this->assertSame( 'img_alt', $result['changes'][0]->repair );
	}

	public function test_preview_garbage_html_returns_original() {
		$html = '<html><body><p>broken';
		$this->assertSame( $html, A11yFix_Preview::run( $html, A11yFix_Repairs::defaults() )['html'] );
	}
}
