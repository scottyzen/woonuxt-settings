# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.5.18] - 2026-07-21

### Added

- Add a read-only Connection Health panel to the WooNuxt settings screen
- Report required plugin activation and version status, WooNuxt GraphQL integration readiness, the configured GraphQL endpoint, and frontend URL configuration

## [2.5.17] - 2026-07-20

### Changed

- Remove the unbounded GraphQL product query limit override
- Cache the maximum product price returned by GraphQL settings for 15 minutes

## [2.5.16] - 2026-07-20

### Changed

- Update required WooCommerce version to `10.9.4` and WooGraphQL version to `1.0.3`
- Bump plugin metadata to `2.5.16`

## [2.5.15] - 2026-06-28

### Changed

- Update required setup targets to WooCommerce `10.9.1` and WPGraphQL `2.17.0`
- Bump plugin metadata to `2.5.15`

## [2.5.14] - 2026-06-15

### Added

- Add constants for WordPress tested up to `7.0.0`, Node `22.22.2`, and PHP `8.4` setup targets

### Changed

- Update required setup targets to WooCommerce `10.9.1` and WPGraphQL `2.17.0`
- Bump plugin metadata to `2.5.14`

### Fixed

- Keep `woonuxtSettings` available during first-time setup before WooCommerce is active
- Move required-plugin install and activation handling out of settings-page rendering

## [2.5.13] - 2026-06-13

### Added

- Add an Apple Pay Merchant ID setting and expose it as `stripeSettings.apple_pay_merchant_identifier`
- Add `stripeSettings.active_publishable_key` so clients can use the correct Stripe publishable key without duplicating test mode logic

### Changed

- Return an explicit Stripe GraphQL settings whitelist instead of the raw WooCommerce Stripe gateway settings array

## [2.5.12] - 2026-06-13

### Added

- Expose Stripe `account_id` in the `stripeSettings` GraphQL schema for native payment initialization flows

## [2.5.11] - 2026-05-04

### Removed

- Remove `customerSessionClientSecret` field from `PaymentIntent` GraphQL type
- Remove CustomerSession creation logic from `stripePaymentIntent` resolver

## [2.5.10] - 2026-05-02

### Fixed

- Add missing capability checks to class-based admin AJAX handlers and plugin installation flow
- Restrict plugin status AJAX checks to the configured required plugin file for each allowed slug
- Make the `woonuxtSettings` GraphQL resolver tolerate missing or malformed option arrays
- Guard class-based admin product attribute lookups when WooCommerce helpers are unavailable
- Validate build hook URLs before triggering an admin rebuild request
- Correct `wp_die()` response code so capability check failures return HTTP 403 instead of 200
- Fix undefined `$plugin_list` variable in legacy `woonuxt_handle_check_plugin_status()` AJAX handler

### Added

- Add GitHub Actions PHP lint workflow

## [2.5.9] - 2026-04-26

### Fixed

- Hide Trigger Rebuild unless the Build Hook contains a non-empty URL value
- Prevent whitespace-only Build Hook values from passing deploy checks in admin UI
- Guard admin settings loops against malformed option data to avoid warnings

### Security

- Add capability checks to admin AJAX handlers for plugin status and plugin update actions
- Add request field validation and safer unslashing/sanitization for AJAX payloads

### Changed

- Register a sanitize callback on legacy settings registration path
- Normalize URL-like option sanitization with trimming and enforce minimum `productsPerPage` of `1`

## [2.5.8] - 2026-04-26

### Changed

- Update required plugin install targets to WooCommerce `10.7.0`, WPGraphQL `2.12.0`, and WooGraphQL `1.0.2`

## [2.5.7] - 2026-04-25

### Fixed

- Prevent fatal error on the WooNuxt settings page when WooCommerce attribute helpers are unavailable by safely guarding product attribute lookups

## [2.5.6] - 2026-04-19

### Changed

- Complete saved cards feature: Stripe CustomerSession, `savedPaymentMethods` GraphQL field, and `stripeCustomerId` resolver are production-ready
- Remove all debug `error_log` calls from Stripe GraphQL helpers

## [2.5.5] - 2026-04-03

### Added

- Add support for saving Stripe cards for future payments

## [2.5.3] - 2026-02-22

### Changed

- Bump plugin version metadata to `2.5.3` across plugin header, constants, and update metadata
- Add release housekeeping notes for this version bump

## [2.5.0] - 2026-02-08

### Changed

- Add fullYoastHead field to drive frontend Yoast integration
- Update plugin version metadata and changelog entries

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
