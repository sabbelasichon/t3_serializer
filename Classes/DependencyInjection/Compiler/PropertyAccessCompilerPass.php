<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\DependencyInjection\Compiler;

use Ssch\T3Serializer\DependencyInjection\ConfigurationCollector;
use Ssch\T3Serializer\DependencyInjection\PackageManagerFactory;
use Ssch\T3Serializer\DependencyInjection\PropertyAccessConfigurationResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

final class PropertyAccessCompilerPass implements CompilerPassInterface
{
    private PropertyAccessConfigurationResolver $propertyAccessConfigurationResolver;

    public function __construct(PropertyAccessConfigurationResolver $propertyAccessConfigurationResolver)
    {
        $this->propertyAccessConfigurationResolver = $propertyAccessConfigurationResolver;
    }

    public function process(ContainerBuilder $container): void
    {
        $config = $this->collectPropertyAccessConfigurationsFromPackages();

        $magicMethods = PropertyAccessor::DISALLOW_MAGIC_METHODS;
        $magicMethods |= $config['magic_call'] ? PropertyAccessor::MAGIC_CALL : 0;
        $magicMethods |= $config['magic_get'] ? PropertyAccessor::MAGIC_GET : 0;
        $magicMethods |= $config['magic_set'] ? PropertyAccessor::MAGIC_SET : 0;

        $throw = PropertyAccessor::DO_NOT_THROW;
        $throw |= $config['throw_exception_on_invalid_index'] ? PropertyAccessor::THROW_ON_INVALID_INDEX : 0;
        $throw |= $config['throw_exception_on_invalid_property_path'] ? PropertyAccessor::THROW_ON_INVALID_PROPERTY_PATH : 0;

        $container
            ->getDefinition('property_accessor')
            ->replaceArgument(0, $magicMethods)
            ->replaceArgument(1, $throw)
            ->replaceArgument(
                3,
                new Reference(PropertyReadInfoExtractorInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE)
            )
            ->replaceArgument(
                4,
                new Reference(PropertyWriteInfoExtractorInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE)
            )
        ;
    }

    private function collectPropertyAccessConfigurationsFromPackages(): array
    {
        $config = (new ConfigurationCollector(
            PackageManagerFactory::createPackageManager(),
            'PropertyAccess.php'
        ))->collect();

        return $this->propertyAccessConfigurationResolver->resolve($config->getArrayCopy());
    }
}
