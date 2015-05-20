<?php

namespace xrow\syliusBundle\Repository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

use OAuth2\OAuth2ServerException;

/**
* UserRepository
*/
class UserRepository
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function loginUser($username, $password)
    {
        try {
            // Create a guzzle Client
            $client = new Client();
            // Create a new cookie plugin
            $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
            // Add the cookie plugin to the client
            $client->addSubscriber($cookiePlugin);
            // get client data of the oauth client
            $client_id = $this->container->getParameter('oauth_client_id');
            $client_secret = $this->container->getParameter('oauth_client_secret');
            $username = urlencode($username);
            $password = urlencode($password);
            $url = 'http://www.wuv-abo.de.example.com';
            // all requests
            $requestUrls = array(
                'accessToken' => $url.'/oauth/v2/token?client_id='.$client_id.'&client_secret='.$client_secret.'&grant_type=password&username='.$username.'&password='.$password,
                'authentication' => $url.'/xrowapi/v1/auth?access_token=%s&grant_type=client_credentials',
                'user' => $url.'/xrowapi/v1/user',
                'account' => $url.'/xrowapi/v1/account',
                'subscriptions' => $url.'/xrowapi/v1/subscriptions',
            );
            // get access token
            $accessTokenResponse = $client->get($requestUrls['accessToken'])->send();
            $accessTokenJson = $accessTokenResponse->json();
            if (isset($accessTokenJson['access_token'])) {
                // set authentication
                $authenticationResponse = $client->get(sprintf($requestUrls['authentication'], $accessTokenJson['access_token']))->send();
                // get user data
                $userData = array('user' => $client->get($requestUrls['user'])->send()->json(),
                                  'account' => $client->get($requestUrls['account'])->send()->json(),
                                  'subscriptions' => $client->get($requestUrls['subscriptions'])->send()->json());
                return $userData;
            }
            else {
                throw new OAuth2ServerException(OAuth2ServerException::HTTP_BAD_REQUEST, OAuth2ServerException::ERROR_INVALID_GRANT, "User not valid. No access token.");
            }
        }
        catch(Exception $e){
            throw new OAuth2ServerException(OAuth2ServerException::HTTP_BAD_REQUEST, OAuth2ServerException::ERROR_INVALID_GRANT, " Errorclass(".get_class($e).", Message: ".$e->getMessage().")");
        }
    }
}