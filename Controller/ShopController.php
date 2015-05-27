<?php

namespace xrow\syliusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ShopController extends Controller
{
    /**
     * @Route("/shop/order/{contentId}")
     */
    public function makeAnOrderAction($contentId)
    {
        // Get sylius overwrite service
        $syliusOFRef = $this->container->get('xrow.sylius.override.functions');

        if(strpos($contentId, '|') !== false) {
            $contentIds = explode('|', $contentId);
        }
        else {
            $contentIds = array($contentId);
        }
        // Add eZ object(s) zu cart
        foreach ($contentIds as $contentId) {
            $orderID = $this->addProductToCart($contentId);
        }

        $data = $this->getRequiredData($syliusOFRef);

        //Get jBPM Client
        $jbpmClient = $this->container->get('jbpm.client');
        $processDefinition = $jbpmClient->getProcess('cms.order');

        $processInstance = $processDefinition->start($data);
        if(!is_null($processInstance)) {
            $task = $processInstance->currentTask();
            if(!is_null($task)) {
                return $this->render('xrowjBPMBundle:Default:index.html.twig', array('processid' => $processInstance->getProcessInstanceId(),
                                                                                     'processName' => $processDefinition->getProcessDefinitionID()));
            }
            else {
                return NULL;
            }
        }
        else {
            return NULL;
        }
    }

    private function getRequiredData($syliusOFRef)
    {
        // Get order
        $order = $syliusOFRef->getOrder();
        die(var_dump($order));
        /*'order' => array(
                array( 'sku' => "123123", "amount" => "",
                )*/
        $data = array(
                'first_name' =>             'Björn',
                'last_name' =>              'Dieding',
                'salutation' =>             'Herr',
                'phone' =>                  '0151 154221',
                'email' =>                  'bjoern@xrow.de',
                'company' =>                'xrow GmbH',
                'position' =>               'Geschäftsleitung',
                'vertical' =>               'IT',
                'company_size' =>           '5-10',
                'billing_city' =>           'Hannover',
                'billing_country' =>        'Deutschland',
                'billing_postal_code' =>    '30159',
                'billing_street' =>         'Goseriede',
                'billing_street_number' =>  '4',
                'mailing_first_name' =>     'Kristina',
                'mailing_last_name' =>      'Ebel',
                'mailing_salutation' =>     'Frau',
                'mailing_company' =>        'xrow GmbH',
                'mailing_city' =>           'Hannover',
                'mailing_country' =>        'Deutschland',
                'mailing_postal_code' =>    '30159',
                'mailing_street' =>         'Goseriede',
                'mailing_street_number' =>  '4',
                'promotion' =>              true,
                'vatin' =>                  'USt-IdNr' );

        return $data;
    }
}