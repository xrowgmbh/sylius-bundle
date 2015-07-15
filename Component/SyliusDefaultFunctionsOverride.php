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

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Sylius\Component\Addressing\Model\AddressInterface;

use Sylius\Component\Core\SyliusCheckoutEvents;
use Sylius\Component\Core\SyliusOrderEvents;
use Sylius\Component\Order\OrderTransitions;

class SyliusDefaultFunctionsOverride
{
    private $container;
    public $eZAPIRepository;
    private $entityManager;
    private $eventDispatcher;
    private $sylius = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->eZAPIRepository = $this->container->get('ezpublish.api.repository');
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->eventDispatcher = $this->container->get('event_dispatcher');
        $this->sylius['CartProvider'] = $this->container->get('sylius.cart_provider');
        $this->sylius['CartItemController'] = $this->container->get('sylius.controller.cart_item');
        $this->sylius['OrderRepository'] = $this->container->get('sylius.repository.order');
        $this->sylius['ProductRepository'] = $this->container->get('sylius.repository.product');
        $this->sylius['PriceCalculator'] = $this->container->get('sylius.price_calculator');
    }

    /**
     * Add eZ Object to Sylius cart
     * 
     * @param  integer $contentId The contentId/object id of an ez object
     * @throws \Sylius\Component\Cart\Resolver\ItemResolvingException
     * @return \Sylius\Component\Core\Model\Order
     */
    public function addProductToCart($contentId)
    {
        $cart = $this->sylius['CartProvider']->getCart();
        $cartItem = $this->sylius['CartItemController']->createNew(); // Sylius\Component\Core\Model\OrderItem
        try {
            if (!$syliusProduct = $this->sylius['ProductRepository']->findOneBy(array('content_id' => $contentId))) {
                // Create new sylius product
                $syliusProduct = $this->createNewProductAndVariant($contentId);
            }
            $syliusProductVariant = $syliusProduct->getMasterVariant();
            // Put product variant to the cart
            $cartItem->setVariant($syliusProductVariant);

            $quantity = $cartItem->getQuantity();
            $context = array('quantity' => $quantity);
            /*
              we don't have here a sylius user
            if (null !== $user = $cart->getUser()) {
                $context['groups'] = $user->getGroups()->toArray();
            }*/

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
            $this->eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_INITIALIZE, $event);
            $this->eventDispatcher->dispatch(SyliusCartEvents::CART_CHANGE, new GenericEvent($cart));
            $this->eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);

            return $cart;
        } catch (ItemResolvingException $exception) {
            throw new ItemResolvingException($exception->getMessage());
        }
        return null;
    }

    /**
     * Make a checkout
     * 
     * @param OrderInterface $order
     * @param array $userData
     * @return OrderInterface $order
     */
    public function checkoutOrder(OrderInterface $order, $userData)
    {
        // set temporary user
        if ($order->getUser() === null) {
            //$user = $this->createUser($userData['billing_first_name'], $userData['billing_last_name'], $userData['billing_email'], $billindAddress);
            $user = $this->createUser($userData['billing_first_name'], $userData['billing_last_name'], $userData['billing_email']);
            $order->setUser($user);
        }

        // Calculate amount of the order
        $order->calculateTotal();
        // Set current time
        $order->complete();
        $order->setState(OrderInterface::STATE_CONFIRMED);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        #$this->eventDispatcher->dispatch(SyliusCheckoutEvents::FINALIZE_COMPLETE, new GenericEvent($order));
        #$this->eventDispatcher->dispatch(SyliusOrderEvents::POST_CREATE, new GenericEvent($order));

        return $order;
    }

    public function removeOrder(OrderInterface $order)
    {
        if ($order !== null) {
            try {
                $user = $order->getUser();
                // Remove user and order
                $this->entityManager->remove($order);
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            } catch (Exception $e) {
                die(var_dump($e->getMessage()));
            }
        }
    }

    /**
     * Create new Sylius product to make possible to order via Sylius
     * 
     * @param unknown $contentId
     * @return ProductInterface $product
     */
    private function createNewProductAndVariant($contentId)
    {
        $product = $this->sylius['ProductRepository']->createNew();
        $product->setContentId($contentId);

        $taxCategoryRepository = $this->entityManager->getRepository('\Sylius\Component\Taxation\Model\TaxCategory');
        $taxCategory = $taxCategoryRepository->find(1);
        $product->setTaxCategory($taxCategory);

        // get eZ Object
        $eZObjectArray = $this->getEZObjectWithParent($contentId);

        $name = $eZObjectArray['contentObject']->getFieldValue('name')->__toString();
        $search_array = array('/û/', '/ù/', '/ú/', '/ø/', '/ô/', '/ò/', '/ó/', '/î/', '/ì/', '/í/', '/æ/', '/ê/', '/è/', '/é/', '/å/', '/â/', '/à/', '/á/', '/Û/', '/Ù/', '/Ú/', '/Ø/', '/Ô/', '/Ò/', '/Ó/', '/Î/', '/Ì/', '/Ì/', '/Í/', '/Æ/', '/Ê/', '/È/', '/É/', '/Å/', '/Â/', '/Â/', '/À/', '/Á/','/Ö/', '/Ä/', '/Ü/', "/'/", '/\&/', '/ö/', '/ä/', "/ /", '/ü/', '/ß/', '/\!/', '/\"/', '/\§/', '/\$/', '/\%/', '/\//', '/\(/', '/\)/', '/\=/', '/\?/', '/\@/', '/\#/', '/\*/', '/€/');
        $replace_array = array('u', 'u', 'u', 'o', 'o', 'o', 'o', 'i', 'i', 'i', 'ae', 'e', 'e', 'e', 'a', 'a', 'a', 'a', 'U', 'U', 'U', 'O', 'O', 'O', 'O', 'I', 'I', 'I', 'I', 'Ae', 'E', 'E', 'E', 'A', 'A', 'A', 'A', 'A', 'Oe', 'Ae', 'Ue', '', '+', 'oe', 'ae', "-", 'ue', 'ss', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
        $slug = preg_replace($search_array, $replace_array, strtolower($name));
        $description = $eZObjectArray['parentContentObject']->getFieldValue('description')->__toString();
        $locale = $this->container->getParameter('sylius.locale');

        $product->setContentId($contentId);
        $product->setCurrentLocale($locale);
        $product->setFallbackLocale($locale);
        $product->setSlug($slug);
        $product->setName($name);
        $product->setDescription($description);

        $product = $this->addMasterVariant($product, $eZObjectArray['contentObject']);

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

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        return $product;
    }

    /**
     * Get eZ object, in our case this is the original product
     * 
     * @param integer $contentId
     * @return array $eZObjectArray
     */
    public function getEZObjectWithParent($contentId)
    {
        $contentCervice = $this->eZAPIRepository->getContentService();
        $eZObjectArray = array('contentObject' => $contentCervice->loadContent($contentId));
        // Get the parent of eZ product
        $reverseRelations = $contentCervice->loadReverseRelations($eZObjectArray['contentObject']->versionInfo->contentInfo);
        $parentContentInfoObject = $reverseRelations[0]->sourceContentInfo;
        $eZObjectArray['parentContentObject'] = $contentCervice->loadContent($parentContentInfoObject->id);
        return $eZObjectArray;
    }

    /**
     * Get eZ object, in our case this is the original product
     *
     * @param integer $contentId
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getEZObject($contentId)
    {
        $contentCervice = $this->eZAPIRepository->getContentService();
        return $contentCervice->loadContent($contentId);
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

        $price = (int)$contentObject->getFieldValue('price')->__toString() * 100;
        // Sylius Produkt Variant
        $variant->setSku($product->getContentId());
        $variant->setAvailableOn($contentObject->versionInfo->creationDate);
        $variant->setOnHand(100);
        $variant->setPrice($price);

        $product->setMasterVariant($variant);

        return $product;
    }

    /**
     * Create Sylius user
     * 
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param AddressInterface $billindAddress
     * @return UserInterface $user
     */
    protected function createUser($firstName, $lastName, $email)
    {
        // check if user exists
        $userRepository = $this->container->get('sylius.repository.user');
        $user = $userRepository->findOneBy(array('email' => $email, 'lastName' => $lastName, 'firstName' => $firstName));
        if ($user === null) {
            $user = $userRepository->findOneBy(array('email' => $email, 'lastName' => $lastName));
            if ($user === null) {
                $user = $userRepository->findOneBy(array('email' => $email));
            }
        }
        if ($user === null) {
            $user = $this->container->get('sylius.repository.user')->createNew();
            $user->setFirstname($firstName);
            $user->setLastname($lastName);
            $user->setUsername($email);
            $user->setEmail($email);
            $user->setPlainPassword('%6Gfr420?');
            $user->setRoles(array('ROLE_USER'));
            $user->setCurrency('EUR');
            $user->setEnabled(true);

            $this->entityManager->persist($user);
        }
        return $user;
    }
}