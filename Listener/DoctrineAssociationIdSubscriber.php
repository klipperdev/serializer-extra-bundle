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
use JMS\Serializer\Accessor\AccessorStrategyInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use Klipper\Bundle\SerializerExtraBundle\Type\AssociationId;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DoctrineAssociationIdSubscriber implements EventSubscriberInterface
{
    protected ManagerRegistry $doctrine;

    protected AccessorStrategyInterface $accessorStrategy;

    /**
     * @param ManagerRegistry $doctrine The doctrine registry
     */
    public function __construct(ManagerRegistry $doctrine, AccessorStrategyInterface $accessorStrategy)
    {
        $this->doctrine = $doctrine;
        $this->accessorStrategy = $accessorStrategy;
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
        $context = $event->getContext();

        if (!\is_object($object) || !$context instanceof SerializationContext) {
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
                        $this->getValue($object, $propertyMeta, $context)
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

    /**
     * @param mixed $object
     *
     * @throws
     *
     * @return null|int|string
     */
    private function getValue($object, PropertyMetadata $propertyMeta, SerializationContext $context)
    {
        if (\is_object($object)) {
            $data = $this->accessorStrategy->getValue($object, $propertyMeta, $context);

            if (\is_object($data)) {
                $class = \get_class($data);
                $om = $this->doctrine->getManagerForClass($class);

                if (null !== $om) {
                    $meta = $om->getClassMetadata($class);
                    $identifier = $meta->getIdentifierValues($data);

                    return \count($identifier) > 0 ? current($identifier) : null;
                }
            }
        }

        return null;
    }
}
