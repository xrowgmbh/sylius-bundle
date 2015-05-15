<?php

namespace xrow\syliusBundle\Model;

use Sylius\Component\Core\Model\ProductVariant as BaseVariant;

class ProductVariant extends BaseVariant
{
    protected $id;
    protected $product_id;

    /**
     * Override constructor to set on hand stock.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($contentobject_id)
    {
        $this->id = $contentobject_id;

        return $this;
    }

    public function getProductId()
    {
        return $this->product_id;
    }

    public function setProductId($contentobject_id)
    {
        $this->product_id = $contentobject_id;
    
        return $this;
    }
}
