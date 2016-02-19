<?php

namespace xrow\syliusBundle\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;

class SyliusUserProvider implements UserProviderInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

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
            $user = $this->userRepository->findOneBy(array('emailCanonical' => $username));
        }
        else {
            $user = $this->userRepository->findOneBy(array('usernameCanonical' => $username));
        }
        //die(var_dump($user->getRoles()));
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
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }
        $refreshedUser = $this->userRepository->find($user->getId());
        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($user->getId())));
        }

        return $refreshedUser;
    }

    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }
}