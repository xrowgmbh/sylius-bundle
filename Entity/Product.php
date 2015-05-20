<?php

namespace xrow\syliusBundle\Entity;

use Sylius\Component\Core\Model\Product as SyliusProduct;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="sylius_product")
 * @ORM\Entity(repositoryClass="xrow\syliusBundle\Repository\ProductRepository")
 */
class Product extends SyliusProduct
{
    /**
     * @ORM\Column(type="integer", unique=true)
     */
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
}