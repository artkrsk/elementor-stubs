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

	/**
	 * Test that ElementorDeps namespace is properly removed from stubs.
	 *
	 * Verifies removeElementorDepsNamespace() post-processor is working correctly.
	 * Note: Some ElementorDeps classes are intentionally added back via getClassAliases()
	 * for Twig compatibility (LoaderInterface, Source).
	 */
	public function testElementorDepsNamespaceIsAbsent(): void {
		$stubContent = file_get_contents( $this->stubsFile );
		$this->assertNotFalse( $stubContent, 'Stub file should be readable' );

		// Count occurrences - should only be the intentional Twig stubs.
		$matches = array();
		preg_match_all( '/namespace\s+ElementorDeps(?!\\\\Twig)/', $stubContent, $matches );

		$this->assertCount(
			0,
			$matches[0],
			'ElementorDeps namespace (except Twig) should not exist in stubs'
		);
	}

	/**
	 * Test that ElementorProDeps namespace is properly removed from stubs.
	 */
	public function testElementorProDepsNamespaceIsAbsent(): void {
		$stubContent = file_get_contents( $this->stubsFile );
		$this->assertNotFalse( $stubContent, 'Stub file should be readable' );

		$this->assertStringNotContainsString(
			'namespace ElementorProDeps',
			$stubContent,
			'ElementorProDeps namespace should not exist in stubs'
		);
	}

	/**
	 * Test that stray code statements are properly removed from stubs.
	 *
	 * Verifies removeStrayCodeStatements() post-processor is working.
	 * These patterns indicate code outside class/function context.
	 */
	public function testNoStrayCodeStatements(): void {
		$stubContent = file_get_contents( $this->stubsFile );
		$this->assertNotFalse( $stubContent, 'Stub file should be readable' );

		// Pattern: $variable = ... $this->method() at namespace level (outside class).
		$this->assertDoesNotMatchRegularExpression(
			'/^\s*\$\w+\s*=.*\$this->/m',
			$stubContent,
			'Should not have stray $this references at namespace level'
		);

		// Pattern: $variable = apply_filters() at top level.
		$this->assertDoesNotMatchRegularExpression(
			'/^\s*\$\w+\s*=\s*apply_filters\(/m',
			$stubContent,
			'Should not have stray apply_filters calls at namespace level'
		);
	}

	public function testElementorVersionConstant(): void {
		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			ELEMENTOR_VERSION,
			'ELEMENTOR_VERSION should be in semantic version format (e.g., 3.33.4)'
		);
	}

	/**
	 * Test that all standard Elementor constants are defined.
	 *
	 * Verifies addSelfContainedConstants() creates all expected constants.
	 */
	public function testAllElementorConstantsExist(): void {
		$requiredConstants = array(
			'ELEMENTOR_VERSION',
			'ELEMENTOR__FILE__',
			'ELEMENTOR_PLUGIN_BASE',
			'ELEMENTOR_PATH',
			'ELEMENTOR_URL',
			'ELEMENTOR_ASSETS_PATH',
			'ELEMENTOR_ASSETS_URL',
		);

		foreach ( $requiredConstants as $constant ) {
			$this->assertTrue(
				defined( $constant ),
				"$constant should be defined"
			);
		}
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

	/**
	 * Test that essential manager classes exist in stubs.
	 */
	public function testCoreManagerClassesExist(): void {
		$managerClasses = array(
			'Elementor\Controls_Manager'         => 'Controls management',
			'Elementor\Elements_Manager'         => 'Elements management',
			'Elementor\Core\Documents_Manager'   => 'Documents management',
			'Elementor\Core\DynamicTags\Manager' => 'Dynamic tags',
			'Elementor\Core\Breakpoints\Manager' => 'Responsive breakpoints',
			'Elementor\Core\Experiments\Manager' => 'Experiments/features',
			'Elementor\Core\Kits\Manager'        => 'Global styles/kits',
		);

		foreach ( $managerClasses as $class => $description ) {
			$this->assertTrue(
				class_exists( $class ),
				"$class ($description) should exist"
			);
		}
	}

	/**
	 * Test that essential base classes for extending exist in stubs.
	 */
	public function testBaseClassesExist(): void {
		$baseClasses = array(
			'Elementor\Widget_Base'           => 'Base for widgets',
			'Elementor\Controls_Stack'        => 'Base with controls',
			'Elementor\Core\Base\Base_Object' => 'Settings handling',
			'Elementor\Core\Base\Document'    => 'Document base',
			'Elementor\Core\Base\Module'      => 'Module base',
			'Elementor\Element_Base'          => 'Element base',
			'Elementor\Skin_Base'             => 'Skin base',
		);

		foreach ( $baseClasses as $class => $description ) {
			$this->assertTrue(
				class_exists( $class ),
				"$class ($description) should exist"
			);
		}
	}

	/**
	 * Test that backwards-compatible class aliases are properly defined.
	 *
	 * Verifies getClassAliases() output is included in stubs.
	 */
	public function testBackwardsCompatibilityAliasesExist(): void {
		// Alias: Elementor\Documents_Manager extends Elementor\Core\Documents_Manager.
		$this->assertTrue(
			class_exists( 'Elementor\Documents_Manager' ),
			'Elementor\Documents_Manager alias should exist'
		);
		$this->assertTrue(
			is_subclass_of( 'Elementor\Documents_Manager', 'Elementor\Core\Documents_Manager' ),
			'Elementor\Documents_Manager should extend Elementor\Core\Documents_Manager'
		);

		// Alias: Elementor\Experiments_Manager extends Elementor\Core\Experiments\Manager.
		$this->assertTrue(
			class_exists( 'Elementor\Experiments_Manager' ),
			'Elementor\Experiments_Manager alias should exist'
		);
		$this->assertTrue(
			is_subclass_of( 'Elementor\Experiments_Manager', 'Elementor\Core\Experiments\Manager' ),
			'Elementor\Experiments_Manager should extend Elementor\Core\Experiments\Manager'
		);

		// Alias: Elementor\Breakpoints_Manager extends Elementor\Core\Breakpoints\Manager.
		$this->assertTrue(
			class_exists( 'Elementor\Breakpoints_Manager' ),
			'Elementor\Breakpoints_Manager alias should exist'
		);
		$this->assertTrue(
			is_subclass_of( 'Elementor\Breakpoints_Manager', 'Elementor\Core\Breakpoints\Manager' ),
			'Elementor\Breakpoints_Manager should extend Elementor\Core\Breakpoints\Manager'
		);

		// Alias: Elementor\Core\Document extends Elementor\Core\Base\Document.
		$this->assertTrue(
			class_exists( 'Elementor\Core\Document' ),
			'Elementor\Core\Document alias should exist'
		);
		$this->assertTrue(
			is_subclass_of( 'Elementor\Core\Document', 'Elementor\Core\Base\Document' ),
			'Elementor\Core\Document should extend Elementor\Core\Base\Document'
		);
	}

	/**
	 * Test that Twig compatibility stubs exist for ElementorDeps.
	 *
	 * These are intentionally added by getClassAliases() to satisfy
	 * type hints in Elementor code that reference ElementorDeps\Twig classes.
	 */
	public function testTwigCompatibilityStubsExist(): void {
		$this->assertTrue(
			interface_exists( 'ElementorDeps\Twig\Loader\LoaderInterface' ),
			'ElementorDeps\Twig\Loader\LoaderInterface should exist'
		);

		$this->assertTrue(
			class_exists( 'ElementorDeps\Twig\Source' ),
			'ElementorDeps\Twig\Source should exist'
		);
	}

	/**
	 * Test that key inheritance chains are correctly defined.
	 */
	public function testClassInheritanceChain(): void {
		// Widget_Base inherits from Element_Base which inherits from Controls_Stack.
		$this->assertTrue(
			is_subclass_of( 'Elementor\Widget_Base', 'Elementor\Element_Base' ),
			'Widget_Base should extend Element_Base'
		);

		$this->assertTrue(
			is_subclass_of( 'Elementor\Element_Base', 'Elementor\Controls_Stack' ),
			'Element_Base should extend Controls_Stack'
		);

		$this->assertTrue(
			is_subclass_of( 'Elementor\Controls_Stack', 'Elementor\Core\Base\Base_Object' ),
			'Controls_Stack should extend Base_Object'
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

	/**
	 * Test that all Elementor Pro constants are defined when Pro stubs are present.
	 */
	public function testAllElementorProConstantsExist(): void {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->markTestSkipped( 'Elementor Pro stubs not generated' );
		}

		$requiredConstants = array(
			'ELEMENTOR_PRO_VERSION',
			'ELEMENTOR_PRO__FILE__',
			'ELEMENTOR_PRO_PLUGIN_BASE',
			'ELEMENTOR_PRO_PATH',
			'ELEMENTOR_PRO_URL',
			'ELEMENTOR_PRO_ASSETS_PATH',
			'ELEMENTOR_PRO_ASSETS_URL',
		);

		foreach ( $requiredConstants as $constant ) {
			$this->assertTrue(
				defined( $constant ),
				"$constant should be defined"
			);
		}
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
