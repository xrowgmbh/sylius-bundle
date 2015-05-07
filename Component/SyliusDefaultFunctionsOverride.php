<?php

namespace xrow\syliusBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sylius\Component\Cart\Event\CartItemEvent;
use Sylius\Component\Cart\Resolver\ItemResolvingException;
use Sylius\Component\Cart\SyliusCartEvents;
use Sylius\Component\Resource\Event\FlashEvent;
use Symfony\Component\EventDispatcher\GenericEvent;
use Sylius\Bundle\CartBundle\Controller\CartItemController;

class SyliusDefaultFunctionsOverride
{
    private $container;
    private $cartProvider;
    private $productRepository;
    private $cartItemController;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cartProvider = $this->container->get('sylius.cart_provider');
        $this->productRepository = $this->container->get('xrow.sylius.repository.product');
        $this->cartItemController = $this->container->get('sylius.controller.cart_item');
    }

    public function addProductToCartAction($id)
    {
        // get sylius services
        $eventDispatcher = $this->container->get('event_dispatcher');
        $cart = $this->cartProvider->getCart();
        // $emptyItem => Sylius\Component\Core\Model\OrderItem
        $emptyItem = $this->cartItemController->createNew();
        try {
            if (!$product = $this->productRepository->find($id)) {
                throw new ItemResolvingException('Requested product was not found.');
            }
            die(var_dump($product));
            /* das könnte/sollte man überschreiben für unsere Zwecke
            // We use forms to easily set the quantity and pick variant but you can do here whatever is required to create the item.
            $form = $this->formFactory->create('sylius_cart_item', $item, array('product' => $product));
            $form->submit($data);

            // If our product has no variants, we simply set the master variant of it.
            if (null === $item->getVariant()) {
                $item->setVariant($product->getMasterVariant());
            }
            $variant = $item->getVariant();

            // If all is ok with form, quantity and other stuff, simply return the item.
            if (!$form->isValid() || null === $variant) {
                throw new ItemResolvingException('Submitted form is invalid.');
            }

            $quantity = $item->getQuantity();
        
            $context = array('quantity' => $quantity);
        
            if (null !== $user = $cart->getUser()) {
                $context['groups'] = $user->getGroups()->toArray();
            }
        
            $item->setUnitPrice($this->priceCalculator->calculate($variant, $context));
        
            foreach ($cart->getItems() as $cartItem) {
                if ($cartItem->equals($item)) {
                    $quantity += $cartItem->getQuantity();
                    break;
                }
            }
        
            if (!$this->availabilityChecker->isStockSufficient($variant, $quantity)) {
                throw new ItemResolvingException('Selected item is out of stock.');
            }*/
            #$item = $resolver->resolve($emptyItem, $request);
        } catch (ItemResolvingException $exception) {
            // Write flash message
            $eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_ERROR, new FlashEvent($exception->getMessage()));
            throw new ItemResolvingException($exception->getMessage());
        }
        die(var_dump($item));
        $event = new CartItemEvent($cart, $item);
        $event->isFresh(true);

        // Update models
        $eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_INITIALIZE, $event);
        $eventDispatcher->dispatch(SyliusCartEvents::CART_CHANGE, new GenericEvent($cart));
        $eventDispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);

        // Write flash message
        $eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_COMPLETED, new FlashEvent());

        return array($cart, $item);
    }
}