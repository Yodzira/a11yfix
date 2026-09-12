<?php

use PHPUnit\Framework\TestCase;

/**
 * Repairs html_lang and table_scope.
 */
class RepairDocTest extends TestCase {

	public function test_html_lang_added() {
		$dom = A11yFix_Parser::load( '<html><body>x</body></html>' );
		$out = ( new A11yFix_Repair_Html_Lang() )->apply( $dom, array( 'lang' => 'ru-RU' ) );

		$this->assertCount( 1, $out );
		$this->assertSame( 'ru-RU', $dom->documentElement->getAttribute( 'lang' ) );
	}

	public function test_html_lang_not_overwritten() {
		$dom = A11yFix_Parser::load( '<html lang="de"><body>x</body></html>' );
		$out = ( new A11yFix_Repair_Html_Lang() )->apply( $dom, array( 'lang' => 'ru-RU' ) );

		$this->assertCount( 0, $out );
		$this->assertSame( 'de', $dom->documentElement->getAttribute( 'lang' ) );
	}

	public function test_html_lang_skipped_without_ctx() {
		$dom = A11yFix_Parser::load( '<html><body>x</body></html>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Html_Lang() )->apply( $dom ) );
	}

	public function test_thead_th_gets_col_scope() {
		$dom = A11yFix_Parser::load( '<body><table><thead><tr><th>A</th><th>B</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table></body>' );
		$out = ( new A11yFix_Repair_Table_Scope() )->apply( $dom );

		$this->assertCount( 2, $out );
		$ths = $dom->getElementsByTagName( 'th' );
		$this->assertSame( 'col', $ths->item( 0 )->getAttribute( 'scope' ) );
		$this->assertSame( 'col', $ths->item( 1 )->getAttribute( 'scope' ) );
	}

	public function test_row_header_gets_row_scope() {
		$dom = A11yFix_Parser::load( '<body><table><tr><th>H</th></tr><tr><th>RowA</th><td>1</td></tr></table></body>' );
		$out = ( new A11yFix_Repair_Table_Scope() )->apply( $dom );

		$this->assertCount( 2, $out );
		$this->assertSame( 'col', $dom->getElementsByTagName( 'th' )->item( 0 )->getAttribute( 'scope' ) );
		$this->assertSame( 'row', $dom->getElementsByTagName( 'th' )->item( 1 )->getAttribute( 'scope' ) );
	}

	public function test_existing_scope_preserved() {
		$dom = A11yFix_Parser::load( '<body><table><tr><th scope="row">H</th></tr></table></body>' );
		$out = ( new A11yFix_Repair_Table_Scope() )->apply( $dom );

		$this->assertCount( 0, $out );
		$this->assertSame( 'row', $dom->getElementsByTagName( 'th' )->item( 0 )->getAttribute( 'scope' ) );
	}

	public function test_data_cells_untouched() {
		$dom = A11yFix_Parser::load( '<body><table><tr><td>A</td><td>B</td></tr></table></body>' );

		$this->assertCount( 0, ( new A11yFix_Repair_Table_Scope() )->apply( $dom ) );
	}
}
