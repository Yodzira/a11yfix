<?php

use PHPUnit\Framework\TestCase;

/**
 * Golden Master: on every fixture, all repairs together may only add
 * allowlisted attributes — structure, text and everything else must be
 * byte-equal through the DOM.
 */
class GoldenMasterTest extends TestCase {

	/**
	 * @dataProvider fixturesProvider
	 *
	 * @param string $path Fixture path.
	 * @param string $name Fixture name.
	 */
	public function test_no_unexpected_changes( $path, $name ) {
		$before = file_get_contents( $path );

		$result = A11yFix_Preview::run(
			$before,
			array_fill_keys( array_keys( A11yFix_Repairs::defaults() ), true ), // All repairs, even risky.
			array(
				'lang'         => 'ru-RU',
				'submit_label' => 'Submit',
				'iframe_title' => 'Embedded content',
				'alt_lookup'   => static function ( $src ) {
					$map = array(
						'/wp-content/uploads/2026/08/office.png' => 'Our office',
						'/img/skhema.png'                        => 'Схема дома',
					);
					if ( '/img/pic.jpg' === $src ) {
						return 'תמונה';
					}

					return isset( $map[ $src ] ) ? $map[ $src ] : '';
				},
			)
		);

		$violations = A11yFix_GoldenMaster::compare( $before, $result['html'] );

		$this->assertSame( array(), $violations, "Fixture {$name} changed outside the allowlist:\n" . implode( "\n", $violations ) );
	}

	public function fixturesProvider() {
		$files = glob( __DIR__ . '/../fixtures/gm/*.html' );
		$out   = array();
		foreach ( $files as $file ) {
			$out[ basename( $file, '.html' ) ] = array( $file, basename( $file, '.html' ) );
		}

		return $out;
	}

	public function test_expected_changes_actually_happen() {
		$before = file_get_contents( __DIR__ . '/../fixtures/gm/theme-ish.html' );

		$result = A11yFix_Preview::run(
			$before,
			array_fill_keys( array_keys( A11yFix_Repairs::defaults() ), true ),
			array(
				'lang'         => 'en-US',
				'submit_label' => 'Submit',
				'iframe_title' => 'Embedded content',
				'alt_lookup'   => static function ( $src ) {
					return '/wp-content/uploads/2026/08/office.png' === $src ? 'Our office' : '';
				},
			)
		);

		$after = $result['html'];
		$this->assertStringContainsString( 'lang="en-US"', $after, 'html lang added' );
		$this->assertStringContainsString( 'alt="Our office"', $after, 'alt added from lookup' );
		$this->assertStringContainsString( 'scope="col"', $after, 'col scope added' );
		$this->assertStringContainsString( 'scope="row"', $after, 'row scope added' );
		$this->assertStringContainsString( 'title="Video"', $after, 'youtube iframe titled' );
		$this->assertStringContainsString( 'value="Submit"', $after, 'blank submit labelled' );
		$this->assertStringContainsString( 'aria-label="Your e-mail"', $after, 'placeholder aria-label added' );
		$this->assertStringContainsString( 'aria-label="Contacts"', $after, 'icon link named from slug' );

		// The planted h1->h4 skip must NOT be auto-"fixed" (report-only).
		$this->assertStringContainsString( '<h4>', $after, 'heading structure untouched by repairs' );
	}

	public function test_comparator_catches_outside_tampering() {
		$before = '<html lang="en"><body><div class="a"><p>Keep</p></div></body></html>';
		$after  = '<html lang="en"><body><div class="changed"><p>Keep</p></div></body></html>';

		$violations = A11yFix_GoldenMaster::compare( $before, $after );
		$this->assertNotEmpty( $violations );
		$this->assertStringContainsString( 'div@class', $violations[0] );
	}
}
