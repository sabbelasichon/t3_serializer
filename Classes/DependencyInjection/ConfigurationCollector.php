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

final class ConfigurationCollector
{
    private PackageManager $packageManager;

    private string $configurationFileName;

    public function __construct(PackageManager $packageManager, string $configurationFileName)
    {
        $this->packageManager = $packageManager;
        $this->configurationFileName = $configurationFileName;
    }

    public function collect(): \ArrayObject
    {
        $config = new \ArrayObject();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $serializerConfigurationFile = $package->getPackagePath() . 'Configuration/' . $this->configurationFileName;
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
