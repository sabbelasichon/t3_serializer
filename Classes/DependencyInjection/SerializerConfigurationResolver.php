<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\DependencyInjection;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class SerializerConfigurationResolver
{
    public function resolve(array $configuration): array
    {
        $resolver = new OptionsResolver();
        $this->configureDefaultOptions($resolver);

        return $resolver->resolve($configuration);
    }

    private function configureDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('name_converter', null)
            ->setInfo('name_converter', 'The service id of the NameConverter');
        $resolver->setDefault('enable_annotations', true);
        $resolver
            ->setDefault('circular_reference_handler', null)
            ->setInfo('circular_reference_handler', 'The service id of the CircularReferenceHandler');
        $resolver
            ->setDefault('max_depth_handler', null)
            ->setInfo('max_depth_handler', 'The service id of the MaxDepthHandler');
        $resolver->setDefault('default_context', []);
    }
}
