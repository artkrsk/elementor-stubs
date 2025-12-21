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

	/**
	 * Test that PHPDoc class names are fully qualified in generated stubs.
	 *
	 * This verifies that the resolvePhpDocClassNames() post-processor is working correctly.
	 * Unqualified class names in PHPDoc annotations should be resolved to their
	 * fully-qualified counterparts using the original source files' use statements.
	 */
	public function testPhpDocClassNamesAreFullyQualified(): void {
		$stubContent = file_get_contents( $this->stubsFile );
		$this->assertNotFalse( $stubContent, 'Stub file should be readable' );

		// Test case 1: Tab_Base::$parent should use fully-qualified Kit class
		// Before: @var Kit (PHPStan would resolve as Elementor\Core\Kits\Documents\Tabs\Kit - wrong!)
		// After:  @var \Elementor\Core\Kits\Documents\Kit (correct fully-qualified name)
		$this->assertStringContainsString(
			'@var \Elementor\Core\Kits\Documents\Kit',
			$stubContent,
			'Tab_Base::$parent should use fully-qualified Kit class name'
		);

		// Test case 2: Verify unqualified Kit reference doesn't exist in Tab_Base context
		// Match pattern: "namespace Elementor\Core\Kits\Documents\Tabs {" followed by "@var Kit" (without \)
		$pattern = '/namespace\s+Elementor\\\\Core\\\\Kits\\\\Documents\\\\Tabs\s*\{[^}]*@var\s+Kit[^\\\\]/s';
		$this->assertDoesNotMatchRegularExpression(
			$pattern,
			$stubContent,
			'Tab_Base should not have unqualified Kit reference in @var annotation'
		);

		// Test case 3: Verify other common PHPDoc annotations use fully-qualified names
		// Sample check: Look for patterns like "@param ClassName" without leading backslash
		// Allow scalar types but catch class names that should be qualified
		$unqualifiedClassPattern = '/@(var|param|return)\s+(?!array|string|int|float|bool|mixed|void|null|false|true|self|static|parent|callable|iterable|object|resource|never)([A-Z][a-zA-Z_]*[^\\\\|\[\]<>,\s])/';
		$matches                 = array();
		preg_match_all( $unqualifiedClassPattern, $stubContent, $matches );

		// Filter out false positives (e.g., "Array<Type>" which is intentional in some docblocks)
		$genuineUnqualified = array_filter(
			$matches[2],
			function ( $className ) {
				// Allow "Array" as it might be used intentionally in some contexts
				return 'Array' !== $className;
			}
		);

		$this->assertLessThan(
			5,
			count( $genuineUnqualified ),
			'Found ' . count( $genuineUnqualified ) . ' potentially unqualified class names in PHPDoc: ' . implode( ', ', array_unique( $genuineUnqualified ) )
		);
	}
}
