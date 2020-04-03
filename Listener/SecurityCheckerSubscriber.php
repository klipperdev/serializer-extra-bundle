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
use Klipper\Component\Security\Permission\PermissionManagerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SecurityCheckerSubscriber implements EventSubscriberInterface
{
    /**
     * @var PermissionManagerInterface
     */
    private $permissionManager;

    /**
     * Constructor.
     *
     * @param PermissionManagerInterface $permissionManager The permission manager
     */
    public function __construct(PermissionManagerInterface $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event The event
     */
    public function onPreSerialize(ObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!\is_object($object) || !$this->permissionManager->isManaged($object)) {
            return;
        }

        $classMeta = $event->getContext()->getMetadataFactory()->getMetadataForClass(\get_class($object));

        if (null !== $classMeta) {
            /** @var PropertyMetadata $propertyMeta */
            foreach ($classMeta->propertyMetadata as $propertyMeta) {
                if (null === $propertyMeta->excludeIf) {
                    $propertyMeta->excludeIf = sprintf(
                        '!is_granted("perm_read", [object, "%s"])',
                        $propertyMeta->name
                    );
                }
            }
        }
    }
}
