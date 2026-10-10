<?php declare(strict_types = 1);

namespace Invisnet\PHPStan\ComposeShapes\Data;

use function PHPStan\Testing\assertType;

final class ComposeShapesInvalid
{

	public function check(): void
	{
		assertType('*ERROR*', $this->tooFewArguments());
		assertType('*ERROR*', $this->unsealedRejected());
		assertType('*ERROR*', $this->generalArrayRejected());
		assertType('*ERROR*', $this->unionRejected());
	}

	/**
	 * @return compose-shapes<array{a: int}>
	 */
	private function tooFewArguments()
	{
		return [];
	}

	/**
	 * @return compose-shapes<array{a: int, ...}, array{b: string}>
	 */
	private function unsealedRejected()
	{
		return [];
	}

	/**
	 * @return compose-shapes<array<string, int>, array{b: string}>
	 */
	private function generalArrayRejected()
	{
		return [];
	}

	/**
	 * @return compose-shapes<array{a: int}|array{b: string}, array{c: bool}>
	 */
	private function unionRejected()
	{
		return [];
	}

}
