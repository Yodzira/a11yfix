<?php

use PHPUnit\Framework\TestCase;

/**
 * Rules empty_link, empty_button, input_no_label, vague_link.
 */
class RuleControlsTest extends TestCase {

	private function run_rule( $rule_class, $html ) {
		$dom = A11yFix_Parser::load( $html );

		return ( new $rule_class() )->check( $dom, $html );
	}

	public function test_link_without_name_is_found() {
		$found = $this->run_rule( A11yFix_Rule_Empty_Link::class, '<body><a href="/en/"><span class="icon"></span></a></body>' );
		$this->assertCount( 1, $found );
		$this->assertSame( 'high', $found[0]->severity );
		$this->assertStringContainsString( '/en/', $found[0]->locator );
	}

	public function test_anchor_without_href_is_ignored() {
		$this->assertCount( 0, $this->run_rule( A11yFix_Rule_Empty_Link::class, '<body><a name="top"></a></body>' ) );
	}

	public function test_named_links_pass() {
		$html  = '<body>'
			. '<a href="/a/">About us</a>'
			. '<a href="/b/" aria-label="Search"></a>'
			. '<a href="/c/" title="Contacts"></a>'
			. '<a href="/d/"><img src="x.png" alt="Chart"></a>'
			. '<a href="/e/" aria-labelledby="lbl"></a><span id="lbl">Labeled</span>'
			. '</body>';

		$this->assertCount( 0, $this->run_rule( A11yFix_Rule_Empty_Link::class, $html ) );
	}

	public function test_empty_button_is_found() {
		$found = $this->run_rule( A11yFix_Rule_Empty_Button::class, '<body><button type="button"><span class="bars"></span></button></body>' );
		$this->assertCount( 1, $found );
	}

	public function test_submit_without_value_is_found() {
		$found = $this->run_rule( A11yFix_Rule_Empty_Button::class, '<body><form><input type="submit"></form></body>' );
		$this->assertCount( 1, $found );
		$this->assertStringContainsString( 'input[type=submit]', $found[0]->locator );
	}

	public function test_named_buttons_pass() {
		$html = '<body><button>Go</button><button aria-label="Menu"></button><input type="submit" value="Send"></body>';

		$this->assertCount( 0, $this->run_rule( A11yFix_Rule_Empty_Button::class, $html ) );
	}

	public function test_field_without_label_is_found() {
		$found = $this->run_rule( A11yFix_Rule_Input_Label::class, '<body><form><input type="text" name="phone" placeholder="+7 ..."></form></body>' );
		$this->assertCount( 1, $found );
		$this->assertSame( 'medium', $found[0]->severity );
		$this->assertStringContainsString( 'phone', $found[0]->locator );
	}

	public function test_labelled_fields_pass() {
		$html = '<body><form>'
			. '<label for="a">Name</label><input type="text" id="a">'
			. '<label>Mail <input type="email" name="m"></label>'
			. '<input type="search" name="q" aria-label="Search">'
			. '<input type="search" name="w" title="Search">'
			. '<select name="s" aria-label="Topic"><option>1</option></select>'
			. '<textarea name="t" aria-label="Comment"></textarea>'
			. '<input type="hidden" name="h">'
			. '<input type="submit" value="Go">'
			. '</form></body>';

		$this->assertCount( 0, $this->run_rule( A11yFix_Rule_Input_Label::class, $html ) );
	}

	public function test_vague_link_text_is_found() {
		$found = $this->run_rule( A11yFix_Rule_Vague_Link::class, '<body><a href="/p/">Read More</a></body>' );
		$this->assertCount( 1, $found );
		$this->assertSame( 'low', $found[0]->severity );
	}

	public function test_russian_vague_link_is_found() {
		$this->assertCount( 1, $this->run_rule( A11yFix_Rule_Vague_Link::class, '<body><a href="/p/">Подробнее</a></body>' ) );
	}

	public function test_specific_links_pass() {
		$html = '<body><a href="/p/">Read more: pricing</a><a href="/p/" aria-label="Read more about pricing">Read more</a><a href="/p/">Our portfolio</a></body>';

		$this->assertCount( 0, $this->run_rule( A11yFix_Rule_Vague_Link::class, $html ) );
	}
}
