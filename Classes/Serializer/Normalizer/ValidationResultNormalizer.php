<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Serializer\Normalizer;

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use TYPO3\CMS\Extbase\Error\Result;

/**
 * A normalizer that normalizes a Result instance.
 *
 * This Normalizer implements RFC7807 {@link https://tools.ietf.org/html/rfc7807}.
 */
final class ValidationResultNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const INSTANCE = 'instance';

    public const STATUS = 'status';

    public const TITLE = 'title';

    public const TYPE = 'type';

    private array $defaultContext;

    private ?AdvancedNameConverterInterface $nameConverter;

    public function __construct(array $defaultContext = [], AdvancedNameConverterInterface $nameConverter = null)
    {
        $this->defaultContext = $defaultContext;
        $this->nameConverter = $nameConverter;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        if (! $object instanceof Result) {
            throw new \InvalidArgumentException('Supports only normalization of type ' . Result::class);
        }

        $violations = [];
        $messages = [];
        foreach ($object->getFlattenedErrors() as $propertyPath => $propertyErrors) {
            $propertyPath = $this->nameConverter !== null ? $this->nameConverter->normalize(
                $propertyPath,
                null,
                $format,
                $context
            ) : $propertyPath;

            foreach ($propertyErrors as $propertyError) {
                $violationEntry = [
                    'propertyPath' => $propertyPath,
                    'title' => $propertyError->getTitle(),
                    'parameters' => $propertyError->getArguments(),
                ];
                $code = $propertyError->getCode();
                if ($code !== null) {
                    $violationEntry['type'] = sprintf('urn:uuid:%s', $code);
                }

                $violations[] = $violationEntry;

                $prefix = $propertyPath !== '' ? sprintf('%s: ', $propertyPath) : '';
                $messages[] = $prefix . $propertyError->getMessage();
            }
        }

        $result = [
            'type' => $context[self::TYPE] ?? $this->defaultContext[self::TYPE] ?? 'https://typo3.org/errors/validation',
            'title' => $context[self::TITLE] ?? $this->defaultContext[self::TITLE] ?? 'Validation Failed',
        ];

        $status = $context[self::STATUS] ?? $this->defaultContext[self::STATUS] ?? null;
        if ($status !== null) {
            $result['status'] = $status;
        }
        if ($messages !== []) {
            $result['detail'] = implode("\n", $messages);
        }

        $instance = $context[self::INSTANCE] ?? $this->defaultContext[self::INSTANCE] ?? null;
        if ($instance !== null) {
            $result['instance'] = $instance;
        }

        return $result + [
            'violations' => $violations,
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Result;
    }
}
