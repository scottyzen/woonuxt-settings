# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.4] - 2025-12-03

### Added

- Complete plugin architecture refactoring to class-based structure following WordPress standards
- New class-based components: `WooNuxt_Plugin`, `WooNuxt_Admin`, `WooNuxt_Ajax_Handler`, `WooNuxt_Plugin_Manager`
- Centralized helper functions in `functions.php` for improved code reusability
- New constants file (`constants.php`) for centralized version management and URLs
- GraphQL Schema Reference UI with collapsible details in admin settings page
- New template system for admin UI components (`admin-page.php`, `graphql-schema.php`)
- Comprehensive code formatting configuration (`.editorconfig`, `.php-cs-fixer.php`, `.phpfmt.ini`, `.prettierrc`)
- WooCommerce logo asset for admin UI
- Detailed refactoring documentation in `REFACTORING.md`

### Changed

- Refactored entire plugin structure for improved maintainability and testability
- Enhanced admin JavaScript with better state management and error handling (245 line changes)
- Massively improved CSS styling with modern admin UI patterns (1521+ line additions)
- Reorganized GraphQL integration code for better readability (451 line refactor)
- Updated asset enqueuing system for better performance
- Improved plugin.json structure and metadata

### Fixed

- Improve Stripe payment intent error handling and validation
- Add comprehensive debugging and logging for Stripe API calls
- Enhanced clientSecret generation validation to resolve Stripe.js integration issues
- Better error handling for WooCommerce cart availability
- Added HTTP response code validation for Stripe API responses
- Improved JSON parsing error handling for Stripe responses

### Technical

- Implemented singleton pattern for main plugin class
- Separated concerns with dedicated classes for admin, AJAX, and plugin management
- Enhanced security with improved input validation and sanitization
- Better WordPress coding standards compliance
- Improved code documentation with comprehensive docblocks
- Total changes: 4,020 insertions, 799 deletions across 24 files

## [2.2.3] - 2025-10-11

### Fixed

- Add missing Stripe payment intent and setup intent functions to resolve GraphQL stripePaymentIntent query errors
- Implemented `create_payment_intent()` and `create_setup_intent()` functions with proper Stripe API integration
- Added comprehensive error handling for Stripe configuration and API communication issues

## [2.2.2] - 2025-08-13

### Added

- Enable logout mutation by default (@alexookah in #18)

### Fixed

- Ensure $options['productsPerPage'] is set before accessing it (@hacknug in #16)

## [2.2.1] - 2024-XX-XX

### Changed

- Update WP GraphQL Headless Login version to 0.4.3

## [2.2.0] - 2024-XX-XX

### Added

- Major security and code quality improvements
- Enhanced AJAX security with nonces
- Comprehensive input sanitization
- Improved error handling
- Standardized function naming with woonuxt\_ prefix
- Proper DocBlocks
- Better JavaScript error handling
- Overall code organization

## [2.0.1] - 2024-XX-XX

### Changed

- Update plugin and software versions

## [2.0.0] - 2024-XX-XX

### Changed

- Move to Headless Login for WPGraphQL

## [1.0.58] - 2024-XX-XX

### Improved

- Stripe add payment option

## [1.0.57] - 2024-XX-XX

### Changed

- Update plugin versions

## [1.0.56] - 2024-XX-XX

### Added

- Add currencySymbol

### Fixed

- Fix Vercel url

## [1.0.55] - 2024-XX-XX

### Fixed

- Ceil maxPrice value

## [1.0.54] - 2024-XX-XX

### Changed

- Change to setup_intents

## [1.0.53] - 2024-XX-XX

### Added

- Add stripePaymentIntent for 3d secure

## [1.0.52] - 2024-XX-XX

### Changed

- Bump version

## [1.0.51] - 2024-XX-XX

### Enhanced

- Social media fields and schema

## [1.0.50] - 2024-XX-XX

### Added

- SEO settings

## [1.0.49] - 2024-XX-XX

### Added

- Functionality to increase max query amount if there are more than 100 products

## [1.0.45] - 2024-XX-XX

### Fixed

- Error when WooCommerce is disabled or not installed
