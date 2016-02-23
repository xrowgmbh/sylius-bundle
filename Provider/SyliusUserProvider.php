<?php

namespace xrow\syliusBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;
use eZ\Publish\Core\MVC\Symfony\Security\UserWrapped as eZUserWrapped;

class SyliusUserProvider implements UserProviderInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->userRepository = $this->em->getRepository('\Sylius\Component\Core\Model\User');
    }

    /**
     * Loads the user for the given username for sylius
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username
     * @throws UsernameNotFoundException if the user is not found
     * @return UserInterface
     */
    public function loadUserByUsername($username)
    {
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userRepository->findOneBy(array('email' => $username));
        }
        else {
            $user = $this->userRepository->findOneBy(array('username' => $username));
        }
        if ($user === null) {
            $message = sprintf(
                'Unable to find an active user object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message);
        }
        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     * @throws UnsupportedUserException if the account is not supported
     * @throws UsernameNotFoundException if user not found
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if ($user instanceof eZUserWrapped) {
            $user = $user->getWrappedUser();
        }
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }
        $refreshedUser = $this->loadUserByUsername($user->getUsername());
        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with Id %s not found', $user->getId()));
        }
        return $refreshedUser;
    }

    public function supportsClass($class)
    {
        return $class === 'Sylius\\Component\\Core\\Model\\User';
    }
}