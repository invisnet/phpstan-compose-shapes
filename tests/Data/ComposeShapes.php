<?php declare(strict_types = 1);

namespace Invisnet\PHPStan\ComposeShapes\Data;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Left array{foo: bool, nested?: array{a?: int}}
 * @phpstan-type Right array{bar: string, nested?: array{b?: int}}
 * @phpstan-type Overwrite array{foo: int}
 */
final class ComposeShapes
{

	/**
	 * @param compose-shapes<array{a: int}, array{b: string}> $value
	 */
	public function disjoint(array $value): void
	{
		assertType('array{a: int, b: string}', $value);
	}

	/**
	 * @param compose-shapes<Left, Right> $value
	 */
	public function nestedMerge(array $value): void
	{
		assertType('array{foo: bool, nested?: array{a?: int, b?: int}, bar: string}', $value);
	}

	/**
	 * @param compose-shapes<Left, Right, Overwrite> $value
	 */
	public function thirdArgumentWins(array $value): void
	{
		assertType('array{foo: int, nested?: array{a?: int, b?: int}, bar: string}', $value);
	}

	/**
	 * @param compose-shapes<array{foo?: int}, array{foo: string}> $value
	 */
	public function rightOptionalityWins(array $value): void
	{
		assertType('array{foo: string}', $value);
	}

	/**
	 * @param compose-shapes<array{foo: int}, array{foo?: string}> $value
	 */
	public function rightOptionalOverwritesRequired(array $value): void
	{
		assertType('array{foo?: string}', $value);
	}

	/**
	 * @param compose-shapes<array{nested: array{a: int}}, array{nested: bool}> $value
	 */
	public function nestedLeafOverwrite(array $value): void
	{
		assertType('array{nested: bool}', $value);
	}

	/**
	 * @param compose-shapes<non-empty-array{a: int}, array{b: string}> $value
	 */
	public function nonEmptyAccessoryAccepted(array $value): void
	{
		assertType('array{a: int, b: string}', $value);
	}

}
