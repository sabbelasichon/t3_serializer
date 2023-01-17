<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\DependencyInjection;

use Ssch\T3Serializer\Contract\ConfigurationResolver;
use TYPO3\CMS\Core\Package\PackageManager;

final class ConfigurationCollector
{
    private PackageManager $packageManager;

    private string $configurationFileName;

    private ConfigurationResolver $configurationResolver;

    public function __construct(
        PackageManager $packageManager,
        ConfigurationResolver $configurationResolver,
        string $configurationFileName
    ) {
        $this->packageManager = $packageManager;
        $this->configurationFileName = basename($configurationFileName);
        $this->configurationResolver = $configurationResolver;
    }

    public function collect(): array
    {
        $config = new \ArrayObject();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $serializerConfigurationFile = $package->getPackagePath() . 'Configuration/' . $this->configurationFileName . '.php';
            if (file_exists($serializerConfigurationFile)) {
                $serializerInPackage = require $serializerConfigurationFile;
                if (is_array($serializerInPackage)) {
                    $config->exchangeArray(array_replace_recursive($config->getArrayCopy(), $serializerInPackage));
                }
            }
        }

        return $this->configurationResolver->resolve($config->getArrayCopy());
    }
}
