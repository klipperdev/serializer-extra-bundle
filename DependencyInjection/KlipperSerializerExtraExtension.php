<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SerializerExtraBundle\DependencyInjection;

use Doctrine\Persistence\ManagerRegistry;
use Klipper\Bundle\SecurityBundle\KlipperSecurityBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperSerializerExtraExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (interface_exists(ManagerRegistry::class)) {
            $loader->load('doctrine.xml');
        }

        if (interface_exists(AuthorizationCheckerInterface::class) && class_exists(KlipperSecurityBundle::class)) {
            $loader->load('security.xml');
        }

        $container->setParameter('klipper_serializer_extra.excluded_bundles', $config['excluded_bundles']);
    }
}
