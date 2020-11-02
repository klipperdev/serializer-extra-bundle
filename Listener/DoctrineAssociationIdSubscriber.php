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
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Metadata\PropertyMetadata;
use Klipper\Bundle\SerializerExtraBundle\Type\AssociationId;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DoctrineAssociationIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
                'priority' => 1024,
            ],
        ];
    }

    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $object = $event->getObject();

        if (!\is_object($object)) {
            return;
        }

        $classMeta = $event->getContext()->getMetadataFactory()->getMetadataForClass(\get_class($object));

        if (null !== $classMeta) {
            /** @var PropertyMetadata $propertyMeta */
            foreach ($classMeta->propertyMetadata as $propertyMeta) {
                if (null === $propertyMeta->type) {
                    continue;
                }

                if ('AssociationId' === $propertyMeta->type['name']) {
                    $propertyMeta->type['name'] = AssociationId::class;
                }

                if (is_a($propertyMeta->type['name'], AssociationId::class, true)
                        && 0 !== substr_compare($propertyMeta->serializedName, '_id', -\strlen('_id'))) {
                    $propertyMeta->serializedName .= '_id';
                }
            }
        }
    }
}
