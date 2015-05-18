<?php

namespace xrow\syliusBundle\Repository;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as SyliusProductRepository;

use xrow\syliusBundle\Entity\Product as SyliusProduct;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class ProductRepository extends SyliusProductRepository
{
    private $container;
    private $eZAPIRepository;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->eZAPIRepository = $this->container->get('ezpublish.api.repository'); // eZ\Publish\Core\SignalSlot\ContentService
    }

    public function getContainer()
    {
        $this->container = $container;
    }

    public function find($id)
    {
        $syliusProduct = $this->getQueryBuilder()
                                    ->andWhere($this->getAlias().'.id = '.intval($id))
                                    ->getQuery()
                                    ->getOneOrNullResult();
        return $syliusProduct;
    }

    protected function getAlias()
    {
        return 'sylius_product';
    }
}