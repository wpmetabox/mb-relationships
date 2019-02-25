# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.6.0 - 2019-02-25
### Added
- Added support for querying by multiple relationships. See [docs](https://docs.metabox.io/extensions/mb-relationships/) for details.

### Changed
- Extracted admin columns and meta boxes into their own classes
- Renamed files and classes for clarity

## 1.5.0 - 2018-12-19
### Fixed
- Fixed incorrect order of items when changing order of connected items.

### Changed
- Changed the database structure by adding `order_from` and `order_to` columns to track order of items.
- Removed reference to global `$wpdb` and use the global variable directly. This prevents serialize objects in some unexpected situations.

## 1.4.1 - 2018-10-26
### Fixed
 - Fixed cannot query for posts excluded from search.
 - Fixed not showing entries in admin columns.

## 1.4.0 - 2018-08-24
### Added
- Added 'closed' and 'autosave' param to relationship meta boxes.

### Fixed
- Fixed indirect variable access in PHP 5.x.

## 1.3.2 - 2018-07-02
### Changed
- Reverted the `'post_type' => 'any'` as it relates to many queries.

## 1.3.1 - 2018-07-02
### Changed
- Remove `'post_type' => 'any'` in the query for relationship, which causes unexpected behaviour with post types that have `'exclude_from_search' => true`. Developers should always set `post_type` in their queries. See https://bit.ly/2lPvnPk.

## 1.3.0 - 2018-05-25
### Added
- Added support for admin columns. See [documentation](https://docs.metabox.io/extensions/mb-relationships/) for details.

## 1.2.0 - 2018-04-27
### Added
- Added API to get siblings items.

## 1.1.2 - 2018-03-08
### Fixed
- Fixed output of related posts with the same order as in the backend.

## 1.1.1 - 2018-02-27
### Added
- Made clones sortable

## 1.1.0
### Added
- Added meta box for selecting 'from' objects
- Added CRUD API for relationships

## Version 1.0.0
- Initial version
