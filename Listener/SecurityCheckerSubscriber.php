<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SerializerExtraBundle\Listener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\PropertyMetadata;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Security\Permission\PermissionManagerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SecurityCheckerSubscriber implements EventSubscriberInterface
{
    private PermissionManagerInterface $permissionManager;

    private array $cache = [];

    public function __construct(PermissionManagerInterface $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ],
        ];
    }

    public function onPreSerialize(ObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!\is_object($object) || !$this->permissionManager->isManaged($object)) {
            return;
        }

        $class = ClassUtils::getClass($object);

        if (!\in_array($class, $this->cache, true)) {
            $this->cache[] = $class;
            $classMeta = $event->getContext()->getMetadataFactory()->getMetadataForClass($class);

            if (null !== $classMeta) {
                /** @var PropertyMetadata $propertyMeta */
                foreach ($classMeta->propertyMetadata as $propertyMeta) {
                    if (null === $propertyMeta->excludeIf) {
                        $propertyMeta->excludeIf = sprintf(
                            '!is_granted("perm:read", [object, "%s"])',
                            $propertyMeta->name
                        );
                    }
                }
            }
        }
    }
}
