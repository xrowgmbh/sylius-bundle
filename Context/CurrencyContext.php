<?php

namespace xrow\syliusBundle\Context;

use Sylius\Bundle\CoreBundle\Context\CurrencyContext as BaseCurrencyContext;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Sylius\Component\Core\Model\UserInterface;

class CurrencyContext extends BaseCurrencyContext
{
    public function getCurrency()
    {
        return 'EUR';
    }

    public function getDefaultCurrency()
    {
        return 'EUR';
    }

    protected function getUser()
    {
        if (
            $this->securityContext->getToken() &&
            $this->securityContext->getToken()->getUser() instanceof UserInterface
        )
        {
            return parent::getUser();
        }

        return null;
    }
}
