<?php declare(strict_types = 1);

namespace Invisnet\PHPStan\ComposeShapes;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ComposeShapesTypeInferenceTest extends TypeInferenceTestCase
{

	/**
	 * @return iterable<mixed>
	 */
	public static function dataFileAsserts(): iterable
	{
		yield from self::gatherAssertTypes(__DIR__ . '/Data/ComposeShapes.php');
		yield from self::gatherAssertTypes(__DIR__ . '/Data/ComposeShapesInvalid.php');
	}

	#[DataProvider('dataFileAsserts')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		mixed ...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		$phpstanPhar = dirname(__DIR__) . '/vendor/phpstan/phpstan/phpstan.phar';

		return [
			'phar://' . $phpstanPhar . '/conf/bleedingEdge.neon',
			dirname(__DIR__) . '/extension.neon',
		];
	}

}
