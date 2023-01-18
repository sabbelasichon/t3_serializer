<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class ObjectStorageNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    public function normalize($object, string $format = null, array $context = [])
    {
        if (! $object instanceof ObjectStorage) {
            throw new \InvalidArgumentException(sprintf('Object must be of type "%s"', ObjectStorage::class));
        }

        return array_map(function ($item) use ($format, $context) {
            return $this->normalizer->normalize($item, $format, $context);
        }, $object->toArray());
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof ObjectStorage;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->normalizer instanceof CacheableSupportsMethodInterface && $this->normalizer->hasCacheableSupportsMethod();
    }
}
