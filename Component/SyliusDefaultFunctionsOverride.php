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

use Doctrine\Common\Persistence\ObjectManager;
use Faker\Provider\DateTime as FakeDateTime;

use xrow\syliusBundle\Entity\ProductVariant as SyliusProductVariant;

class SyliusDefaultFunctionsOverride
{
    protected $container;
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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {}
    
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {}

    public function addProductToCartAction($contentId)
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $cart = $this->sylius['CartProvider']->getCart();
        $cartItem = $this->sylius['CartItemController']->createNew(); // Sylius\Component\Core\Model\OrderItem
        $this->sylius['ProductRepository']->setContainer($this->container);
        $this->sylius['ProductVariantRepository']->setContainer($this->container);
        try {
            if (!$syliusProduct = $this->sylius['ProductRepository']->findOneBy(array('content_id' => $contentId))) {
                // create new sylius product
                $syliusProduct = $this->createNewProductAndVariant($contentId);
            }
            die(var_dump($syliusProduct));
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

        $product->setCurrentLocale($locale);
        $product->setFallbackLocale($locale);
        $product->setSlug($slug);
        $product->setName($name);
        $product->setDescription($description);

        $product = $this->addMasterVariant($product, $eZObject['contentObject']);

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

        #$this->addAttribute($product, 'Book author', $author);
        #$this->addAttribute($product, 'Book ISBN', $isbn);
        #$this->addAttribute($product, 'Book pages', $this->faker->randomNumber(3));

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
     * @param string           $sku
     */
    protected function addMasterVariant($product, $contentObject)
    {
        $variant = $this->sylius['ProductVariantRepository']->createNew();
        $variant->setProduct($product);

        $price = (int)$contentObject->getFieldValue('price_de')->__toString() * 100;
        // Sylius Produkt Variant
        $variant->setSku(intval(rand(1,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9)));
        $variant->setAvailableOn(FakeDateTime::dateTimeBetween('-1 year'));
        $variant->setOnHand(100);
        $variant->setPrice($price);

        $product->setMasterVariant($variant);
        return $product;
    }
}