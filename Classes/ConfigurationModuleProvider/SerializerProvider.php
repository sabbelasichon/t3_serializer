<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\ConfigurationModuleProvider;

use Ssch\T3Serializer\DependencyInjection\ConfigurationCollector;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\AbstractProvider;

final class SerializerProvider extends AbstractProvider
{
    /**
     * @var ConfigurationCollector[]
     */
    private iterable $configurationCollectors;

    /**
     * @param ConfigurationCollector[] $configurationCollectors
     */
    public function __construct(iterable $configurationCollectors)
    {
        $this->configurationCollectors = $configurationCollectors;
    }

    public function getConfiguration(): array
    {
        $configuration = [];

        foreach ($this->configurationCollectors as $configurationCollector) {
            $configuration[$configurationCollector->getConfigurationName()] = $configurationCollector->collect();
        }

        return $configuration;
    }
}
