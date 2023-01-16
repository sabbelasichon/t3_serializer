<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\DependencyInjection;

use TYPO3\CMS\Core\Package\PackageManager;

final class SerializerConfigurationCollector
{
    private PackageManager $packageManager;

    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    public function collect(): \ArrayObject
    {
        $config = new \ArrayObject();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $serializerConfigurationFile = $package->getPackagePath() . 'Configuration/Serializer.php';
            if (file_exists($serializerConfigurationFile)) {
                $serializerInPackage = require $serializerConfigurationFile;
                if (is_array($serializerInPackage)) {
                    $config->exchangeArray(array_replace_recursive($config->getArrayCopy(), $serializerInPackage));
                }
            }
        }

        return $config;
    }
}
