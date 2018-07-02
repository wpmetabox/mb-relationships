# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

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
