# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [2.0.2] - 2022-12-23

### Fixed

- Non UTF-8 encoded contents

## [2.0.1] - 2022-12-16

### Fixed

- Usage of deprecated function `mb_convert_encoding()` with html-entities (PHP 8.2)

## [2.0.0] - 2022-08-24

### Changed

- Code style

## [2.0.0-beta3] - 2022-08-16

### Fixed

- Fix the "end" operator `=$` for attribute comparison selector
- Fix `Query::prev()` method

## [2.0.0-beta2] - 2022-01-13

### Added

- New method `Query::map(): array` to apply function to all results of query and get array result

### Fixed

- Bad Xpath context in method `Query::is(): bool`

## [2.0.0-beta1] - 2021-05-11

### Added

- `:count(...)` pseudo class

### Fixed

- Tests fixed

## [2.0.0-alpha1] - 2021-03-22

### Added

- `HtmlSelector` class to init and manage relations
- `XpathSolver` class to solve a selector to xpath
- Extensions to add pseudo classes

### Changed

- Refactoring

## [1.0.0] - 2020-11-05

First version
