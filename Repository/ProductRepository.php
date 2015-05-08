<?php

namespace xrow\syliusBundle\Repository;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class ProductRepository
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param mixed $id
     *
     * @return null|object
     */
    public function find($node_id)
    {
        $repository = $this->container->get('ezpublish.api.repository');
        $source_node = $repository->getLocationService()->loadLocation($node_id);
    }

    /**
     * @return array
     */
    public function findAll()
    {
        // erweitern um die KlassenID, damit nur Produke ausgegeben werden
        new Criterion\ContentTypeIdentifier(array('product'));
        return $this
            ->getCollectionQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array $criteria
     *
     * @return null|object
     */
    public function findOneBy(array $criteria)
    {
        $queryBuilder = $this->getQueryBuilder();

        $this->applyCriteria($queryBuilder, $criteria);

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}