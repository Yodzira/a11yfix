<?php

use PHPUnit\Framework\TestCase;

/**
 * The full scanner: rule ids, severities and cross-rule behavior on a
 * realistic page.
 */
class ScannerRulesetTest extends TestCase {

	public function test_rule_ids_are_unique_and_documented() {
		$ids = array_map(
			static function ( $rule ) {
				return $rule->id();
			},
			A11yFix_Scanner::default_rules()
		);

		$this->assertCount( 9, $ids );
		$this->assertCount( count( array_unique( $ids ) ), $ids );
		foreach ( A11yFix_Scanner::default_rules() as $rule ) {
			$this->assertContains( $rule->severity(), array( 'high', 'medium', 'low' ), $rule->id() );
			$this->assertNotSame( '', $rule->summary(), $rule->id() );
			$this->assertNotSame( '', $rule->hint(), $rule->id() );
		}
	}

	public function test_realistic_page_produces_expected_findings() {
		$html    = file_get_contents( __DIR__ . '/../fixtures/gm/theme-ish.html' );
		$scanner = new A11yFix_Scanner();

		$findings = $scanner->scan( $html );
		$by_rule  = array_count_values(
			array_map(
				static function ( $finding ) {
					return $finding->rule;
				},
				$findings
			)
		);

		$this->assertSame( 2, isset( $by_rule['missing_alt'] ) ? $by_rule['missing_alt'] : 0 );  // logo + office.png.
		$this->assertSame( 1, isset( $by_rule['heading_skip'] ) ? $by_rule['heading_skip'] : 0 );  // h2 -> h4.
		$this->assertSame( 2, isset( $by_rule['empty_link'] ) ? $by_rule['empty_link'] : 0 );  // icon link + logo-image link without alt.
		$this->assertSame( 1, isset( $by_rule['empty_button'] ) ? $by_rule['empty_button'] : 0 );  // blank submit.
		$this->assertSame( 1, isset( $by_rule['input_no_label'] ) ? $by_rule['input_no_label'] : 0 );  // placeholder-only email.
		$this->assertSame( 1, isset( $by_rule['vague_link'] ) ? $by_rule['vague_link'] : 0 );  // "read more".
		$this->assertSame( 1, isset( $by_rule['html_lang'] ) ? $by_rule['html_lang'] : 0 );
		$this->assertArrayNotHasKey( 'page_title', $by_rule ); // Title present.
		$this->assertArrayNotHasKey( 'multiple_h1', $by_rule ); // Single h1.
	}

	public function test_findings_survive_malformed_input() {
		$scanner = new A11yFix_Scanner();
		$findings = $scanner->scan( 'totally not html <<<><>>' );

		$this->assertIsArray( $findings );
	}
}
