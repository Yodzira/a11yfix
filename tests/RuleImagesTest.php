<?php

use PHPUnit\Framework\TestCase;

/**
 * Rule missing_alt.
 */
class RuleImagesTest extends TestCase {

	private function findings( $html ) {
		$dom = A11yFix_Parser::load( $html );

		return ( new A11yFix_Rule_Missing_Alt() )->check( $dom, $html );
	}

	public function test_missing_alt_is_found() {
		$found = $this->findings( '<body><img src="/img/team.jpg"></body>' );
		$this->assertCount( 1, $found );
		$this->assertSame( 'missing_alt', $found[0]->rule );
		$this->assertSame( 'high', $found[0]->severity );
		$this->assertStringContainsString( 'team.jpg', $found[0]->locator );
		$this->assertSame( 'team.jpg', $found[0]->fragment );
	}

	public function test_empty_alt_is_not_reported() {
		$this->assertCount( 0, $this->findings( '<body><img src="/a.png" alt=""></body>' ) );
		$this->assertCount( 0, $this->findings( '<body><img src="/a.png" alt="Team"></body>' ) );
	}

	public function test_decorative_images_are_skipped() {
		$this->assertCount( 0, $this->findings( '<body><img src="/a.png" role="presentation"></body>' ) );
		$this->assertCount( 0, $this->findings( '<body><img src="/a.png" aria-hidden="true"></body>' ) );
	}

	public function test_mixed_batch_reports_only_offenders() {
		$found = $this->findings( '<body><img src="/a.png" alt="A"><img src="/b.png"><img src="/c.png" alt="C"></body>' );
		$this->assertCount( 1, $found );
		$this->assertStringContainsString( 'b.png', $found[0]->locator );
	}
}
