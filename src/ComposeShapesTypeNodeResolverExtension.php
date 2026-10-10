<?php declare(strict_types = 1);

namespace Invisnet\PHPStan\ComposeShapes;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

/**
 * Resolves `compose-shapes<A, B, ...>` to a sealed array shape produced by a
 * left-to-right recursive merge where the last-seen key wins.
 */
final class ComposeShapesTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{

	private TypeNodeResolver $typeNodeResolver;

	public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
	{
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if (!$typeNode instanceof GenericTypeNode) {
			return null;
		}

		if ($typeNode->type->name !== 'compose-shapes') {
			return null;
		}

		$arguments = $typeNode->genericTypes;
		if (count($arguments) < 2) {
			return new ErrorType();
		}

		$shapes = [];
		foreach ($arguments as $argument) {
			$resolved = $this->typeNodeResolver->resolve($argument, $nameScope);
			$shape = $this->asSealedConstantArray($resolved);
			if ($shape === null) {
				return new ErrorType();
			}
			$shapes[] = $shape;
		}

		$result = array_shift($shapes);
		foreach ($shapes as $shape) {
			$result = $this->merge($result, $shape);
			if ($result === null) {
				return new ErrorType();
			}
		}

		return $result;
	}

	private function asSealedConstantArray(Type $type): ?ConstantArrayType
	{
		if (TypeUtils::containsTemplateType($type)) {
			return null;
		}

		$arrays = $type->getConstantArrays();
		if (count($arrays) !== 1) {
			return null;
		}

		$array = $arrays[0];
		if ($array->isUnsealed()->yes()) {
			return null;
		}

		return $array;
	}

	private function merge(ConstantArrayType $left, ConstantArrayType $right): ?ConstantArrayType
	{
		/** @var array<string, array{key: ConstantIntegerType|ConstantStringType, value: Type, optional: bool}> $slots */
		$slots = [];

		foreach ($left->getKeyTypes() as $i => $keyType) {
			$slots[$this->keyId($keyType)] = [
				'key' => $keyType,
				'value' => $left->getValueTypes()[$i],
				'optional' => $left->isOptionalKey($i),
			];
		}

		foreach ($right->getKeyTypes() as $i => $keyType) {
			$id = $this->keyId($keyType);
			$rightValue = $right->getValueTypes()[$i];
			$rightOptional = $right->isOptionalKey($i);

			if (!isset($slots[$id])) {
				$slots[$id] = [
					'key' => $keyType,
					'value' => $rightValue,
					'optional' => $rightOptional,
				];
				continue;
			}

			$leftValue = $slots[$id]['value'];
			$leftShape = $this->asSealedConstantArray($leftValue);
			$rightShape = $this->asSealedConstantArray($rightValue);

			if ($leftShape !== null && $rightShape !== null) {
				$merged = $this->merge($leftShape, $rightShape);
				if ($merged === null) {
					return null;
				}
				$value = $merged;
			} else {
				$value = $rightValue;
			}

			$slots[$id] = [
				'key' => $keyType,
				'value' => $value,
				'optional' => $rightOptional,
			];
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->disableArrayDegradation();

		foreach ($slots as $slot) {
			$builder->setOffsetValueType($slot['key'], $slot['value'], $slot['optional']);
		}

		$result = $builder->getArray();
		$arrays = $result->getConstantArrays();
		if (count($arrays) !== 1) {
			return null;
		}

		return $arrays[0];
	}

	private function keyId(ConstantIntegerType|ConstantStringType $keyType): string
	{
		if ($keyType instanceof ConstantIntegerType) {
			return 'i:' . $keyType->getValue();
		}

		return 's:' . $keyType->getValue();
	}

}
