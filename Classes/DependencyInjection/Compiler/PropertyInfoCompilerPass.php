<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\DependencyInjection\Compiler;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

final class PropertyInfoCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(PropertyListExtractorInterface::class)
            ->addTag('property_info.list_extractor');
        $container->registerForAutoconfiguration(PropertyTypeExtractorInterface::class)
            ->addTag('property_info.type_extractor');
        $container->registerForAutoconfiguration(PropertyDescriptionExtractorInterface::class)
            ->addTag('property_info.description_extractor');
        $container->registerForAutoconfiguration(PropertyAccessExtractorInterface::class)
            ->addTag('property_info.access_extractor');
        $container->registerForAutoconfiguration(PropertyInitializableExtractorInterface::class)
            ->addTag('property_info.initializable_extractor');

        if (
            ContainerBuilder::willBeAvailable(
                'phpstan/phpdoc-parser',
                PhpDocParser::class,
                ['symfony/framework-bundle', 'symfony/property-info']
            )
            && ContainerBuilder::willBeAvailable(
                'phpdocumentor/type-resolver',
                ContextFactory::class,
                ['symfony/framework-bundle', 'symfony/property-info']
            )
        ) {
            $definition = $container->register('property_info.phpstan_extractor', PhpStanExtractor::class);
            $definition->addTag('property_info.type_extractor', [
                'priority' => -1000,
            ]);
        }

        if (ContainerBuilder::willBeAvailable(
            'phpdocumentor/reflection-docblock',
            DocBlockFactoryInterface::class,
            ['symfony/framework-bundle', 'symfony/property-info'],
            true
        )) {
            $definition = $container->register('property_info.php_doc_extractor', PhpDocExtractor::class);
            $definition->addTag('property_info.description_extractor', [
                'priority' => -1000,
            ]);
            $definition->addTag('property_info.type_extractor', [
                'priority' => -1001,
            ]);
        }
    }
}
