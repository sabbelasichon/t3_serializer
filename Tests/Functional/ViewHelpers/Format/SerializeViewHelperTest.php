<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Functional\ViewHelpers\Format;

use Spatie\Snapshots\MatchesSnapshots;
use Ssch\T3Serializer\Tests\Functional\Fixtures\Extensions\t3_serializer_test\Classes\Domain\Person;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SerializeViewHelperTest extends FunctionalTestCase
{
    use MatchesSnapshots;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/t3_serializer',
        'typo3conf/ext/t3_serializer/Tests/Functional/Fixtures/Extensions/t3_serializer_test',
    ];

    public function testSerialization(): void
    {
        $person1 = new Person('Torsten', 'Müller');
        $person2 = new Person('Frank', 'Müller');

        $object = new Person('max', 'mustermann');
        $object->addPerson($person1);
        $object->addPerson($person2);

        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $view->assign('data', $object);
        $view->getRenderingContext()
            ->getViewHelperResolver()
            ->addNamespace('s', 'Ssch\\T3Serializer\\ViewHelpers');

        $view->setTemplateSource('<s:format.serialize>{data}</s:format.serialize>');

        $actual = $view->render();
        $this->assertMatchesJsonSnapshot($actual);
    }
}
