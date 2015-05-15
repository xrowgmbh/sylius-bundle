<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xrow\syliusBundle\Entity;

use Sylius\Component\Core\Model\Product as SyliusProduct;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Component\Taxation\Model\TaxCategoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface as BaseTaxonInterface;
use Sylius\Component\Variation\Model\VariantInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sylius_product")
 * @ORM\Entity(repositoryClass="xrow\syliusBundle\Repository\ProductRepository",readOnly=true)
 */
class Product extends SyliusProduct
{
    protected $content_id;

    public function getContentId()
    {
        return $this->content_id;
    }

    public function setContentId($contentId)
    {
        $this->content_id = $contentId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMasterVariant(VariantInterface $masterVariant)
    {
        if($masterVariant instanceof ProductVariant) {
            $masterVariant->setMaster(true);
            if (!$this->variants->contains($masterVariant)) {
                $masterVariant->setProduct($this);
                $this->variants->add($masterVariant);
            }
        }

        return $this;
    }
}