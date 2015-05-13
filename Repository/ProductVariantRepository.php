<?php

namespace xrow\syliusBundle\Repository;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository as SyliusProductVariantRepository;

use xrow\syliusBundle\Entity\ProductVariant as SyliusProductVariant;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class ProductVariantRepository extends SyliusProductVariantRepository
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

    public function find($contentobject_id)
    {
        $syliusProductVariant = $this->getQueryBuilder()
                                        ->andWhere($this->getAlias().'.product_id = '.intval($contentobject_id))
                                        ->getQuery()
                                        ->getOneOrNullResult();
        return $syliusProductVariant;
    }

    protected function getAlias()
    {
        return 'variant';
    }
}
