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

use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Klipper\Bundle\SerializerExtraBundle\Type\AssociationId;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DoctrineAssociationIdSubscriber implements EventSubscriberInterface
{
    protected ManagerRegistry $doctrine;

    /**
     * @param ManagerRegistry $doctrine The doctrine registry
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

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

    public function onPreSerialize(ObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!\is_object($object)) {
            return;
        }

        $classMeta = $event->getContext()->getMetadataFactory()->getMetadataForClass(\get_class($object));

        if (null !== $classMeta) {
            /** @var PropertyMetadata $propertyMeta */
            foreach ($classMeta->propertyMetadata as $i => $propertyMeta) {
                if (null === $propertyMeta->type) {
                    continue;
                }

                if ('AssociationId' === $propertyMeta->type['name']) {
                    $propertyMeta->type['name'] = AssociationId::class;
                }

                if (is_a($propertyMeta->type['name'], AssociationId::class, true)) {
                    $classMeta->propertyMetadata[$i] = $staticPropMeta = new StaticPropertyMetadata(
                        $propertyMeta->class,
                        $propertyMeta->serializedName,
                        $this->getValue($object)
                    );
                    $staticPropMeta->sinceVersion = $propertyMeta->sinceVersion;
                    $staticPropMeta->untilVersion = $propertyMeta->untilVersion;
                    $staticPropMeta->groups = $propertyMeta->groups;
                    $staticPropMeta->inline = $propertyMeta->inline;
                    $staticPropMeta->skipWhenEmpty = $propertyMeta->skipWhenEmpty;
                    $staticPropMeta->excludeIf = $propertyMeta->excludeIf;

                    if (0 !== substr_compare($staticPropMeta->serializedName, '_id', -\strlen('_id'))) {
                        $staticPropMeta->serializedName .= '_id';
                    }
                }
            }
        }
    }

    private function getValue($data)
    {
        if (\is_object($data)) {
            $class = \get_class($data);
            $om = $this->doctrine->getManagerForClass($class);

            if (null !== $om) {
                $meta = $om->getClassMetadata($class);
                $identifier = $meta->getIdentifierValues($data);
                $data = 1 === \count($identifier) ? current($identifier) : $identifier;
            }
        }

        return $data;
    }
}
