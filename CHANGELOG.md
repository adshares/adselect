# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2022-07-27
### Changed
- Support PHP 8.1

## [1.1.2] - 2022-05-19
### Added
- Integration tests
### Changed
- RPM range up to 999.99
### Fixed
- Check campaign time range

## [1.1.1] - 2022-04-14
### Fixed
- Banner selection on low RPM

## [1.1.0] - 2022-02-17
### Added
- Support responsive banners

## [1.0.0] - 2021-08-10
### Changed
- Upgrade to PHP 7.4
- Upgrade to Composer 2
- Upgrade to Symfony 5

## [0.3.0] - 2021-06-01
### Changed
- New score formula 
- Remove stale banners
- Return RPM without seen adjustment
- Support min_cpm and cpa_only
- Only count recent events for rpm stats
- Use RPM stats to select banners
### Fixed
- Fix banner rotation
- Fix scoring

## [0.2.2] - 2019-06-26
### Added
- Index refresh interval setting

## [0.2.2] - 2019-06-26
### Added
- Index refresh interval setting

## [0.2.2] - 2019-06-26
### Added
- Index refresh interval setting

## [0.2.1] - 2019-06-25
### Added
- Campaign soft delete
- Remove documents from User history and Events indexes
- Slow log configuration
- Add tracking_id to User history and based on it during fetching banners
- Add budget information to a campaign index

## [0.2.0] - 2019-06-12
### Changed
- Implementation from python to PHP
### Added
- ElasticSearch as storage

## [0.1.0] - 2019-04-19
Last python version

[Unreleased]: https://github.com/adshares/adselect/compare/v1.2.0...develop
[1.2.0]: https://github.com/adshares/adselect/compare/v1.1.2...v1.2.0
[1.1.2]: https://github.com/adshares/adselect/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/adshares/adselect/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/adshares/adselect/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/adshares/adselect/compare/v0.3.0...v1.0.0
[1.0.0]: https://github.com/adshares/adselect/compare/v0.3.0...v1.0.0
[0.3.0]: https://github.com/adshares/adselect/compare/v0.2.1...v0.3.0
[0.2.2]: https://github.com/adshares/adselect/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/adshares/adselect/compare/v0.2...v0.2.1
[0.2.0]: https://github.com/adshares/adselect/compare/v0.1...v0.2
[0.1.0]: https://github.com/adshares/adselect/releases/tag/v0.1
