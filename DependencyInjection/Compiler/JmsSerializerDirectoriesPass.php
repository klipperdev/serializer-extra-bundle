<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SerializerExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class JmsSerializerDirectoriesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @throws
     */
    public function process(ContainerBuilder $container): void
    {
        $def = $container->getDefinition('jms_serializer.metadata.traceable_file_locator');
        $excludedBundles = $container->getParameter('klipper_serializer_extra.excluded_bundles');
        $bundles = $container->getParameter('kernel.bundles');
        $dirs = $def->getArgument(0);

        foreach ($bundles as $name => $bundleClass) {
            if (\in_array($bundleClass, $excludedBundles, true) || \in_array($name, $excludedBundles, true)) {
                continue;
            }

            $ref = new \ReflectionClass($bundleClass);
            $basePath = \dirname($ref->getFileName()).'/Resources/config/serializer';

            if (is_dir($basePath)) {
                $scanPaths = array_diff(scandir($basePath), ['.', '..']);

                foreach ($scanPaths as $scanPath) {
                    $ns = str_replace('.', '\\', $scanPath);
                    $dirs[$ns] = realpath($basePath.\DIRECTORY_SEPARATOR.$scanPath);
                }
            }
        }

        $def->setArgument(0, $dirs);
        $container->getParameterBag()->remove('klipper_serializer_extra.excluded_bundles');
    }
}
