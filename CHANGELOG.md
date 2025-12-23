# Changelog

## [3.34.0.2] - 2025-12-23

- improved: README for better SEO and discoverability
- added: Features and Requirements sections to README
- improved: composer.json keywords for better package discovery
- added: GitHub Sponsor button via FUNDING.yml
- added: Dependabot configuration for automated dependency updates
- added: CHANGELOG to track version history

## [3.34.0.1] - 2025-12-23

- **BREAKING:** require PHP 8.0 or higher
- fixed: composer.lock compatibility with PHP 8.0
- added: automated version checking workflow (biweekly checks for new Elementor releases)
- added: status badges to README
- improved: export-ignore configuration for cleaner package distribution

## [3.34.0] - 2025-12-23

- changed: updated stubs for Elementor 3.34.0

## [3.33.6.2] - 2025-12-22

- fixed: PHPDoc class name resolution for Elementor namespaces
- improved: handling of partial namespace paths in type annotations

## [3.33.6.1] - 2025-12-21

- added: resolve unqualified class names in PHPDoc annotations using source file `use` statements
- improved: PHPDoc type resolution for better static analysis accuracy
- fixed: Tab_Base parent property now correctly references `\Elementor\Core\Kits\Documents\Kit`
- fixed: CI workflow permissions for PR comments

## [3.33.6] - 2025-12-21

- added: initial release of Elementor stubs
- added: complete type definitions for Elementor Free and Elementor Pro
- added: self-contained constants for all Elementor paths and versions
- added: automated stub generation from Elementor source
- added: PHPUnit and PHPStan test coverage
- added: GitHub Actions CI/CD pipeline

[3.34.0.2]: https://github.com/artkrsk/elementor-stubs/compare/v3.34.0.1...v3.34.0.2
[3.34.0.1]: https://github.com/artkrsk/elementor-stubs/compare/v3.34.0...v3.34.0.1
[3.34.0]: https://github.com/artkrsk/elementor-stubs/compare/v3.33.6.2...v3.34.0
[3.33.6.2]: https://github.com/artkrsk/elementor-stubs/compare/v3.33.6.1...v3.33.6.2
[3.33.6.1]: https://github.com/artkrsk/elementor-stubs/compare/v3.33.6...v3.33.6.1
[3.33.6]: https://github.com/artkrsk/elementor-stubs/releases/tag/v3.33.6
