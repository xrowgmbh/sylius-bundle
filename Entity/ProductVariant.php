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
    public function setProductId($id)
    {
        return $this->product_id = $id;
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
