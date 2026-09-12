<?php

use PHPUnit\Framework\TestCase;

/**
 * Rules html_lang and page_title.
 */
class RuleDocumentTest extends TestCase {

	public function test_missing_lang_detected() {
		$html = '<html><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Html_Lang() )->check( $dom, $html );
		$this->assertCount( 1, $found );
		$this->assertSame( 'high', $found[0]->severity );
		$this->assertSame( 'html', $found[0]->locator );
	}

	public function test_empty_lang_detected() {
		$html = '<html lang=""><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$this->assertCount( 1, ( new A11yFix_Rule_Html_Lang() )->check( $dom, $html ) );
	}

	public function test_present_lang_passes() {
		$html = '<html lang="ru-RU"><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$this->assertCount( 0, ( new A11yFix_Rule_Html_Lang() )->check( $dom, $html ) );
	}

	public function test_missing_title_detected() {
		$html = '<html lang="en"><head><meta charset="utf-8"></head><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Page_Title() )->check( $dom, $html );
		$this->assertCount( 1, $found );
		$this->assertStringContainsString( 'No <title>', $found[0]->message );
	}

	public function test_empty_title_detected() {
		$html = '<html lang="en"><head><title>   </title></head><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$found = ( new A11yFix_Rule_Page_Title() )->check( $dom, $html );
		$this->assertCount( 1, $found );
		$this->assertStringContainsString( 'empty', $found[0]->message );
	}

	public function test_present_title_passes() {
		$html = '<html lang="en"><head><title>Hello</title></head><body>x</body></html>';
		$dom  = A11yFix_Parser::load( $html );

		$this->assertCount( 0, ( new A11yFix_Rule_Page_Title() )->check( $dom, $html ) );
	}
}
