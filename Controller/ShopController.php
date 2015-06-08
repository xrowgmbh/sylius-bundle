<?php

namespace xrow\syliusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use Sylius\Component\Core\Model\OrderInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ShopController extends Controller
{
    public $userData = array(
            'first_name' =>             'Björn',
            'last_name' =>              'Dieding',
            'salutation' =>             'Herr',
            'phone' =>                  '0151 154221',
            'email' =>                  'kristina@xrow.de',
            'fax' =>                    '0049 511 4512134',
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
            'mailing_phone' =>          '0151 154221',
            'mailing_email' =>          'kristina@xrow.de',
            'mailing_fax' =>            '0049 511 4512134',
            'mailing_company' =>        'xrow GmbH',
            'mailing_city' =>           'Hannover',
            'mailing_country' =>        'Deutschland',
            'mailing_postal_code' =>    '30159',
            'mailing_street' =>         'Goseriede',
            'mailing_street_number' =>  '4',
            'promotion' =>              true,
            'vatin' =>                  'USt-IdNr' );

    /**
     * @Route("/shop/order/{contentId}")
     */
    public function makeAnOrderAction($contentId)
    {
        // Get sylius overwrite service
        $syliusOFRef = $this->container->get('xrow.sylius.override.functions');
        // We need the data as array
        $userData = (array)$this->userData;
        // Validate user data
        // HERE

        if(strpos($contentId, '|') !== false) {
            $contentIds = explode('|', $contentId);
        }
        else {
            $contentIds = array($contentId);
        }
        // Add eZ object(s) to cart
        foreach ($contentIds as $contentId) {
            $order = $syliusOFRef->addProductToCart($contentId);
        }

        $order = $syliusOFRef->checkoutOrder($order, $userData);
        $data = $this->getRequiredData($order, $userData);

        //Get jBPM Client
        $jbpmClient = $this->container->get('jbpm.client');
        $processDefinition = $jbpmClient->getProcess('cms.order');

        $processInstance = $processDefinition->start($data);
        if($processInstance !== null) {
            $task = $processInstance->currentTask();
            if($task !== null) {
                $syliusOFRef->removeOrder($order);
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

    private function getRequiredData(OrderInterface $order, $userData)
    {
        $data = $userData;
        // Get order
        $orderItems = $order->getItems();
        $data['order'] = array('id' => $order->getId(), 'items' => array());
        foreach ($orderItems as $orderItem) {
            $variant = $orderItem->getVariant();
            $data['order']['items'][$orderItem->getId()] = array('sku' => $variant->getSku(), 'amount' => ($variant->getPrice()/100));
        }

        return $data;
    }

    private function validateAndGetDataViaPlugin($data)
    {
        if ($this->container->hasParameter('xrow.sylius.data.validator')) {
            $validatorServiceName = $this->container->getParameter('xrow.sylius.data.validator');
            if ($this->container->has($validatorServiceName)) {
                $validator = $this->container->get($validatorServiceName);
                $result = $validator->validate($data);
                return $result;
            }
        }
        return true;
    }
}