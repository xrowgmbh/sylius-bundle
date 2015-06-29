<?php

namespace xrow\syliusBundle\Repository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

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
    private $client = NULL;
    private $cookiePlugin;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        if($this->client === NULL) {
            // Create a guzzle Client
            $client = new Client();
            // Create a new cookie plugin
            $this->cookiePlugin = new CookiePlugin(new ArrayCookieJar());
            // Add the cookie plugin to the client
            $client->addSubscriber($this->cookiePlugin);
            $this->client = $client;
        }
    }

    public function loadUserByCredentials($username, $password)
    {
        try {
            $username = urlencode($username);
            $password = urlencode($password);
            // Get client data of the oauth client
            $client_id = $this->container->getParameter('oauth_client_id');
            $client_secret = $this->container->getParameter('oauth_client_secret');
            $base_url = $this->container->getParameter('oauth_baseurl');
            $this->client->setBaseUrl($base_url);
            $cookieJar = $this->cookiePlugin->getCookieJar();
            // All requests
            $requestUrls = array(
                    'accessToken' => '/oauth/v2/token?client_id='.$client_id.'&client_secret='.$client_secret.'&grant_type=password&username='.$username.'&password='.$password,
                    'authentication' => '/xrowapi/v1/auth?access_token=%s',
                    'user' => '/xrowapi/v1/user',
                    'account' => '/xrowapi/v1/account',
                    'subscriptions' => '/xrowapi/v1/subscriptions',
            );
            $oauthToken = $this->container->get('security.context')->getToken();
            if ($oauthToken instanceof AnonymousToken) {
                $accessTokenResponse = $this->client->get($requestUrls['accessToken'])->send();
                $cookieJar->addCookiesFromResponse($accessTokenResponse);
                $accessTokenJson = $accessTokenResponse->json();
                if (isset($accessTokenJson['access_token'])) {
                    // Set authentication
                    $authenticationResponse = $this->client->get(sprintf($requestUrls['authentication'], $accessTokenJson['access_token']))->send();
                    $cookieJar->addCookiesFromResponse($authenticationResponse);
                }
            }
            $user = $this->client->get($requestUrls['user'])->send()->json();
            $account = $this->client->get($requestUrls['account'])->send()->json();
            $subscriptions = $this->client->get($requestUrls['subscriptions'])->send()->json();
            $userData = array(
                    'user' => $user['result'],
                    'account' => $account['result'],
                    'subscriptions' => $subscriptions['result']);
            var_dump($this->client);
            return $userData;
        }
        catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $test = $e->getResponse();
            die(var_dump($test));
        }
        catch(OAuth2AuthenticateException $e) {
            die('hier drin');
            /*} catch (OAuth2AuthenticateException $e) {
                $exception = $this->errorHandling($e);
                throw new OAuth2AuthenticateException($exception['httpCode'], 'access_token', $exception['type'], $exception['error_description']);
            }*/
            // @TODO: wenn der Access Token abgelaufen ist, landet man wahrscheinlich hier. Dann diesen erneuern
            throw new OAuth2ServerException(OAuth2ServerException::HTTP_BAD_REQUEST, OAuth2ServerException::ERROR_INVALID_GRANT, " Errorclass(".get_class($e).", Message: ".$e->getMessage().")");
        }
        catch(Exception $e){
            die(var_dump('bitte abfangen '. $e->getMessage()));
        }
    }
}