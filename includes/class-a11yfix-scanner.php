<?php
/**
 * Audit scanner: parses HTML once and runs every registered rule.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the rule set over an HTML string.
 */
class A11yFix_Scanner {

	/** @var A11yFix_Rule[] */
	private $rules;

	/**
	 * Constructor.
	 *
	 * @param A11yFix_Rule[]|null $rules Rule instances; default = full v1 set.
	 */
	public function __construct( array $rules = null ) {
		$this->rules = $rules ? $rules : A11yFix_Scanner::default_rules();
	}

	/**
	 * The v1 rule set.
	 *
	 * @return A11yFix_Rule[]
	 */
	public static function default_rules() {
		return array(
			new A11yFix_Rule_Missing_Alt(),
			new A11yFix_Rule_Heading_Skip(),
			new A11yFix_Rule_Multiple_H1(),
			new A11yFix_Rule_Empty_Link(),
			new A11yFix_Rule_Empty_Button(),
			new A11yFix_Rule_Input_Label(),
			new A11yFix_Rule_Vague_Link(),
			new A11yFix_Rule_Html_Lang(),
			new A11yFix_Rule_Page_Title(),
		);
	}

	/**
	 * Scan an HTML document.
	 *
	 * @param string $html Raw HTML.
	 * @return array Findings list (A11yFix_Finding[]), empty when the page
	 *               cannot be parsed at all (never blocks on garbage).
	 */
	public function scan( $html ) {
		$dom = A11yFix_Parser::load( $html );
		if ( ! $dom ) {
			return array();
		}

		$findings = array();
		foreach ( $this->rules as $rule ) {
			foreach ( $rule->check( $dom, (string) $html ) as $finding ) {
				$findings[] = $finding;
			}
		}

		return $findings;
	}
}
