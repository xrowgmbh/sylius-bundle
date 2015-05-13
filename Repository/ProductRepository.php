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

    public function find($contentobject_id)
    {
        if($this->container) {
            $contentObject = $this->eZAPIRepository->getContentService()->loadContent($contentobject_id); // eZ\Publish\Core\Repository\Values\Content\Content
            $product = $this->createNew();
            $product->setEZObject($contentObject);
            return $product;
        }
        else {
            throw new InvalidArgumentException('ContainerInterface container not set.');
        }
    }

    /**
     * @return array
     */
    public function findAll()
    {
        // erweitern um die KlassenID, damit nur Produke ausgegeben werden
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
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
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
    }

    public function save(Customer $customer)
    {
        $this->_em->persist($customer);
        $this->_em->flush();
    }
}