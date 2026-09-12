<?php

use PHPUnit\Framework\TestCase;

/**
 * Repairs control_name (risky) and iframe_title (risky).
 */
class RepairControlsTest extends TestCase {

	public function test_icon_link_named_from_url_slug() {
		$dom = A11yFix_Parser::load( '<body><a href="/about-team/"><span class="icon"></span></a></body>' );
		$out = ( new A11yFix_Repair_Control_Name() )->apply( $dom );

		$this->assertCount( 1, $out );
		$this->assertSame( 'About team', $dom->getElementsByTagName( 'a' )->item( 0 )->getAttribute( 'aria-label' ) );
	}

	public function test_named_link_untouched() {
		$dom = A11yFix_Parser::load( '<body><a href="/about/">About</a></body>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Control_Name() )->apply( $dom ) );
	}

	public function test_useless_slug_is_skipped() {
		$dom = A11yFix_Parser::load( '<body><a href="/12345/?utm=x"><span></span></a></body>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Control_Name() )->apply( $dom ) );
	}

	public function test_blank_submit_gets_value() {
		$dom = A11yFix_Parser::load( '<body><form><input type="submit"></form></body>' );
		$out = ( new A11yFix_Repair_Control_Name() )->apply( $dom, array( 'submit_label' => 'Отправить' ) );

		$this->assertCount( 1, $out );
		$this->assertSame( 'Отправить', $dom->getElementsByTagName( 'input' )->item( 0 )->getAttribute( 'value' ) );
	}

	public function test_placeholder_becomes_aria_label_when_no_label() {
		$dom = A11yFix_Parser::load( '<body><form><input type="text" name="phone" placeholder="Your phone"></form></body>' );
		$out = ( new A11yFix_Repair_Control_Name() )->apply( $dom );

		$this->assertCount( 1, $out );
		$this->assertSame( 'Your phone', $dom->getElementsByTagName( 'input' )->item( 0 )->getAttribute( 'aria-label' ) );
	}

	public function test_labelled_field_gets_no_placeholder_label() {
		$dom = A11yFix_Parser::load( '<body><form><label for="p">Phone</label><input type="text" id="p" placeholder="Your phone"></form></body>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Control_Name() )->apply( $dom ) );
	}

	public function test_nameless_button_is_left_alone() {
		$dom = A11yFix_Parser::load( '<body><button><span class="bars"></span></button></body>' );
		$out = ( new A11yFix_Repair_Control_Name() )->apply( $dom );

		$this->assertCount( 0, $out ); // No safe name source for content buttons in v1.
		$this->assertFalse( $dom->getElementsByTagName( 'button' )->item( 0 )->hasAttribute( 'aria-label' ) );
	}

	public function test_iframe_title_generic_and_video() {
		$dom = A11yFix_Parser::load( '<body><iframe src="https://www.youtube.com/embed/x"></iframe><iframe src="https://cdn.example.com/widget"></iframe></body>' );
		$out = ( new A11yFix_Repair_Iframe_Title() )->apply( $dom );

		$this->assertCount( 2, $out );
		$this->assertSame( 'Video', $dom->getElementsByTagName( 'iframe' )->item( 0 )->getAttribute( 'title' ) );
		$this->assertSame( 'Embedded content', $dom->getElementsByTagName( 'iframe' )->item( 1 )->getAttribute( 'title' ) );
	}

	public function test_iframe_title_custom_label() {
		$dom = A11yFix_Parser::load( '<body><iframe src="https://x.example/w"></iframe></body>' );
		$out = ( new A11yFix_Repair_Iframe_Title() )->apply( $dom, array( 'iframe_title' => 'Встроенный виджет' ) );

		$this->assertCount( 1, $out );
		$this->assertSame( 'Встроенный виджет', $dom->getElementsByTagName( 'iframe' )->item( 0 )->getAttribute( 'title' ) );
	}

	public function test_titled_and_hidden_iframes_skipped() {
		$dom = A11yFix_Parser::load( '<body><iframe src="https://a/" title="T"></iframe><iframe src="https://b/" aria-hidden="true"></iframe></body>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Iframe_Title() )->apply( $dom ) );
	}
}
