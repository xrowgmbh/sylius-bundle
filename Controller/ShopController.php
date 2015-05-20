<?php

namespace xrow\syliusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ShopController extends Controller
{
    /**
     * @Route("/shop/order/{contentId}")
     */
    public function makeAnOrderAction($contentId)
    {
        die('bin drin: '.$contentId);
    }
}
