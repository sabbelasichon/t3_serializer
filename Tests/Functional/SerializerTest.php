<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Functional;

use Ssch\T3Serializer\Tests\Functional\Fixtures\Extensions\t3_serializer_test\Classes\Service\MyService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SerializerTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/t3_serializer',
        'typo3conf/ext/t3_serializer/Tests/Functional/Fixtures/Extensions/t3_serializer_test',
    ];

    private MyService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(MyService::class);
    }

    public function testThatObjectIsSuccessfullySerializedToJson(): void
    {
        $object = new \stdClass();
        $object->firstName = 'Max';
        $object->lastName = 'Mustermann';
        self::assertJsonStringEqualsJsonFile(
            __DIR__ . '/Fixtures/Serializer/expected.json',
            $this->subject->serialize($object)
        );
    }
}
