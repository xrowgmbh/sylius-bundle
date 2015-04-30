<?php

namespace xrow\syliusBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $output->writeln(
            sprintf(
                'A new order withh id <info>%s</info> has been added',
                $order->getId()
            )
        );
    }
}