# phpstan-compose-shapes

[![Build Status](https://github.com/invisnet/phpstan-compose-shapes/actions/workflows/tests.yml/badge.svg)](https://github.com/invisnet/phpstan-compose-shapes/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/invisnet/phpstan-compose-shapes)](https://packagist.org/packages/invisnet/phpstan-compose-shapes)

PHPStan extension that adds `compose-shapes<A, B, ...>`: a left-to-right recursive merge of sealed array shapes where the last-seen key wins.

Composition is not intersection. PHPStan can intersect unsealed shapes, but a sealed intersection collapses when the key sets differ. This operator simply glues concrete sealed shapes together—keys present on only one side are kept, later keys overwrite earlier ones, and nested sealed shapes are merged recursively—without treating the operation as a type-theoretic intersection.

It is intended for already-resolved sealed constant shapes (inline shapes or `@phpstan-type` aliases). Template types, unions, unsealed shapes, and general `array<...>` arguments are rejected, as they fall outside that model.

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

`compose-shapes` is useful when you have a sealed shape you want to reuse and extend, rather than writing the combined shape out by hand.

### Extending a base shape

```php
/**
 * @phpstan-type CoreSettings array{
 *   "cache"?: array{
 *     "driver"?: string,
 *     "ttl"?: int,
 *   },
 *   "debug"?: array{
 *     "enabled"?: bool,
 *   },
 * }
 *
 * @phpstan-type AddonSettings array{
 *   "metrics"?: array{
 *     "enabled"?: bool,
 *     "sample_rate"?: float,
 *   },
 *   "queue"?: array{
 *     "driver"?: string,
 *   },
 * }
 *
 * @phpstan-type Settings compose-shapes<CoreSettings, AddonSettings>
 */
```

`Settings` is the core shape with the addon keys added. You do not have to repeat the `"cache"` and `"debug"` definitions, and later keys simply appear alongside them.

Writing the full shape by hand would duplicate every key from the base. Intersection (`CoreSettings & AddonSettings`) is the wrong tool here: sealed shapes with differing key sets do not compose that way.

### Nested merge

When both sides supply the same key and both values are themselves sealed shapes, those values are merged recursively:

```php
/**
 * @phpstan-type Base array{
 *   "cache"?: array{
 *     "driver"?: string,
 *     "options"?: array{"a"?: int},
 *   },
 * }
 *
 * @phpstan-type Extra array{
 *   "cache"?: array{
 *     "options"?: array{"b"?: int},
 *     "prefetch"?: bool,
 *   },
 * }
 *
 * @phpstan-type Combined compose-shapes<Base, Extra>
 */
```

`Combined` is:

```php
array{
  "cache"?: array{
    "driver"?: string,
    "options"?: array{"a"?: int, "b"?: int},
    "prefetch"?: bool,
  },
}
```

The `"cache"` shapes are merged rather than the right-hand one simply replacing the left-hand one.

### Overwrite

Later arguments still win on non-shape clashes:

```php
/**
 * @param compose-shapes<
 *   array{"flag"?: bool, "nested"?: array{"a"?: int}},
 *   array{"flag"?: int, "nested"?: array{"b"?: int}},
 * > $row
 */
```

That is `array{"flag"?: int, "nested"?: array{"a"?: int, "b"?: int}}`.

Any number of shapes can be composed; each is merged left-to-right.

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
