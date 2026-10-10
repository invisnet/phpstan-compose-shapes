# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-10

### Added

- `compose-shapes<A, B, ...>` PHPDoc type: left-to-right recursive merge of sealed array shapes
- Last-seen key wins for value type and optionality; nested sealed shapes are merged
- Unresolvable when given fewer than two arguments, or a non-sealed / non-shape argument
- Auto-registration via `phpstan/extension-installer`

[1.0.0]: https://github.com/invisnet/phpstan-compose-shapes/releases/tag/1.0.0
