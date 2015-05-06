<?php

namespace xrow\syliusBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use xrow\syliusBundle\Component\SyliusDefaultFunctionsOverride;

class MakingAnOrderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('xrow:sylius:add-product')
            ->setDescription('Making an order')
            ->addOption(
                'product-id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'eZ contentobject_id as product id'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info>command makes an order.
<info>php %command.full_name% [--product-id=...] name</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $productId = $input->getOption('product-id');
        $request = Request::create('', 'GET', array('id' => $productId));
        // create a cart
        try {
            $cartItemArray = SyliusDefaultFunctionsOverride::addProductToCartAction($request);
            die(var_dump($cartItemArray));
            $output->writeln(sprintf('A new cart with id <info>%s</info> has been added', $cartItemArray[0]->getId()));
        } catch (ItemResolvingException $exception) {
            $output->writeln(sprintf('ERROR! Message <info>%s</info> has been added', $exception->getMessage()));
        }
    }
}