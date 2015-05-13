<?php

/**
 * Zur Hilfe kann man sich die Sylius\Bundle\FixturesBundle\DataFixtures\ORM\LoadProductsData nehmen.
 * Da werden alle Demodaten in Sylius angelegt.
 * @author kristina
 *
 */

namespace xrow\syliusBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

use Sylius\Component\Cart\Event\CartItemEvent;
use Sylius\Component\Cart\Resolver\ItemResolvingException;
use Sylius\Component\Cart\SyliusCartEvents;
use Sylius\Component\Resource\Event\FlashEvent;

use xrow\syliusBundle\Entity\ProductVariant as SyliusProductVariant;

use Faker\Provider\DateTime as FakeDateTime;

class SyliusDefaultFunctionsOverride
{
    private $container;
    public $sylius = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->sylius['CartProvider'] = $this->container->get('sylius.cart_provider');
        $this->sylius['CartItemController'] = $this->container->get('sylius.controller.cart_item');
        $this->sylius['ProductRepository'] = $this->container->get('sylius.repository.product');
        $this->sylius['ProductVariantRepository'] = $this->container->get('sylius.repository.product_variant');
        $this->sylius['PriceCalculator'] = $this->container->get('sylius.price_calculator');
    }

    public function addProductToCartAction($contentobject_id)
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $cart = $this->sylius['CartProvider']->getCart();
        $cartItem = $this->sylius['CartItemController']->createNew(); // Sylius\Component\Core\Model\OrderItem
        $this->sylius['ProductRepository']->setContainer($this->container);
        $this->sylius['ProductVariantRepository']->setContainer($this->container);
        try {
            if (!$syliusProduct = $this->sylius['ProductRepository']->find($contentobject_id)) {
                throw new ItemResolvingException('Requested product was not found.');
            }
            #$syliusProduct->setId($contentobject_id);
            // sylius needs a product variant to create a cart
            $variant = $this->sylius['ProductVariantRepository']->createNew();
            $variant->setSku(intval(rand(1,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9)));
            $variant->setAvailableOn(FakeDateTime::dateTimeBetween('-1 year'));
            $variant->setOnHand(100);
            $variant->setPrice($syliusProduct->getPrice());
            // set new master variant xrow\syliusBundle\Entity\ProductVariant
            $syliusProduct->setMasterVariant($variant);
            // all entities have to be persisted
            $entityManager->persist($variant);
            $entityManager->persist($syliusProduct);
            $entityManager->flush();
            #$variant->setPrice($variant->getPrice());
            // put product variant to the cart
            $cartItem->setVariant($variant);
            $quantity = $cartItem->getQuantity();
            $context = array('quantity' => $quantity);
            if (null !== $user = $cart->getUser()) {
                $context['groups'] = $user->getGroups()->toArray();
            }
            //Sylius\Bundle\CartBundle\EventListener\CartListener->saveCart
            $cartItem->setUnitPrice($this->sylius['PriceCalculator']->calculate($variant, $context));
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
            // Write flash message
            #$eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_ERROR, new FlashEvent($exception->getMessage()));
            throw new ItemResolvingException($exception->getMessage());
        }
        return null;
    }
}