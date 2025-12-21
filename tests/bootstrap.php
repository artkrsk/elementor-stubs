<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load WordPress stubs first (provides WordPress functions)
require_once __DIR__ . '/../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
require_once __DIR__ . '/../vendor/php-stubs/wp-cli-stubs/wp-cli-stubs.php';

// Load WooCommerce stubs (required by Elementor Pro)
require_once __DIR__ . '/../vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php';

// Load Elementor stubs
require_once __DIR__ . '/../elementor-stubs.php';
