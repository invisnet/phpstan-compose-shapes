# phpstan-compose-shapes

[![Build Status](https://github.com/invisnet/phpstan-compose-shapes/actions/workflows/tests.yml/badge.svg)](https://github.com/invisnet/phpstan-compose-shapes/actions/workflows/tests.yml)

PHPStan extension that adds `compose-shapes<A, B, ...>`: a left-to-right recursive merge of sealed array shapes where the last-seen key wins.

PHPStan can intersect unsealed shapes, but a sealed intersection collapses when the key sets differ. Composition is not intersection. This operator glues shapes together without that type-theory detour.

Requires PHPStan 2.3+.

## Installation

```bash
composer require --dev invisnet/phpstan-compose-shapes
```

If you use [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), you are done.

Otherwise include the extension in your `phpstan.neon`:

```neon
includes:
	- vendor/invisnet/phpstan-compose-shapes/extension.neon
```

Sealedness checks use PHPStan's sealed-shape model. Include bleeding edge if you want truly sealed shapes and rejection of `array{..., ...}`:

```neon
includes:
	- vendor/phpstan/phpstan/conf/bleedingEdge.neon
```

## Usage

```php
/**
 * @phpstan-type Base array{name: string, nested?: array{a?: int}}
 * @phpstan-type Extra array{email: string, nested?: array{b?: int}}
 * @phpstan-type User compose-shapes<Base, Extra>
 */
```

`User` is `array{name: string, nested?: array{a?: int, b?: int}, email: string}`.

Any number of arguments is allowed. Later arguments overwrite earlier ones:

```php
/**
 * @param compose-shapes<
 *   array{foo: bool, nested?: array{a?: int}},
 *   array{bar: string, nested?: array{b?: int}},
 *   array{foo: int},
 * > $row
 */
```

That is `array{foo: int, nested?: array{a?: int, b?: int}, bar: string}`.

## Merge rules

- Keys present on only one side are copied with that side's optionality.
- When both sides have the same key and both values are sealed shapes, those values are merged recursively.
- Any other clash keeps the right-hand value type and the right-hand optionality.

## Rejected inputs

`compose-shapes` is unresolvable when:

- fewer than two type arguments are given
- an argument is not a single sealed constant array shape (unions, general `array<...>`, unsealed `array{..., ...}`, or template types)

PHPStan reports that as `parameter.unresolvableType` / `return.unresolvableType` (and similar) on the PHPDoc tag.

Aliases expand before the merge, so importing a sealed `@phpstan-type` works.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). AI-assisted contributions are welcome; open or agree an Issue before sending a PR.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT
