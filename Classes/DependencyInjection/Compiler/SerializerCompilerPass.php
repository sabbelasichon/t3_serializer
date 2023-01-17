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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Yaml\Yaml;

final class SerializerCompilerPass implements CompilerPassInterface
{
    private ConfigurationCollector $configurationCollector;

    public function __construct(ConfigurationCollector $configurationCollector)
    {
        $this->configurationCollector = $configurationCollector;
    }

    public function process(ContainerBuilder $container): void
    {
        $config = $this->configurationCollector->collect();
        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');

        $container->registerForAutoconfiguration(EncoderInterface::class)
            ->addTag('serializer.encoder');
        $container->registerForAutoconfiguration(DecoderInterface::class)
            ->addTag('serializer.decoder');
        $container->registerForAutoconfiguration(NormalizerInterface::class)
            ->addTag('serializer.normalizer');
        $container->registerForAutoconfiguration(DenormalizerInterface::class)
            ->addTag('serializer.normalizer');

        if (! class_exists(Yaml::class)) {
            $container->removeDefinition('serializer.encoder.yaml');
        }

        if (! class_exists(UnwrappingDenormalizer::class)) {
            $container->removeDefinition('serializer.denormalizer.unwrapping');
        }

        if (! class_exists(Headers::class)) {
            $container->removeDefinition('serializer.normalizer.mime_message');
        }

        $serializerLoaders = [];
        if (isset($config['enable_annotations']) && $config['enable_annotations']) {
            $annotationLoader = new Definition(
                AnnotationLoader::class,
                [new Reference('annotation_reader', ContainerInterface::NULL_ON_INVALID_REFERENCE)]
            );
            $annotationLoader->setPublic(false);

            $serializerLoaders[] = $annotationLoader;
        }

        $chainLoader->replaceArgument(0, $serializerLoaders);

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
}
