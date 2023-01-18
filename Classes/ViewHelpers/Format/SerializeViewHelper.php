<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\ViewHelpers\Format;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class SerializeViewHelper extends AbstractViewHelper
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('data', 'mixed', 'The data to serialize');
        $this->registerArgument('format', 'string', 'The format to serialize the data to', false, JsonEncoder::FORMAT);
        $this->registerArgument('context', 'array', 'Additional context to pass to the serializer', false, []);
    }

    public function render(): string
    {
        $data = $this->arguments['data'] ?? $this->renderChildren();

        return $this->serializer->serialize($data, $this->arguments['format'], $this->arguments['context']);
    }
}
