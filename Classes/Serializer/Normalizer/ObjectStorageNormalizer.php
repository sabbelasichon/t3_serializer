<?php
declare(strict_types=1);


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
        if(!$object instanceof ObjectStorage) {
            throw new \InvalidArgumentException(sprintf('Object must be of type "%s"', ObjectStorage::class));
        }

        if (!$this->normalizer instanceof NormalizerInterface) {
            throw new \BadMethodCallException(sprintf('The "%s()" method cannot be called as nested normalizer doesn\'t implements "%s".', __METHOD__, NormalizerInterface::class));
        }

        return array_map(function($item) use($format, $context) {
            return $this->normalizer->normalize($item, $format, $context);
        }, $object->toArray());
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        if (!$this->normalizer instanceof NormalizerInterface) {
            return false;
        }

        return $data instanceof ObjectStorage;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->normalizer instanceof CacheableSupportsMethodInterface && $this->normalizer->hasCacheableSupportsMethod();
    }
}
