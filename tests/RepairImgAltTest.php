<?php

use PHPUnit\Framework\TestCase;

/**
 * Repair img_alt.
 */
class RepairImgAltTest extends TestCase {

	private function apply( $html, $lookup ) {
		$dom = A11yFix_Parser::load( $html );

		return array(
			'dom'     => $dom,
			'changes' => ( new A11yFix_Repair_Img_Alt() )->apply( $dom, array( 'alt_lookup' => $lookup ) ),
		);
	}

	public function test_fills_missing_alt_from_lookup() {
		$r = $this->apply( '<body><img src="/img/team.jpg"></body>', static function ( $src ) {
			return 'Our team at work';
		} );

		$this->assertCount( 1, $r['changes'] );
		$this->assertSame( 'Our team at work', $r['dom']->getElementsByTagName( 'img' )->item( 0 )->getAttribute( 'alt' ) );
		$this->assertSame( 'alt', $r['changes'][0]->attr );
		$this->assertSame( '', $r['changes'][0]->before );
		$this->assertStringContainsString( 'team.jpg', $r['changes'][0]->target );
	}

	public function test_fills_empty_alt_attribute() {
		$r = $this->apply( '<body><img src="/img/a.png" alt=""></body>', static function () {
			return 'X';
		} );

		$this->assertCount( 1, $r['changes'] );
	}

	public function test_never_touches_existing_alt() {
		$r = $this->apply( '<body><img src="/img/a.png" alt="Original"></body>', static function () {
			return 'Overwrite attempt';
		} );

		$this->assertCount( 0, $r['changes'] );
		$this->assertSame( 'Original', $r['dom']->getElementsByTagName( 'img' )->item( 0 )->getAttribute( 'alt' ) );
	}

	public function test_skips_when_lookup_has_nothing() {
		$r = $this->apply( '<body><img src="/img/unknown.png"></body>', static function () {
			return '';
		} );

		$this->assertCount( 0, $r['changes'] );
		$this->assertFalse( $r['dom']->getElementsByTagName( 'img' )->item( 0 )->hasAttribute( 'alt' ) );
	}

	public function test_skips_decorative_images() {
		$html = '<body><img src="/img/a.png" role="presentation"><img src="/img/b.png" aria-hidden="true"></body>';
		$r    = $this->apply( $html, static function () {
			return 'X';
		} );

		$this->assertCount( 0, $r['changes'] );
	}

	public function test_lookup_receives_src() {
		$seen = array();
		$this->apply( '<body><img src="/img/one.jpg"></body>', static function ( $src ) use ( &$seen ) {
			$seen[] = $src;

			return '';
		} );

		$this->assertSame( array( '/img/one.jpg' ), $seen );
	}
}
