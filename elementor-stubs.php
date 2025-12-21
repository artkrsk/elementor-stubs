<?php
/**
 * Elementor PHPStan Stubs
 *
 * Placeholder file - regenerate via GitHub Actions workflow or `composer generate`
 *
 * @package arts/elementor-stubs
 */

namespace {
	// Elementor Free constants
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		define( 'ELEMENTOR_VERSION', '0.0.0' );
	}
	if ( ! defined( 'ELEMENTOR__FILE__' ) ) {
		define( 'ELEMENTOR__FILE__', __FILE__ );
	}
	if ( ! defined( 'ELEMENTOR_PLUGIN_BASE' ) ) {
		define( 'ELEMENTOR_PLUGIN_BASE', plugin_basename( ELEMENTOR__FILE__ ) );
	}
	if ( ! defined( 'ELEMENTOR_PATH' ) ) {
		define( 'ELEMENTOR_PATH', plugin_dir_path( ELEMENTOR__FILE__ ) );
	}
	if ( ! defined( 'ELEMENTOR_URL' ) ) {
		define( 'ELEMENTOR_URL', plugins_url( '/', ELEMENTOR__FILE__ ) );
	}
	if ( ! defined( 'ELEMENTOR_ASSETS_PATH' ) ) {
		define( 'ELEMENTOR_ASSETS_PATH', ELEMENTOR_PATH . 'assets/' );
	}
	if ( ! defined( 'ELEMENTOR_ASSETS_URL' ) ) {
		define( 'ELEMENTOR_ASSETS_URL', ELEMENTOR_URL . 'assets/' );
	}
}

namespace Elementor {
	class Plugin {
		/** @var Plugin|null */
		public static $instance;
	}

	abstract class Widget_Base {
	}
}

namespace Elementor\Core\Base {
	abstract class Document {
	}
}
