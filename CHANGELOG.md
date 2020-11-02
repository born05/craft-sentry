# Craft Sentry Changelog

## 1.0.8 - 2020-11-02
### Changed
- Updated sentry/sdk to 3.1
- Use the default integrations as much as possible for more info

## 1.0.7 - 2020-10-16
### Changed
- Don't do anything until EVENT_AFTER_LOAD_PLUGINS. Thanks to @jamesedmonston

## 1.0.6 - 2020-01-17
### Fixes
- Prevent errors during plugin install. Thanks to @boboldehampsink

## 1.0.5 - 2020-01-09
### Changed
- Updated sentry/sdk to 2.1.0

## 1.0.4 - 2020-01-09
### Added
- Let sentry handle craft console errors

## 1.0.3 - 2020-01-09
### Change
- Let craft handle craft errors again for excludedCodes support.
- Depend on enabled setting not on devMode

## 1.0.2 - 2019-12-13
### Fixed
- Downgraded sentry/sdk to prevent install errors

## 1.0.1 - 2019-12-12
### Change
- Populate all captures with additional data.
- Don't use craft handled error events.

## 1.0.0 - 2019-11-26
### Added
- Stable release

## 1.0.0-beta.1 - 2019-11-25
### Added
- Initial Release
