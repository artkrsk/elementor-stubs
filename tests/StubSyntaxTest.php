<?php

namespace ElementorStubs\Tests;

use PHPUnit\Framework\TestCase;

class StubSyntaxTest extends TestCase {

	private string $stubsFile;

	protected function setUp(): void {
		$this->stubsFile = __DIR__ . '/../elementor-stubs.php';
	}

	public function testStubFileExists(): void {
		$this->assertFileExists( $this->stubsFile, 'Stub file should exist' );
	}

	public function testStubFileIsReadable(): void {
		$this->assertFileIsReadable( $this->stubsFile, 'Stub file should be readable' );
	}

	public function testStubFileHasValidSyntax(): void {
		$output   = array();
		$exitCode = 0;
		exec( 'php -l ' . escapeshellarg( $this->stubsFile ) . ' 2>&1', $output, $exitCode );

		$this->assertEquals( 0, $exitCode, 'Stub file should have valid PHP syntax: ' . implode( "\n", $output ) );
	}

	public function testElementorVersionConstant(): void {
		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			ELEMENTOR_VERSION,
			'ELEMENTOR_VERSION should be in semantic version format (e.g., 3.33.4)'
		);
	}

	public function testCoreClassesExist(): void {
		$this->assertTrue(
			class_exists( 'Elementor\Plugin' ),
			'Elementor\Plugin class should exist'
		);
		$this->assertTrue(
			class_exists( 'Elementor\Widget_Base' ),
			'Elementor\Widget_Base class should exist'
		);
		$this->assertTrue(
			class_exists( 'Elementor\Core\Base\Document' ),
			'Elementor\Core\Base\Document class should exist'
		);
	}

	public function testElementorProVersionConstant(): void {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->markTestSkipped( 'Elementor Pro stubs not generated' );
		}

		/** @var string $version */
		$version = ELEMENTOR_PRO_VERSION;

		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			$version,
			'ELEMENTOR_PRO_VERSION should be in semantic version format'
		);
	}

	public function testProClassesExist(): void {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->markTestSkipped( 'Elementor Pro stubs not generated' );
		}

		$this->assertTrue(
			class_exists( 'ElementorPro\Plugin' ),
			'ElementorPro\Plugin class should exist'
		);
	}
}
