<?php

namespace xrow\syliusBundle\Entity;

use Sylius\Component\Core\Model\ProductVariant as SyliusProductVariant;
use Sylius\Component\Core\Model\ProductVariantImageInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sylius_product_variant")
 * @ORM\Entity(repositoryClass="xrow\syliusBundle\Repository\ProductVariantRepository",readOnly=true)
 */
class ProductVariant extends SyliusProductVariant
{ 
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getProduct()->getEZObject()->getFieldValue('contentobject_id')->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $string = $this->getProduct()->getEZObject()->getFieldValue('name')->__toString(); // eZ\Publish\Core\FieldType\TextLine\Value

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return (int)$this->getProduct()->getEZObject()->getFieldValue('price_de')->__toString() * 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getPricingCalculator()
    {
        return 'standard';
    }

    /**
     * {@inheritdoc}
     */
    public function getPricingConfiguration()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function isInStock()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getOnHold()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getSold()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeleted()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isMaster()
    {
        return true;
    }
}
