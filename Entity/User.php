<?php

namespace xrow\syliusBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Sylius\Component\Core\Model\User as SyliusUser;
use Sylius\Component\Rbac\Model\RoleInterface;
use Sylius\Component\Core\Model\AddressInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="xrow\syliusBundle\Repository\UserRepository")
 */
class User extends SyliusUser
{
    /**
     * {@inheritdoc}
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAddress(AddressInterface $address)
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        return $this->addresses->contains($address);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    public function getFullName()
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeleted()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        parent::setEmail($email);
        parent::setUsername($email);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailCanonical($emailCanonical)
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        parent::setEmailCanonical($emailCanonical);
        parent::setUsernameCanonical($emailCanonical);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthAccounts()
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        return $this->oauthAccounts;
    }

    /**
     * {@inheritdoc}
     */
    public function getOAuthAccount($provider)
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        if ($this->oauthAccounts->isEmpty()) {
            return null;
        }

        $filtered = $this->oauthAccounts->filter(function (UserOAuthInterface $oauth) use ($provider) {
            return $provider === $oauth->getProvider();
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        return $filtered->current();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationRoles()
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        die('Bin hier gelandet und brauche eine sinnvolle Wiedergabe in ' . get_class($this) . ', Funktion ' . __METHOD__);

        return array();
    }
}
