<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Functional\Fixtures\Extensions\t3_serializer_test\Classes\Domain;

use TYPO3\CMS\Core\Type\Enumeration;

final class TypeEnumeration extends Enumeration
{
    public const TYPE1 = 'type1';

    public const TYPE2 = 'type2';
}
