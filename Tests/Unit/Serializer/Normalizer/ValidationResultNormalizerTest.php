<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Unit\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Ssch\T3Serializer\Serializer\Normalizer\ValidationResultNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;

final class ValidationResultNormalizerTest extends TestCase
{
    use MatchesSnapshots;

    public function testThatAnExceptionIsThrownWhenNormalizationIsNotPossible(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Arrange
        $subject = new ValidationResultNormalizer();

        // Act
        $subject->normalize('foo');
    }

    public function testThatViolationsArrayIsCorrectlyBuild(): void
    {
        // Arrange
        $subject = new ValidationResultNormalizer();
        $result = new Result();
        $result->forProperty('title')
            ->addError(new Error('No valid title given', 1678219993, [
                'argument' => '1',
            ], 'Title is invalid'));
        $result->forProperty('message')
            ->addError(new Error('No valid message given', 1678219993, [], 'Message is invalid'));

        // Act
        $violations = $subject->normalize($result, JsonEncoder::FORMAT, [
            ValidationResultNormalizer::STATUS => 405,
            ValidationResultNormalizer::TYPE => 'Type',
            ValidationResultNormalizer::TITLE => 'Validation errors occurred',
            ValidationResultNormalizer::INSTANCE => 'instance',
        ]);

        // Assert
        $this->assertMatchesJsonSnapshot(json_encode($violations));
    }
}
