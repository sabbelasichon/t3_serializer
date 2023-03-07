<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Psr\Cache\CacheItemPoolInterface;
use Ssch\Cache\Factory\Psr6Factory;
use Ssch\T3Serializer\ConfigurationModuleProvider\SerializerProvider;
use Ssch\T3Serializer\DependencyInjection\Compiler\PropertyAccessCompilerPass;
use Ssch\T3Serializer\DependencyInjection\Compiler\PropertyInfoCompilerPass;
use Ssch\T3Serializer\DependencyInjection\Compiler\SerializerCompilerPass;
use Ssch\T3Serializer\DependencyInjection\ConfigurationCollector;
use Ssch\T3Serializer\DependencyInjection\PackageManagerFactory;
use Ssch\T3Serializer\DependencyInjection\PropertyAccessConfigurationResolver;
use Ssch\T3Serializer\DependencyInjection\SerializerConfigurationResolver;
use Ssch\T3Serializer\Serializer\Normalizer\EnumerationNormalizer;
use Ssch\T3Serializer\Serializer\Normalizer\ObjectStorageNormalizer;
use Ssch\T3Serializer\Serializer\Normalizer\ValidationResultNormalizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\DependencyInjection\PropertyInfoPass;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\MimeMessageNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $services->load('Ssch\\T3Serializer\\', __DIR__ . '/../Classes/')
        ->exclude([__DIR__ . '/../Classes/DependencyInjection/']);

    $containerConfigurator->parameters()
        ->set('kernel.debug', ! Environment::getContext()->isProduction());

    // Lowlevel Configuration Provider
    $services->set(SerializerConfigurationResolver::class);
    $services->set('serializer.serializer_configuration_collector', ConfigurationCollector::class)
        ->args([service(PackageManager::class), service(SerializerConfigurationResolver::class)])
        ->tag('serializer.configuration_collector');

    $services->set(PropertyAccessConfigurationResolver::class);
    $services->set('serializer.property_access_configuration_collector', ConfigurationCollector::class)
        ->args([service(PackageManager::class), service(PropertyAccessConfigurationResolver::class)])
        ->tag('serializer.configuration_collector');

    $services->set(SerializerProvider::class)
        ->args([tagged_iterator('serializer.configuration_collector')])
        ->tag(
            'lowlevel.configuration.module.provider',
            [
                'identifier' => 'serializer',
                'label' => 'Serializer Configuration',
                'after' => 'mfaProviders',
            ]
        );

    $services
        ->set('serializer', Serializer::class)
        ->args([[], []])
        ->alias(SerializerInterface::class, 'serializer')
        ->alias(NormalizerInterface::class, 'serializer')
        ->alias(DenormalizerInterface::class, 'serializer')
        ->alias(EncoderInterface::class, 'serializer')
        ->alias(DecoderInterface::class, 'serializer')
        ->alias('serializer.property_accessor', 'property_accessor');

    $services
        // Discriminator Map
        ->set('serializer.mapping.class_discriminator_resolver', ClassDiscriminatorFromClassMetadata::class)
        ->args([service('serializer.mapping.class_metadata_factory')])
        ->alias(ClassDiscriminatorResolverInterface::class, 'serializer.mapping.class_discriminator_resolver')

        // Normalizer
        ->set('serializer.normalizer.object_storage', ObjectStorageNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -900,
        ])
        ->set('serializer.normalizer.enumeration', EnumerationNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -900,
        ])
        ->set('serializer.normalizer.mime_message', MimeMessageNormalizer::class)
        ->args([service('serializer.normalizer.property')])
        ->tag('serializer.normalizer', [
            'priority' => -915,
        ])
        ->set('serializer.normalizer.datetimezone', DateTimeZoneNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -915,
        ])
        ->set('serializer.normalizer.dateinterval', DateIntervalNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -915,
        ])
        ->set('serializer.normalizer.data_uri', DataUriNormalizer::class)
        ->args([service('mime_types')->nullOnInvalid()])
        ->tag('serializer.normalizer', [
            'priority' => -920,
        ])
        ->set('serializer.normalizer.error_violation_list', ValidationResultNormalizer::class)
        ->args([
            1 => service('serializer.name_converter.metadata_aware'),
        ])
        ->tag('serializer.normalizer', [
            'priority' => -915,
        ])
        ->set('serializer.normalizer.datetime', DateTimeNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -910,
        ])
        ->set('serializer.normalizer.json_serializable', JsonSerializableNormalizer::class)
        ->args([null, null])
        ->tag('serializer.normalizer', [
            'priority' => -950,
        ])
        ->set('serializer.denormalizer.unwrapping', UnwrappingDenormalizer::class)
        ->args([service('serializer.property_accessor')])
        ->tag('serializer.normalizer', [
            'priority' => 1000,
        ])
        ->set('serializer.normalizer.object', ObjectNormalizer::class)
        ->args([
            service('serializer.mapping.class_metadata_factory'),
            service('serializer.name_converter.metadata_aware'),
            service('serializer.property_accessor'),
            service('property_info')
                ->ignoreOnInvalid(),
            service('serializer.mapping.class_discriminator_resolver')
                ->ignoreOnInvalid(),
            null,
        ])
        ->tag('serializer.normalizer', [
            'priority' => -1000,
        ])
        ->alias(ObjectNormalizer::class, 'serializer.normalizer.object')
        ->deprecate(
            'symfony/serializer',
            '6.2',
            'The "%alias_id%" service alias is deprecated, type-hint against "' . NormalizerInterface::class . '" or implement "' . NormalizerAwareInterface::class . '" instead.'
        )
        ->set('serializer.normalizer.property', PropertyNormalizer::class)
        ->args([
            service('serializer.mapping.class_metadata_factory'),
            service('serializer.name_converter.metadata_aware'),
            service('property_info')
                ->ignoreOnInvalid(),
            service('serializer.mapping.class_discriminator_resolver')
                ->ignoreOnInvalid(),
            null,
        ])
        ->alias(PropertyNormalizer::class, 'serializer.normalizer.property')
        ->deprecate(
            'symfony/serializer',
            '6.2',
            'The "%alias_id%" service alias is deprecated, type-hint against "' . NormalizerInterface::class . '" or implement "' . NormalizerAwareInterface::class . '" instead.'
        )
        ->set('serializer.denormalizer.array', ArrayDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => -990,
        ])

        // Loader
        ->set('serializer.mapping.chain_loader', LoaderChain::class)
        ->args([[]])

        // Class Metadata Factory
        ->set('serializer.mapping.class_metadata_factory', ClassMetadataFactory::class)
        ->args([service('serializer.mapping.chain_loader')])
        ->alias(ClassMetadataFactoryInterface::class, 'serializer.mapping.class_metadata_factory')

        // Cache
        ->set('serializer.mapping.cache', CacheItemPoolInterface::class)
        ->factory([service(Psr6Factory::class), 'create'])
        ->args(['t3_serializer_class_metadata'])
        ->set('serializer.mapping.cache_class_metadata_factory', CacheClassMetadataFactory::class)
        ->decorate('serializer.mapping.class_metadata_factory')
        ->args([
            service('serializer.mapping.cache_class_metadata_factory.inner'),
            service('serializer.mapping.cache'),
        ])

        // Encoders
        ->set('serializer.encoder.xml', XmlEncoder::class)
        ->tag('serializer.encoder')
        ->set('serializer.encoder.json', JsonEncoder::class)
        ->args([null, null])
        ->tag('serializer.encoder')
        ->set('serializer.encoder.yaml', YamlEncoder::class)
        ->args([null, null])
        ->tag('serializer.encoder')
        ->set('serializer.encoder.csv', CsvEncoder::class)
        ->tag('serializer.encoder')

        // Name converter
        ->set('serializer.name_converter.camel_case_to_snake_case', CamelCaseToSnakeCaseNameConverter::class)
        ->set('serializer.name_converter.metadata_aware', MetadataAwareNameConverter::class)
        ->args([service('serializer.mapping.class_metadata_factory')])

        // PropertyInfo extractor
        ->set('property_info.serializer_extractor', SerializerExtractor::class)
        ->args([service('serializer.mapping.class_metadata_factory')])
        ->tag('property_info.list_extractor', [
            'priority' => -999,
        ]);

    if (interface_exists(\BackedEnum::class)) {
        $containerConfigurator->services()
            ->set('serializer.normalizer.backed_enum', BackedEnumNormalizer::class)
            ->tag('serializer.normalizer', [
                'priority' => -915,
            ]);
    }

    $services
        ->set('property_accessor', PropertyAccessor::class)
        ->args([
            abstract_arg('magic methods allowed, set by the extension'),
            abstract_arg('throw exceptions, set by the extension'),
            service('cache.property_access')
                ->ignoreOnInvalid(),
            abstract_arg('propertyReadInfoExtractor, set by the extension'),
            abstract_arg('propertyWriteInfoExtractor, set by the extension'),
        ])
        ->alias(PropertyAccessorInterface::class, 'property_accessor');

    $services->set('cache.property_info', CacheItemPoolInterface::class)
        ->factory([service(Psr6Factory::class), 'create'])
        ->args(['t3_serializer_property_info']);

    $services
        ->set('property_info', PropertyInfoExtractor::class)
        ->args([[], [], [], [], []])
        ->alias(PropertyAccessExtractorInterface::class, 'property_info')
        ->alias(PropertyDescriptionExtractorInterface::class, 'property_info')
        ->alias(PropertyInfoExtractorInterface::class, 'property_info')
        ->alias(PropertyTypeExtractorInterface::class, 'property_info')
        ->alias(PropertyListExtractorInterface::class, 'property_info')
        ->alias(PropertyInitializableExtractorInterface::class, 'property_info')
        ->set('property_info.cache', PropertyInfoCacheExtractor::class)
        ->decorate('property_info')
        ->args([service('property_info.cache.inner'), service('cache.property_info')])

        // Extractor
        ->set('property_info.reflection_extractor', ReflectionExtractor::class)
        ->tag('property_info.list_extractor', [
            'priority' => -1000,
        ])
        ->tag('property_info.type_extractor', [
            'priority' => -1002,
        ])
        ->tag('property_info.access_extractor', [
            'priority' => -1000,
        ])
        ->tag('property_info.initializable_extractor', [
            'priority' => -1000,
        ])
        ->alias(PropertyReadInfoExtractorInterface::class, 'property_info.reflection_extractor')
        ->alias(PropertyWriteInfoExtractorInterface::class, 'property_info.reflection_extractor');

    $services
        ->set('annotations.reader', AnnotationReader::class)
        ->call('addGlobalIgnoredName', [
            'required',
            service('annotations.dummy_registry')
                ->ignoreOnInvalid(), // dummy arg to register class_exists as annotation loader only when required
        ])
        ->set('annotations.dummy_registry', AnnotationRegistry::class)
        ->call('registerUniqueLoader', ['class_exists'])
        ->alias('annotation_reader', 'annotations.reader')
        ->alias(Reader::class, 'annotation_reader');

    $propertyAccessConfigurationCollector = new ConfigurationCollector(
        PackageManagerFactory::createPackageManager(),
        new PropertyAccessConfigurationResolver(),
    );

    $serializerConfigurationCollector = new ConfigurationCollector(
        PackageManagerFactory::createPackageManager(),
        new SerializerConfigurationResolver(),
    );

    $containerBuilder->addCompilerPass(new PropertyAccessCompilerPass($propertyAccessConfigurationCollector));
    $containerBuilder->addCompilerPass(new PropertyInfoCompilerPass());
    $containerBuilder->addCompilerPass(new SerializerCompilerPass($serializerConfigurationCollector));
    $containerBuilder->addCompilerPass(new PropertyInfoPass());
    $containerBuilder->addCompilerPass(new SerializerPass());
};
