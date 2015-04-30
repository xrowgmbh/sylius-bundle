<?php

namespace xrow\syliusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('xrowsyliusBundle:Default:index.html.twig', array('name' => $name));
    }
}
