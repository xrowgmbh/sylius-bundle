<?php

namespace xrow\syliusBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use xrow\syliusBundle\Component\SyliusDefaultFunctionsOverride;

class MakingAnOrderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('xrow:sylius:add-product')
            ->setDescription('Making an order')
            ->addOption(
                'contentId',
                null,
                InputOption::VALUE_REQUIRED,
                'eZ contentId'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info>command makes an order.
<info>php %command.full_name% [--contentId=...] name</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentId = $input->getOption('contentId');
        $container = $this->getContainer();
        $syliusOFRef = $container->get('xrow.sylius.override.functions');
        // create a cart
        try {
            $cart = $syliusOFRef->addProductToCart($contentId);
            $output->writeln(sprintf('A new cart with id <info>%s</info> has been added', $cart->getId()));
            // set user
            $user = $syliusOFRef->setUserToOrder($cart);
            // set shipment
            $syliusOFRef->setShipmentToOrder($cart);
            // checkout
            $checkout = $syliusOFRef->checkoutTheOrder($cart);
        } catch (ItemResolvingException $exception) {
            $output->writeln(sprintf('ERROR! Message <info>%s</info> has been added', $exception->getMessage()));
        }
    }
}