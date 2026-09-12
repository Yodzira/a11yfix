<?php

use PHPUnit\Framework\TestCase;

/**
 * Parser: UTF-8 round-trips, malformed HTML recovery, fragments.
 */
class ParserTest extends TestCase {

	public function test_multibyte_survives_roundtrip_without_entity_bloat() {
		$html  = '<html lang="ru"><body><h1>Услуги — 中文 🚧</h1><p>Цена «от 300 000 ₽»</p></body></html>';
		$dom   = A11yFix_Parser::load( $html );
		$out   = A11yFix_Parser::save( $dom );

		$this->assertStringContainsString( 'Услуги — 中文 🚧', $out );
		$this->assertStringContainsString( 'Цена «от 300 000 ₽»', $out );
		$this->assertStringNotContainsString( '&#', $out );
		$this->assertStringNotContainsString( '&Uacute', $out );
	}

	public function test_doctype_is_preserved() {
		$html = '<!DOCTYPE html><html lang="en"><head><title>T</title></head><body><p>x</p></body></html>';
		$out  = A11yFix_Parser::save( A11yFix_Parser::load( $html ) );

		$this->assertStringContainsString( '<!DOCTYPE html>', $out );
	}

	public function test_malformed_html_still_parses() {
		$dom = A11yFix_Parser::load( '<html><body><p>one<div>two<img src="a.png"></p>' );
		$this->assertNotNull( $dom );
		$this->assertGreaterThan( 0, $dom->getElementsByTagName( 'img' )->length );
	}

	public function test_script_content_survives_roundtrip() {
		$html = '<html lang="en"><body><script>var a = "<div class=\"x\">"; if (1 < 2) { alert("hi"); }</script></body></html>';
		$out  = A11yFix_Parser::save( A11yFix_Parser::load( $html ) );

		$this->assertStringContainsString( 'alert("hi")', $out );
		$this->assertStringContainsString( '<div class=\"x\">', $out );
	}

	public function test_text_of_collapses_whitespace() {
		$dom = A11yFix_Parser::load( '<body><p>  a
			b&nbsp;&nbsp;c </p></body>' );
		$this->assertSame( 'a b c', A11yFix_Parser::text_of( $dom->getElementsByTagName( 'p' )->item( 0 ) ) );
	}

	public function test_fragment_strips_and_caps() {
		$this->assertSame( 'a b', A11yFix_Parser::fragment( '  <b>a</b>' . str_repeat( ' ', 5 ) . 'b  ' ) );
		$this->assertSame( 10, strlen( A11yFix_Parser::fragment( str_repeat( 'x', 100 ), 10 ) ) );
		$this->assertSame( 'привет', A11yFix_Parser::fragment( '<span>привет</span>' ) );
	}
}
