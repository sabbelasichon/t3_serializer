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
use Ssch\T3Serializer\DependencyInjection\SerializerConfigurationResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Yaml\Yaml;

final class SerializerCompilerPass implements CompilerPassInterface
{
    private SerializerConfigurationResolver $serializerConfigurationResolver;

    public function __construct(SerializerConfigurationResolver $serializerConfigurationResolver)
    {
        $this->serializerConfigurationResolver = $serializerConfigurationResolver;
    }

    public function process(ContainerBuilder $container): void
    {
        $config = $this->collectSerializerConfigurationsFromPackages();

        if (! class_exists(Yaml::class)) {
            $container->removeDefinition('serializer.encoder.yaml');
        }

        if (! class_exists(UnwrappingDenormalizer::class)) {
            $container->removeDefinition('serializer.denormalizer.unwrapping');
        }

        if (! class_exists(Headers::class)) {
            $container->removeDefinition('serializer.normalizer.mime_message');
        }

        if (isset($config['name_converter']) && $config['name_converter']) {
            $container->getDefinition('serializer.name_converter.metadata_aware')
                ->setArgument(1, new Reference($config['name_converter']));
        }

        if (isset($config['circular_reference_handler']) && $config['circular_reference_handler']) {
            $arguments = $container->getDefinition('serializer.normalizer.object')
                ->getArguments();
            $context = ($arguments[6] ?? []) + [
                'circular_reference_handler' => new Reference($config['circular_reference_handler']),
            ];
            $container->getDefinition('serializer.normalizer.object')
                ->setArgument(5, null);
            $container->getDefinition('serializer.normalizer.object')
                ->setArgument(6, $context);
        }

        if ($config['max_depth_handler'] ?? false) {
            $defaultContext = $container->getDefinition('serializer.normalizer.object')
                ->getArgument(6);
            $defaultContext += [
                'max_depth_handler' => new Reference($config['max_depth_handler']),
            ];
            $container->getDefinition('serializer.normalizer.object')
                ->replaceArgument(6, $defaultContext);
        }

        if (isset($config['default_context']) && $config['default_context']) {
            $container->setParameter('serializer.default_context', $config['default_context']);
        }
    }

    private function collectSerializerConfigurationsFromPackages(): array
    {
        $config = (new ConfigurationCollector(
            PackageManagerFactory::createPackageManager(),
            'Serializer.php'
        ))->collect();

        return $this->serializerConfigurationResolver->resolve($config->getArrayCopy());
    }
}
