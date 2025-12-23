# Elementor Stubs

[![Test](https://github.com/artkrsk/elementor-stubs/actions/workflows/integrate.yml/badge.svg)](https://github.com/artkrsk/elementor-stubs/actions/workflows/integrate.yml)
[![Latest Release](https://img.shields.io/github/v/release/artkrsk/elementor-stubs)](https://github.com/artkrsk/elementor-stubs/releases/latest)
[![Packagist Version](https://img.shields.io/packagist/v/arts/elementor-stubs)](https://packagist.org/packages/arts/elementor-stubs)
[![PHP Version](https://img.shields.io/packagist/dependency-v/arts/elementor-stubs/php)](https://packagist.org/packages/arts/elementor-stubs)
[![Downloads](https://img.shields.io/packagist/dt/arts/elementor-stubs)](https://packagist.org/packages/arts/elementor-stubs)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-yellow?logo=buy-me-a-coffee)](https://buymeacoffee.com/artemsemkin)

Comprehensive PHPStan stubs for Elementor and Elementor Pro WordPress page builder.

Get full IDE autocomplete, IntelliSense, and type safety when developing Elementor widgets, extensions, and custom implementations.

## Features

- Full IDE autocomplete for all Elementor and Elementor Pro classes
- Type safety and static analysis with PHPStan
- Catch errors before runtime when developing widgets and extensions
- Self-contained - includes all necessary Elementor constants
- Auto-updated with new Elementor releases

## Requirements

- PHP 8.0 or higher
- PHPStan for static analysis
- Automatically includes WordPress and WooCommerce stubs as dependencies

## Installation

```bash
composer require --dev arts/elementor-stubs
```

## Usage with PHPStan

Add to your `phpstan.neon`:

```yaml
parameters:
    bootstrapFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
        - vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php
        - vendor/arts/elementor-stubs/elementor-stubs.php
```

The stubs include both Elementor Free and Elementor Pro type definitions.

> **Note:** Stubs are versioned to match Elementor releases. Check [releases](https://github.com/artkrsk/elementor-stubs/releases) for your specific Elementor version.

## Regenerating Stubs

For contributors or to generate stubs from a specific Elementor version:

1. Copy `.env.example` to `.env`
2. Set `ELEMENTOR_PATH` to your Elementor installation
3. Optionally set `ELEMENTOR_PRO_PATH` for Pro stubs
4. Run: `composer generate`

```bash
cp .env.example .env
# Edit .env with your paths
composer generate
```

## 💖 Support

If you find this plugin useful, consider buying me a coffee:

<a href="https://buymeacoffee.com/artemsemkin" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 60px !important;width: 217px !important;" ></a>

---

Made with ❤️ by [Artem Semkin](https://artemsemkin.com)
