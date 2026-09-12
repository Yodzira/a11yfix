<?php

use PHPUnit\Framework\TestCase;

/**
 * SSRF guard for the self-crawl scanner.
 */
class GuardTest extends TestCase {

	private function check( $url, $home = 'https://example.com/' ) {
		return A11yFix_Guard::url_allowed( $url, $home );
	}

	public function test_same_host_http_and_https_allowed() {
		$this->assertTrue( $this->check( 'https://example.com/' )[0] );
		$this->assertTrue( $this->check( 'https://example.com/about/', 'http://example.com' )[0] );
		$this->assertTrue( $this->check( 'http://example.com/page?x=1' )[0] );
	}

	public function test_cross_host_rejected() {
		$this->assertFalse( $this->check( 'https://evil.com/' )[0] );
		$this->assertFalse( $this->check( 'https://sub.example.com/' )[0] );
		$this->assertFalse( $this->check( 'https://example.com.evil.com/' )[0] );
	}

	public function test_scheme_and_port_restricted() {
		$this->assertFalse( $this->check( 'javascript:alert(1)' )[0] );
		$this->assertFalse( $this->check( 'file:///etc/passwd' )[0] );
		$this->assertFalse( $this->check( 'ftp://example.com/' )[0] );
		$this->assertFalse( $this->check( 'https://example.com:6379/' )[0] ); // Non-web port.
		$this->assertTrue( $this->check( 'https://example.com:443/' )[0] );
		$this->assertTrue( $this->check( 'http://example.com:8080/' )[0] ); // Web port.
	}

	public function test_userinfo_rejected() {
		$this->assertFalse( $this->check( 'https://user:pass@example.com/' )[0] );
	}

	public function test_own_localhost_host_is_allowed_when_configured() {
		// Dev installations run on localhost; the site's own host is always trusted.
		$this->assertTrue( $this->check( 'http://localhost/', 'http://localhost/' )[0] );
		$this->assertTrue( $this->check( 'http://mysite.test/', 'http://mysite.test/' )[0] );
	}

	public function test_internal_host_of_another_site_rejected() {
		$this->assertFalse( $this->check( 'http://localhost/' )[0] );
		$this->assertFalse( $this->check( 'http://mysite.local/' )[0] );
	}

	public function test_private_ip_hosts_rejected() {
		$this->assertFalse( $this->check( 'http://127.0.0.1/', 'http://127.0.0.1/' )[0] );
		$this->assertFalse( $this->check( 'http://10.0.0.5/', 'http://10.0.0.5/' )[0] );
		$this->assertFalse( $this->check( 'http://192.168.1.10/', 'http://192.168.1.10/' )[0] );
		$this->assertFalse( $this->check( 'http://172.16.0.9/', 'http://172.16.0.9/' )[0] );
		$this->assertFalse( $this->check( 'http://169.254.169.254/', 'http://169.254.169.254/' )[0] );
		$this->assertFalse( $this->check( 'http://[::1]/', 'http://[::1]/' )[0] );
		$this->assertFalse( $this->check( 'http://[fd12::1]/', 'http://[fd12::1]/' )[0] );
		$this->assertFalse( $this->check( 'http://[::ffff:10.0.0.1]/', 'http://[::ffff:10.0.0.1]/' )[0] );
	}

	public function test_garbage_rejected() {
		$this->assertFalse( $this->check( '' )[0] );
		$this->assertFalse( $this->check( 'not a url' )[0] );
		$this->assertFalse( $this->check( '/relative/only' )[0] );
	}
}
