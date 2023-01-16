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

final class PropertyAccessConfigurationResolver
{
    public function resolve(array $configuration): array
    {
        $resolver = new OptionsResolver();
        $this->configureDefaultOptions($resolver);

        return $resolver->resolve($configuration);
    }

    private function configureDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('magic_call', false);
        $resolver->setDefault('magic_get', true);
        $resolver->setDefault('magic_set', true);
        $resolver->setDefault('throw_exception_on_invalid_index', false);
        $resolver->setDefault('throw_exception_on_invalid_property_path', true);
    }
}
