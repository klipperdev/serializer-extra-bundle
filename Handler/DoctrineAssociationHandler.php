<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SerializerExtraBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DoctrineAssociationHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $doctrine The doctrine registry
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param SerializationVisitorInterface $visitor The serializer visitor
     * @param mixed                         $data    The data
     *
     * @return array|int|mixed|string
     */
    public function serializeAssociation(SerializationVisitorInterface $visitor, $data)
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
