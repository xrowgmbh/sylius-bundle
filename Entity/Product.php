<?php

namespace xrow\syliusBundle\Entity;

use Sylius\Component\Product\Model\ProductInterface;

class Product implements ProductInterface
{
    // Your code...

    public function getName()
    {
        // Here you just have to return the nice display name of your merchandise.
        return $this->name;
    }
}