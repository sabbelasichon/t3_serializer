<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Functional;

use Spatie\Snapshots\MatchesSnapshots;
use Ssch\T3Serializer\Tests\Functional\Fixtures\Extensions\t3_serializer_test\Classes\Domain\Person;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SerializerTest extends FunctionalTestCase
{
    use MatchesSnapshots;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/typo3_psr_cache_adapter',
        'typo3conf/ext/t3_serializer',
        'typo3conf/ext/t3_serializer/Tests/Functional/Fixtures/Extensions/t3_serializer_test',
    ];

    private SerializerInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get('serializer');
    }

    public function testThatObjectIsSuccessfullySerializedToJson(): void
    {
        $person1 = new Person('Torsten', 'Müller');
        $person2 = new Person('Frank', 'Müller');

        $object = new Person('max', 'mustermann');
        $object->addPerson($person1);
        $object->addPerson($person2);

        $this->assertMatchesJsonSnapshot($this->subject->serialize($object, JsonEncoder::FORMAT));
    }
}
