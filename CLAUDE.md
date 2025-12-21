# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHPStan stubs for Elementor and Elementor Pro WordPress page builder. The stubs are auto-generated from Elementor source code using `php-stubs/generator`.

## Commands

```bash
# Run all tests (PHPUnit + PHPStan + PHPCS)
composer test

# Individual test commands
composer test:phpunit    # Run PHPUnit tests
composer test:phpstan    # Run PHPStan analysis (uses tests/phpstan.neon)
composer test:cs         # Run PHP CodeSniffer
composer test:cs:fix     # Auto-fix coding style issues

# Generate stubs (requires ELEMENTOR_PATH env var)
composer generate
```

## Generating Stubs

The `generate.php` script requires environment variables:
- `ELEMENTOR_PATH` - Path to Elementor source (required)
- `ELEMENTOR_PRO_PATH` - Path to Elementor Pro source (optional)

Set these via `.env` file (copy from `.env.example`) or export directly.

## Architecture

### Key Files
- `elementor-stubs.php` - Generated output file containing all stubs
- `generate.php` - Stub generation script with post-processing:
  - Removes ElementorDeps/ElementorProDeps namespaces
  - Removes stray code statements (code outside class/function context)
  - Adds self-contained Elementor constants
  - Appends class aliases for deprecated class locations

### GitHub Workflows
- `generate.yml` - Manual workflow to generate stubs from specific Elementor version
- `integrate.yml` - CI tests on push/PR (PHP 8.0-8.4)
- `release.yml` - Creates GitHub release when tag is pushed

### Testing
- PHPStan runs at max level against the stubs file
- PHPUnit verifies stub syntax is valid PHP
- PHPCS enforces WordPress coding standards on `generate.php` and tests

## Coding Standards

Uses WordPress-Core coding standards with exceptions for:
- Modern file naming (PSR-4 style)
- camelCase function/variable names
