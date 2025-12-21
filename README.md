# Elementor Stubs

Comprehensive PHPStan stubs for Elementor and Elementor Pro WordPress page builder.

Auto-generated, self-contained, plug & play.

## Installation

```bash
composer require --dev arts/elementor-stubs
```

## Usage

Add to your `phpstan.neon`:

```yaml
parameters:
    bootstrapFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
        - vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php
        - vendor/arts/elementor-stubs/elementor-stubs.php
```

The stubs include both Elementor Free and Elementor Pro type definitions.

## Included Constants

The stubs define essential constants for static analysis:

**Elementor Free:**
- `ELEMENTOR_VERSION`
- `ELEMENTOR__FILE__`
- `ELEMENTOR_PLUGIN_BASE`
- `ELEMENTOR_PATH`
- `ELEMENTOR_URL`
- `ELEMENTOR_ASSETS_PATH`
- `ELEMENTOR_ASSETS_URL`

**Elementor Pro:**
- `ELEMENTOR_PRO_VERSION`
- `ELEMENTOR_PRO__FILE__`
- `ELEMENTOR_PRO_PLUGIN_BASE`
- `ELEMENTOR_PRO_PATH`
- `ELEMENTOR_PRO_URL`
- `ELEMENTOR_PRO_ASSETS_PATH`
- `ELEMENTOR_PRO_ASSETS_URL`

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

## Requirements

- PHP >= 7.2
- Composer

## Dependencies

This package requires the following stub packages (installed automatically):
- `php-stubs/wordpress-stubs`
- `php-stubs/woocommerce-stubs`
- `php-stubs/wp-cli-stubs`

## License

GPL-3.0-or-later
