<?php

namespace xrow\syliusBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;
use Sylius\Component\Cart\Event\CartItemEvent;
use Sylius\Component\Cart\Resolver\ItemResolvingException;
use Sylius\Component\Cart\SyliusCartEvents;
use Sylius\Component\Resource\Event\FlashEvent;
use Symfony\Component\EventDispatcher\GenericEvent;

class SyliusDefaultFunctionsOverride
{
    public static function addProductToCartAction($request)
    {
        $provider = $this->container->get('sylius.cart_provider');
        $resolver = $this->container->get('sylius.cart_resolver');
        $eventDispatcher = $this->container->get('event_dispatcher');
        $cart = $provider->getCart();
        $cartItemRef = new CartItemInterface();
        $emptyItem = $cartItemRef->createNew();

        try {
            $item = $resolver->resolve($emptyItem, $request);
        } catch (ItemResolvingException $exception) {
            // Write flash message
            $eventDispatcher->dispatch(SyliusCartEvents::ITEM_ADD_ERROR, new FlashEvent($exception->getMessage()));
            throw new ItemResolvingException($exception->getMessage());
        }

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