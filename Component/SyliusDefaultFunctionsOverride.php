<?php

/**
 * Copy required/overwrite functions from Sylius bundle to make it possible to order an eZ object
 * Some helpfull functions you can find in the demo data bundle Sylius\Bundle\FixturesBundle\DataFixtures\ORM\Load[SOMEOBJECTS]Data
 * 
 * @author kristina
 *
 */

namespace xrow\syliusBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

use Sylius\Component\Cart\Event\CartItemEvent;
use Sylius\Component\Cart\Resolver\ItemResolvingException;
use Sylius\Component\Cart\SyliusCartEvents;
use Sylius\Component\Core\Model\ShipmentInterface;

#use xrow\syliusBundle\Entity\User as User;

#use Faker\Provider\DateTime as FakeDateTime;

class SyliusDefaultFunctionsOverride
{
    private $container;
    private $sylius = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->sylius['CartProvider'] = $this->container->get('sylius.cart_provider');
        $this->sylius['CartItemController'] = $this->container->get('sylius.controller.cart_item');
        $this->sylius['OrderRepository'] = $this->container->get('sylius.repository.order');
        $this->sylius['ProductRepository'] = $this->container->get('sylius.repository.product');
        $this->sylius['ShipmentRepository'] = $this->container->get('sylius.repository.shipment');
        $this->sylius['PriceCalculator'] = $this->container->get('sylius.price_calculator');
        //$this->sylius['ProcessController'] = $this->container->get('sylius.controller.process');
    }

    /**
     * @param  integer $contentId The contentId/object id of an ez object
     * @throws \Sylius\Component\Cart\Resolver\ItemResolvingException
     * @return \Sylius\Component\Core\Model\Order
     */
    public function addProductToCart($contentId)
    {
        // get order
        if($tmpOder = $this->sylius['OrderRepository']->find(1))
        {
            return $tmpOder;
        }

        $eventDispatcher = $this->container->get('event_dispatcher');
        $cart = $this->sylius['CartProvider']->getCart();
        $cartItem = $this->sylius['CartItemController']->createNew(); // Sylius\Component\Core\Model\OrderItem
        try {
            if (!$syliusProduct = $this->sylius['ProductRepository']->findOneBy(array('content_id' => $contentId))) {
                // create new sylius product
                $syliusProduct = $this->createNewProductAndVariant($contentId);
            }
            $syliusProductVariant = $syliusProduct->getMasterVariant();
            // put product variant to the cart
            $cartItem->setVariant($syliusProductVariant);

            $quantity = $cartItem->getQuantity();
            $context = array('quantity' => $quantity);
            if (null !== $user = $cart->getUser()) {
                $context['groups'] = $user->getGroups()->toArray();
            }

            $cartItem->setUnitPrice($this->sylius['PriceCalculator']->calculate($syliusProductVariant, $context));
            foreach ($cart->getItems() as $cartItemTmp) {
                if ($cartItemTmp->equals($cartItem)) {
                    $quantity += $cartItemTmp->getQuantity();
                    break;
                }
            }

            $event = new CartItemEvent($cart, $cartItem);
            $event->isFresh(true);
            // Update models
            $eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_INITIALIZE, $event);
            $eventDispatcher->dispatch(SyliusCartEvents::CART_CHANGE, new GenericEvent($cart));
            $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);

            return $cart;
        } catch (ItemResolvingException $exception) {
            throw new ItemResolvingException($exception->getMessage());
        }
        return null;
    }

    /**
     * get current logged in user and add his data to order
     * 
     * @param \Sylius\Component\Core\Model\Order $order
     * @return \Sylius\Component\Core\Model\Order
     */
    public function setUserToOrder(\Sylius\Component\Core\Model\Order $order)
    {
        $oauthToken = $this->container->get('security.context')->getToken();
        if ($oauthToken === NULL) {
            // show login page or do this here
            $userRepository = $this->container->get('xrow.sylius.repository.user');
            $username = 'schaller';
            $password = 'cschall';
            $user = $userRepository->loginUser($username, $password);
            if (!$user) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('User with username %s does not exist.', $username));
            }
        }
        $order->setShippingAddress($this->createAddress('shipping'));
        $order->setBillingAddress($this->createAddress('billing'));
        $this->dispatchEvents($order);

        $order->calculateTotal();
        $order->complete();
    
        if ($i < 4) {
            $order->setUser($this->getReference('Sylius.User-Administrator'));
    
            $this->createPayment($order, PaymentInterface::STATE_COMPLETED);
        } else {
            $order->setUser($this->getReference('Sylius.User-'.rand(1, 15)));
    
            $this->createPayment($order);
        }
    
        $order->setCompletedAt($this->faker->dateTimeThisDecade);
        $this->setReference('Sylius.Order-'.$i, $order);
    
        $manager->persist($order);
    }

    public function setShipmentToOrder(\Sylius\Component\Core\Model\Order $cart)
    {
        $shipment = $this->sylius['ShipmentRepository']->createNew();
        $shipmentMethode = $this->sylius['ShipmentRepository']->find(1);
        die(var_dump($shipmentMethode));
        $shipment->setMethod($shipmentMethode);
        $shipmentState = array_rand(array_flip(array(
            ShipmentInterface::STATE_PENDING,
            ShipmentInterface::STATE_ONHOLD,
            ShipmentInterface::STATE_CHECKOUT,
            ShipmentInterface::STATE_SHIPPED,
            ShipmentInterface::STATE_READY,
            ShipmentInterface::STATE_BACKORDERED,
            ShipmentInterface::STATE_RETURNED,
            ShipmentInterface::STATE_CANCELLED,
        )));
        $shipment->setState($shipmentState);

        foreach ($cart->getInventoryUnits() as $item) {
            $shipment->addItem($item);
        }

        $cart->addShipment($shipment);
    }

    public function checkoutTheOrder()
    {
        
    }

    private function createNewProductAndVariant($contentId)
    {
        // sylius.manager.tax_category
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $product = $this->sylius['ProductRepository']->createNew();
        $product->setContentId($contentId);

        $taxCategoryRepository = $entityManager->getRepository('\Sylius\Component\Taxation\Model\TaxCategory');
        $taxCategory = $taxCategoryRepository->find(1);
        $product->setTaxCategory($taxCategory);

        // get eZ Object
        $eZObject = $this->getEZObject($contentId, true);

        $name = $eZObject['contentObject']->getFieldValue('name')->__toString();
        $search_array = array('/û/', '/ù/', '/ú/', '/ø/', '/ô/', '/ò/', '/ó/', '/î/', '/ì/', '/í/', '/æ/', '/ê/', '/è/', '/é/', '/å/', '/â/', '/à/', '/á/', '/Û/', '/Ù/', '/Ú/', '/Ø/', '/Ô/', '/Ò/', '/Ó/', '/Î/', '/Ì/', '/Ì/', '/Í/', '/Æ/', '/Ê/', '/È/', '/É/', '/Å/', '/Â/', '/Â/', '/À/', '/Á/','/Ö/', '/Ä/', '/Ü/', "/'/", '/\&/', '/ö/', '/ä/', "/ /", '/ü/', '/ß/', '/\!/', '/\"/', '/\§/', '/\$/', '/\%/', '/\//', '/\(/', '/\)/', '/\=/', '/\?/', '/\@/', '/\#/', '/\*/', '/€/');
        $replace_array = array('u', 'u', 'u', 'o', 'o', 'o', 'o', 'i', 'i', 'i', 'ae', 'e', 'e', 'e', 'a', 'a', 'a', 'a', 'U', 'U', 'U', 'O', 'O', 'O', 'O', 'I', 'I', 'I', 'I', 'Ae', 'E', 'E', 'E', 'A', 'A', 'A', 'A', 'A', 'Oe', 'Ae', 'Ue', '', '+', 'oe', 'ae', "-", 'ue', 'ss', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
        $slug = preg_replace($search_array, $replace_array, strtolower($name));
        $description = $eZObject['parentContentObject']->getFieldValue('description')->__toString();
        $locale = $this->container->getParameter('sylius.locale');

        $product->setContentId($contentId);
        $product->setCurrentLocale($locale);
        $product->setFallbackLocale($locale);
        $product->setSlug($slug);
        $product->setName($name);
        $product->setDescription($description);

        $product = $this->addMasterVariant($product, $eZObject['contentObject'], $entityManager);

        // get ArchType 
        switch($name) {
            case strpos($name, 'Kontakter') !== false:
                $archcode = 'kontakterepaper';
                break;
            case strpos($name, 'LEAD digital') !== false:
                $archcode = 'leaddigitalepaper';
                break;
            default:
                $archcode = 'wuvepaper';
                break;
        }
        $archetypeRepository = $this->container->get('sylius.repository.product_archetype');
        $archetype = $archetypeRepository->findOneBy(array('code' => $archcode));
        $product->setArchetype($archetype);

        $entityManager->persist($product);
        $entityManager->flush();
        return $product;
    }

    /**
     * 
     * @param unknown $contentId
     * @param string $getParent
     * @return multitype:NULL
     */
    private function getEZObject($contentId, $getParent = false)
    {
        $eZAPIRepository = $this->container->get('ezpublish.api.repository');
        $contentCervice = $eZAPIRepository->getContentService();
        $eZObject = array('contentObject' => $contentCervice->loadContent($contentId));
        if($getParent) {
            $reverseRelations = $contentCervice->loadReverseRelations($eZObject['contentObject']->versionInfo->contentInfo);
            $parentContentInfoObject = $reverseRelations[0]->sourceContentInfo;
            $eZObject['parentContentObject'] = $contentCervice->loadContent($parentContentInfoObject->id);
        }
        return $eZObject;
    }

    /**
     * Adds master variant to product.
     * 
     * @param ProductInterface $product
     * @param unknown $contentObject
     * @return ProductInterface
     */
    protected function addMasterVariant(ProductInterface $product, $contentObject)
    {
        $variant = $product->getMasterVariant();
        $variant->setProduct($product);

        $price = (int)$contentObject->getFieldValue('price_de')->__toString() * 100;
        // Sylius Produkt Variant
        $variant->setSku(intval(rand(1,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9)));
        $variant->setAvailableOn($contentObject->versionInfo->creationDate);
        $variant->setOnHand(100);
        $variant->setPrice($price);

        $product->setMasterVariant($variant);

        return $product;
    }
    
    protected function createAddress($user)
    {
        /* @var $address AddressInterface */
        $address = $this->getAddressRepository()->createNew();
        $address->setFirstname($this->faker->firstName);
        $address->setLastname($this->faker->lastName);
        $address->setCity($this->faker->city);
        $address->setStreet($this->faker->streetAddress);
        $address->setPostcode($this->faker->postcode);
    
        do {
            $isoName = $this->faker->countryCode;
        } while ('UK' === $isoName);
    
        $country  = $this->getReference('Sylius.Country.'.$isoName);
        $province = $country->hasProvinces() ? $this->faker->randomElement($country->getProvinces()->toArray()) : null;
    
        $address->setCountry($country);
        $address->setProvince($province);
    
        return $address;
    }
}