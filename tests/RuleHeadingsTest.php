<?php

use PHPUnit\Framework\TestCase;

/**
 * Rules heading_skip and multiple_h1.
 */
class RuleHeadingsTest extends TestCase {

	public function test_skip_is_detected() {
		$html = '<body><h1>A</h1><h2>B</h2><h4>D</h4></body>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Heading_Skip() )->check( $dom, $html );
		$this->assertCount( 1, $found );
		$this->assertSame( 'h4', $found[0]->locator );
		$this->assertStringContainsString( 'h2 to h4', $found[0]->message );
		$this->assertSame( 'D', $found[0]->fragment );
	}

	public function test_sequential_levels_pass() {
		$html = '<body><h1>A</h1><h2>B</h2><h3>C</h3><h2>D</h2><h3>E</h3></body>';
		$dom  = A11yFix_Parser::load( $html );

		$this->assertCount( 0, ( new A11yFix_Rule_Heading_Skip() )->check( $dom, $html ) );
	}

	public function test_jump_back_up_is_not_a_violation() {
		$html = '<body><h1>A</h1><h3>C</h3><h2>B</h2></body>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Heading_Skip() )->check( $dom, $html );
		$this->assertCount( 1, $found ); // Only h1->h3.
		$this->assertSame( 'h3', $found[0]->locator );
	}

	public function test_multiple_h1_detected() {
		$html = '<body><h1>Main</h1><article><h1>Post</h1></article></body>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Multiple_H1() )->check( $dom, $html );
		$this->assertCount( 1, $found );
		$this->assertStringContainsString( '2 h1', $found[0]->message );
		$this->assertStringContainsString( 'Main', $found[0]->fragment );
		$this->assertStringContainsString( 'Post', $found[0]->fragment );
	}

	public function test_single_h1_passes() {
		$html = '<body><h1>Main</h1></body>';
		$dom  = A11yFix_Parser::load( $html );

		$this->assertCount( 0, ( new A11yFix_Rule_Multiple_H1() )->check( $dom, $html ) );
	}
}
