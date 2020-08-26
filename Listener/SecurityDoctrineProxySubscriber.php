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

use Doctrine\ORM\EntityNotFoundException;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class SecurityDoctrineProxySubscriber implements EventSubscriberInterface
{
    private DoctrineProxySubscriber $subscriber;

    public function __construct(DoctrineProxySubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public static function getSubscribedEvents(): array
    {
        return DoctrineProxySubscriber::getSubscribedEvents();
    }

    public function onPreSerialize(PreSerializeEvent $event): void
    {
        try {
            $this->loadObject($event);
            $this->subscriber->onPreSerialize($event);
        } catch (EntityNotFoundException $e) {
            // Skip entity not found filtered by the organizational filter
            $event->setType(\stdClass::class);
        }
    }

    public function onPreSerializeTypedProxy(PreSerializeEvent $event, string $eventName, string $class, string $format, EventDispatcherInterface $dispatcher): void
    {
        try {
            $this->loadObject($event);
            $this->subscriber->onPreSerializeTypedProxy($event, $eventName, $class, $format, $dispatcher);
        } catch (EntityNotFoundException $e) {
            // Skip entity not found filtered by the organizational filter
            $event->setType(\stdClass::class);
        }
    }

    private function loadObject(PreSerializeEvent $event): void
    {
        $object = $event->getObject();

        if (\is_object($object)) {
            if (interface_exists(LazyLoadingInterface::class) && $object instanceof LazyLoadingInterface) {
                $object->initializeProxy();
            } elseif (method_exists($object, '__load')) {
                $object->__load();
            }
        }
    }
}
