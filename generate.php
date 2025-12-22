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

// Load .env configuration (optional - CI sets env vars directly)
$dotenv = Dotenv::createImmutable( __DIR__ );
$dotenv->safeLoad(); // Won't throw if .env doesn't exist

// Get paths from environment (supports both .env and CI env vars)
$envElementorPath    = getenv( 'ELEMENTOR_PATH' );
$envElementorProPath = getenv( 'ELEMENTOR_PRO_PATH' );
$elementorPath       = $envElementorPath ? $envElementorPath : ( $_ENV['ELEMENTOR_PATH'] ?? null );
$elementorProPath    = $envElementorProPath ? $envElementorProPath : ( $_ENV['ELEMENTOR_PRO_PATH'] ?? null );

if ( empty( $elementorPath ) ) {
	echo color( "Error: ELEMENTOR_PATH environment variable is required.\n", 'red' );
	echo "Please create .env file or set the environment variable.\n";
	echo "See .env.example for template.\n";
	exit( 1 );
}

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
	->exclude( array( 'lib', 'vendor', 'tests', 'modules/dev-tools', 'modules/*/node_modules' ) )
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

// 2.6. Resolve unqualified class names in PHPDoc annotations
$content = resolvePhpDocClassNames( $content, $elementorPath, $includeElementorPro ? $elementorProPath : null );

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

/**
 * Resolve unqualified class names in PHPDoc annotations by using the original source files' use statements.
 *
 * Fixes issues where stub generators use namespace blocks where `use` statements cannot be placed,
 * causing PHPStan to resolve unqualified class names to the wrong namespace.
 *
 * Example fix:
 *   Before: @var Kit (resolved as Elementor\Core\Kits\Documents\Tabs\Kit - wrong!)
 *   After:  @var \Elementor\Core\Kits\Documents\Kit (correct fully-qualified name)
 *
 * @param string      $stubContent       The generated stub content
 * @param string      $elementorPath     Path to Elementor source
 * @param string|null $elementorProPath  Path to Elementor Pro source (optional)
 * @return string                        Stub content with resolved class names
 */
function resolvePhpDocClassNames( string $stubContent, string $elementorPath, ?string $elementorProPath = null ): string {
	// Build a mapping of namespaces to their use statements from source files
	$useMap = buildUseStatementsMap( $elementorPath, $elementorProPath );

	// Parse stub content and resolve unqualified class names in PHPDoc annotations
	$lines  = explode( "\n", $stubContent );
	$output = array();

	$currentNamespace = '';
	$inDocBlock       = false;

	foreach ( $lines as $line ) {
		// Track current namespace
		if ( preg_match( '/^namespace\s+([\w\\\\]+)\s*[{;]/', $line, $matches ) ) {
			$currentNamespace = $matches[1];
		}

		// Track if we're in a doc block
		if ( str_contains( $line, '/**' ) ) {
			$inDocBlock = true;
		}

		// Process PHPDoc annotations
		if ( $inDocBlock && preg_match( '/@(var|param|return|throws)\s+/', $line ) ) {
			$line = resolveAnnotationLine( $line, $currentNamespace, $useMap );
		}

		// Track if we're leaving a doc block
		if ( str_contains( $line, '*/' ) ) {
			$inDocBlock = false;
		}

		$output[] = $line;
	}

	return implode( "\n", $output );
}

/**
 * Build a map of namespaces to their use statements from source files.
 *
 * @param string      $elementorPath    Path to Elementor source
 * @param string|null $elementorProPath Path to Elementor Pro source (optional)
 * @return array                        Map of namespace => [className => fullyQualifiedName]
 */
function buildUseStatementsMap( string $elementorPath, ?string $elementorProPath = null ): array {
	$map   = array();
	$paths = array( $elementorPath );

	if ( $elementorProPath ) {
		$paths[] = $elementorProPath;
	}

	foreach ( $paths as $path ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}

			// Skip excluded directories
			$filePath = $file->getPathname();
			if ( preg_match( '#/(lib|vendor|tests|node_modules)/#', $filePath ) ) {
				continue;
			}

			$fileUseMap = extractUseStatementsFromFile( $filePath );
			foreach ( $fileUseMap as $namespace => $useStatements ) {
				if ( ! isset( $map[ $namespace ] ) ) {
					$map[ $namespace ] = array();
				}
				$map[ $namespace ] = array_merge( $map[ $namespace ], $useStatements );
			}
		}
	}

	return $map;
}

/**
 * Extract use statements from a PHP source file.
 *
 * @param string $filePath Path to PHP source file
 * @return array           Map of namespace => [className => fullyQualifiedName]
 */
function extractUseStatementsFromFile( string $filePath ): array {
	$content = file_get_contents( $filePath );
	$result  = array();

	// Match namespace declaration
	if ( ! preg_match( '/^namespace\s+([\w\\\\]+)\s*;/m', $content, $nsMatches ) ) {
		return $result;
	}

	$namespace = $nsMatches[1];

	// Extract all use statements
	preg_match_all( '/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m', $content, $useMatches, PREG_SET_ORDER );

	$useMap = array();
	foreach ( $useMatches as $match ) {
		$fullyQualified   = $match[1];
		$alias            = $match[2] ?? basename( str_replace( '\\', '/', $fullyQualified ) );
		$useMap[ $alias ] = '\\' . $fullyQualified;
	}

	if ( ! empty( $useMap ) ) {
		$result[ $namespace ] = $useMap;
	}

	return $result;
}

/**
 * Resolve unqualified class names in a PHPDoc annotation line.
 *
 * @param string $line             The annotation line
 * @param string $currentNamespace The current namespace context
 * @param array  $useMap           Map of namespace => [className => fullyQualifiedName]
 * @return string                  Line with resolved class names
 */
function resolveAnnotationLine( string $line, string $currentNamespace, array $useMap ): string {
	// Pattern: @var|@param|@return followed by type (possibly with |, [], etc.)
	// Examples: @var Kit, @var Kit[], @var Kit|null, @param array<Kit>
	return preg_replace_callback(
		'/@(var|param|return|throws)\s+([^\s*]+)/',
		function ( $matches ) use ( $currentNamespace, $useMap ) {
			$annotation = $matches[1];
			$typeString = $matches[2];

			// Resolve each type in the type string (handle unions, arrays, generics)
			$resolvedType = resolveTypeString( $typeString, $currentNamespace, $useMap );

			return '@' . $annotation . ' ' . $resolvedType;
		},
		$line
	);
}

/**
 * Resolve a complex type string (with unions, arrays, generics, etc.).
 *
 * @param string $typeString       The type string (e.g., "Kit|null", "Kit[]", "array<Kit>")
 * @param string $currentNamespace The current namespace context
 * @param array  $useMap           Map of namespace => [className => fullyQualifiedName]
 * @return string                  Resolved type string
 */
function resolveTypeString( string $typeString, string $currentNamespace, array $useMap ): string {
	// Split on type separators but preserve them
	// Handle: | for unions, [] for arrays, <> for generics, () for callables
	$parts = preg_split( '/([\|<>,\[\]\(\)\s]+)/', $typeString, -1, PREG_SPLIT_DELIM_CAPTURE );

	$resolved = array();
	foreach ( $parts as $part ) {
		// Skip empty parts and delimiters
		if ( empty( trim( $part ) ) || preg_match( '/^[\|<>,\[\]\(\)\s]+$/', $part ) ) {
			$resolved[] = $part;
			continue;
		}

		// Resolve the class name
		$resolved[] = resolveClassName( $part, $currentNamespace, $useMap );
	}

	return implode( '', $resolved );
}

/**
 * Resolve a single class name using the use map.
 *
 * @param string $className        Unqualified class name
 * @param string $currentNamespace Current namespace context
 * @param array  $useMap           Map of namespace => [className => fullyQualifiedName]
 * @return string                  Fully-qualified class name or original if not resolvable
 */
function resolveClassName( string $className, string $currentNamespace, array $useMap ): string {
	// Handle incomplete Elementor namespace paths (e.g., \Base\Manager, \Core\Kits\Manager)
	// These start with \ but are missing the Elementor\ prefix
	if ( isset( $className[0] ) && '\\' === $className[0] ) {
		// List of known Elementor/ElementorPro sub-namespaces that might appear incomplete
		$elementorSubNamespaces = array( 'App\\', 'Base\\', 'Core\\', 'Data\\', 'Includes\\', 'License\\', 'Modules\\', 'TemplateLibrary\\' );

		foreach ( $elementorSubNamespaces as $subNs ) {
			if ( str_starts_with( substr( $className, 1 ), $subNs ) ) {
				return '\\Elementor\\' . substr( $className, 1 );
			}
		}

		// Already fully-qualified with proper namespace
		return $className;
	}

	// Scalar types, special types, or lowercase (not class names)
	$scalarTypes = array( 'string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'null', 'false', 'true', 'self', 'static', 'parent', 'resource', 'never' );
	if ( in_array( strtolower( $className ), $scalarTypes, true ) ) {
		return $className;
	}

	// Check if it's in the use map for current namespace
	if ( isset( $useMap[ $currentNamespace ][ $className ] ) ) {
		return $useMap[ $currentNamespace ][ $className ];
	}

	// Partial namespace path (contains \ but doesn't start with \)
	if ( str_contains( $className, '\\' ) ) {
		// If we're in an Elementor namespace, treat as relative to current namespace
		if ( str_starts_with( $currentNamespace, 'Elementor' ) ) {
			return '\\' . $currentNamespace . '\\' . $className;
		}

		// Otherwise treat as global namespace
		return '\\' . $className;
	}

	// If not found in use map and starts with uppercase, assume it's in current namespace
	if ( ctype_upper( $className[0] ) ) {
		return '\\' . $currentNamespace . '\\' . $className;
	}

	// Return as-is if we can't resolve it
	return $className;
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
