<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use TYPO3\CMS\Core\Type\Enumeration;

final class EnumerationNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (! is_subclass_of($type, Enumeration::class)) {
            throw new InvalidArgumentException('The data must belong to an enumeration.');
        }

        if (! \is_int($data) && ! \is_string($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is neither an integer nor a string, you should pass an integer or a string that can be parsed as an enumeration case of type ' . $type . '.',
                $data,
                [Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_STRING],
                $context['deserialization_path'] ?? null,
                true
            );
        }

        try {
            return $type::cast($data);
        } catch (\ValueError $e) {
            throw new InvalidArgumentException('The data must belong to an enumeration of type ' . $type);
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_subclass_of($type, Enumeration::class);
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        if (! $object instanceof Enumeration) {
            throw new InvalidArgumentException('The data must belong to an enumeration.');
        }

        return $object->__toString();
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Enumeration;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
