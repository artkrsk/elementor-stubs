#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use StubsGenerator\{StubsGenerator, Finder};
use Dotenv\Dotenv;

// Helper function for colored output
function color( string $text, string $color ): string {
	$colors = array(
		'green'  => "\033[32m",
		'red'    => "\033[31m",
		'yellow' => "\033[33m",
		'reset'  => "\033[0m",
	);

	return ( $colors[ $color ] ?? '' ) . $text . $colors['reset'];
}

// Extract Elementor version from plugin header
function extractElementorVersion( string $elementorPath ): string {
	$mainFile = $elementorPath . '/elementor.php';

	if ( ! file_exists( $mainFile ) ) {
		throw new \Exception( "Elementor main file not found: {$mainFile}" );
	}

	$content = file_get_contents( $mainFile );

	// Parse plugin header for "Version: X.X.X"
	if ( preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', $content, $matches ) ) {
		return trim( $matches[1] );
	}

	throw new \Exception( "Could not extract version from {$mainFile}" );
}

// Extract Elementor Pro version from plugin header
function extractElementorProVersion( string $elementorProPath ): ?string {
	// Check both possible main file names (elementor-pro vs pro-elements directory)
	$possibleMainFiles = array(
		$elementorProPath . '/elementor-pro.php',
		$elementorProPath . '/pro-elements.php',
	);

	$mainFile = null;
	foreach ( $possibleMainFiles as $file ) {
		if ( file_exists( $file ) ) {
			$mainFile = $file;
			break;
		}
	}

	if ( ! $mainFile ) {
		return null;
	}

	$content = file_get_contents( $mainFile );

	// Parse plugin header for "Version: X.X.X"
	if ( preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', $content, $matches ) ) {
		return trim( $matches[1] );
	}

	return null;
}

// Load .env configuration
$dotenv = Dotenv::createImmutable( __DIR__ );
try {
	$dotenv->load();
	$dotenv->required( 'ELEMENTOR_PATH' )->notEmpty();
} catch ( \Exception $e ) {
	echo color( "Error: {$e->getMessage()}\n", 'red' );
	echo "Please create .env file with ELEMENTOR_PATH variable.\n";
	echo "See .env.example for template.\n";
	exit( 1 );
}

$elementorPath    = $_ENV['ELEMENTOR_PATH'];
$elementorProPath = $_ENV['ELEMENTOR_PRO_PATH'] ?? null;

if ( ! is_dir( $elementorPath ) ) {
	echo color( "Error: Elementor source not found at $elementorPath\n", 'red' );
	echo "Please update ELEMENTOR_PATH in .env file.\n";
	exit( 1 );
}

echo color( "Generating stubs from: $elementorPath\n", 'yellow' );

// Check for Elementor Pro
$includeElementorPro = $elementorProPath && is_dir( $elementorProPath );
if ( $includeElementorPro ) {
	echo color( "Including Elementor Pro from: $elementorProPath\n", 'yellow' );
} else {
	echo color( "Elementor Pro path not provided or not found, generating Free version only\n", 'yellow' );
}

// 1. Generate stubs
$finder = Finder::create()
	->in( $elementorPath )
	->exclude( array( 'lib', 'vendor', 'modules/dev-tools', 'modules/*/node_modules' ) )
	->notName( '*/ElementorDeps/*' )
	->sortByName();

// Add Elementor Pro paths if available
if ( $includeElementorPro ) {
	$finder->in( $elementorProPath );
}

$generator = new StubsGenerator( StubsGenerator::DEFAULT );
$result    = $generator->generate( $finder );
$content   = $result->prettyPrint();

// 2. Remove ElementorDeps namespace
$content = removeElementorDepsNamespace( $content );

// 2.5. Remove stray code statements (code outside functions/classes)
$content = removeStrayCodeStatements( $content );

// 3. Extract version from source
$version    = extractElementorVersion( $elementorPath );
$proVersion = $includeElementorPro ? extractElementorProVersion( $elementorProPath ) : null;

// 4. Add self-contained constants with extracted version
$content = addSelfContainedConstants( $content, $version, $proVersion );

// 5. Add class aliases
$content .= "\n" . getClassAliases();

// 6. Write final output
file_put_contents( __DIR__ . '/elementor-stubs.php', $content );

echo color( "✓ Stubs generated successfully\n", 'green' );

// Helper functions
function removeElementorDepsNamespace( string $content ): string {
	$lines  = explode( "\n", $content );
	$output = array();
	$skip   = false;

	foreach ( $lines as $line ) {
		if ( str_contains( $line, 'namespace ElementorDeps' ) || str_contains( $line, 'namespace ElementorProDeps' ) ) {
			$skip = true;
			continue;
		}
		// Stop skipping when we see a namespace that is NOT ElementorDeps/ElementorProDeps
		if ( $skip && preg_match( '/^namespace /', $line ) && ! str_contains( $line, 'ElementorDeps' ) && ! str_contains( $line, 'ElementorProDeps' ) ) {
			$skip = false;
		}
		if ( ! $skip ) {
			$output[] = $line;
		}
	}

	return implode( "\n", $output );
}

/**
 * Remove stray code statements that appear in namespace blocks outside of class/function definitions.
 * These are code snippets from Elementor source that the stub generator incorrectly includes.
 */
function removeStrayCodeStatements( string $content ): string {
	$lines  = explode( "\n", $content );
	$output = array();

	foreach ( $lines as $line ) {
		$trimmed = trim( $line );

		// Skip stray code patterns that use $this outside object context
		if ( preg_match( '/^\s*\$\w+\s*=.*\$this->/', $line ) ) {
			continue;
		}

		// Skip stray apply_filters calls at top level
		if ( preg_match( '/^\s*\$\w+\s*=\s*apply_filters\(/', $line ) ) {
			continue;
		}

		$output[] = $line;
	}

	$content = implode( "\n", $output );

	// Remove empty namespace blocks that only contain doc comments
	// Match: namespace X { /** doc */ }
	$content = preg_replace(
		'/namespace\s+[\w\\\\]+\s*\{\s*\/\*\*[^*]*\*+(?:[^*\/][^*]*\*+)*\/\s*\}/s',
		'',
		$content
	);

	// Clean up multiple empty lines
	$content = preg_replace( '/\n{3,}/', "\n\n", $content );

	return $content;
}

function addSelfContainedConstants( string $content, string $version, ?string $proVersion = null ): string {
	$content = preg_replace( '/namespace \{.*?\n\}/s', '', $content );
	$content = preg_replace( '/^<\?php.*?\n/s', "<?php\n\n", $content );

	$constants = <<<CONSTANTS
namespace {
	// Elementor Free constants
	if (!defined('ELEMENTOR_VERSION')) {
		define('ELEMENTOR_VERSION', '{$version}');
	}
	if (!defined('ELEMENTOR__FILE__')) {
		define('ELEMENTOR__FILE__', __FILE__);
	}
	if (!defined('ELEMENTOR_PLUGIN_BASE')) {
		define('ELEMENTOR_PLUGIN_BASE', plugin_basename(ELEMENTOR__FILE__));
	}
	if (!defined('ELEMENTOR_PATH')) {
		define('ELEMENTOR_PATH', plugin_dir_path(ELEMENTOR__FILE__));
	}
	if (!defined('ELEMENTOR_URL')) {
		define('ELEMENTOR_URL', plugins_url('/', ELEMENTOR__FILE__));
	}
	if (!defined('ELEMENTOR_ASSETS_PATH')) {
		define('ELEMENTOR_ASSETS_PATH', ELEMENTOR_PATH . 'assets/');
	}
	if (!defined('ELEMENTOR_ASSETS_URL')) {
		define('ELEMENTOR_ASSETS_URL', ELEMENTOR_URL . 'assets/');
	}

CONSTANTS;

	// Add Elementor Pro constants if Pro version is provided
	if ( $proVersion ) {
		$constants .= <<<PRO_CONSTANTS

	// Elementor Pro constants
	if (!defined('ELEMENTOR_PRO_VERSION')) {
		define('ELEMENTOR_PRO_VERSION', '{$proVersion}');
	}
	if (!defined('ELEMENTOR_PRO__FILE__')) {
		define('ELEMENTOR_PRO__FILE__', __FILE__);
	}
	if (!defined('ELEMENTOR_PRO_PLUGIN_BASE')) {
		define('ELEMENTOR_PRO_PLUGIN_BASE', plugin_basename(ELEMENTOR_PRO__FILE__));
	}
	if (!defined('ELEMENTOR_PRO_PATH')) {
		define('ELEMENTOR_PRO_PATH', plugin_dir_path(ELEMENTOR_PRO__FILE__));
	}
	if (!defined('ELEMENTOR_PRO_URL')) {
		define('ELEMENTOR_PRO_URL', plugins_url('/', ELEMENTOR_PRO__FILE__));
	}
	if (!defined('ELEMENTOR_PRO_ASSETS_PATH')) {
		define('ELEMENTOR_PRO_ASSETS_PATH', ELEMENTOR_PRO_PATH . 'assets/');
	}
	if (!defined('ELEMENTOR_PRO_ASSETS_URL')) {
		define('ELEMENTOR_PRO_ASSETS_URL', ELEMENTOR_PRO_URL . 'assets/');
	}

PRO_CONSTANTS;
	}

	$constants .= "}\n\n";

	return preg_replace( '/^(namespace )/m', $constants . '$1', $content, 1 );
}

function getClassAliases(): string {
	return <<<'PHP'

namespace Elementor {
	/**
	 * @extends \Elementor\Core\Documents_Manager
	 */
	class Documents_Manager extends \Elementor\Core\Documents_Manager {}

	/**
	 * @extends \Elementor\Core\Experiments\Manager
	 */
	class Experiments_Manager extends \Elementor\Core\Experiments\Manager {}

	/**
	 * @extends \Elementor\Core\Breakpoints\Manager
	 */
	class Breakpoints_Manager extends \Elementor\Core\Breakpoints\Manager {}
}

namespace Elementor\Core {
	/**
	 * @extends \Elementor\Core\Base\Document
	 */
	abstract class Document extends \Elementor\Core\Base\Document {}
}

namespace ElementorDeps\Twig\Loader {
	interface LoaderInterface {
		/** @return \ElementorDeps\Twig\Source */
		public function getSourceContext(string $name);
		/** @return string */
		public function getCacheKey(string $name);
		/** @return bool */
		public function isFresh(string $name, int $time);
		/** @return bool */
		public function exists(string $name);
	}
}

namespace ElementorDeps\Twig {
	class Source {
		public function __construct(string $code, string $name, string $path = '') {}
		public function getCode(): string {}
		public function getName(): string {}
		public function getPath(): string {}
	}
}
PHP;
}
