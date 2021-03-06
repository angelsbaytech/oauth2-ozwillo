<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class Ozwillo extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string HTTP method used to DELETE Instance provision on error.
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * @var array List of scopes that will be used for authentication.
     */
    protected $scopes = [];

    public function getBaseAuthorizationUrl()
    {
        return 'https://accounts.ozwillo-preprod.eu/a/auth';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://accounts.ozwillo-preprod.eu/a/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://accounts.ozwillo-preprod.eu/a/userinfo';
    }

    public function getResourceOwnerDeletePendingInstanceUrl($instanceId)
    {
        return sprintf('https://accounts.ozwillo-preprod.eu/apps/pending-instance/%s', $instanceId);
    }

    protected function getAuthorizationParameters(array $options)
    {
        $options['response_mode'] = 'query';
        $options['nonce'] = uniqid('oz-');
//        $options['nonce'] = bin2hex(random_bytes(10));
        if (empty($options['code_challenge'])) {
            $options['code_challenge'] = null;
        }
        if (empty($options['code_challenge_method'])) {
            $options['code_challenge_method'] = null;
        }

        // The "code_challenge_method" option MUST be removed to prevent conflicts with non-empty "code_challenge".
        if (!empty($options['code_challenge'])) {
            $options['code_challenge_method'] = null;
        }
        if (empty($options['prompt'])) {
            $options['prompt'] = null;
        }
        if (empty($options['id_token_hint'])) {
            $options['id_token_hint'] = null;
        }
        if (empty($options['max_age'])) {
            $options['max_age'] = null;
        }
        if (empty($options['claims'])) {
            $options['claims'] = null;
        }
        if (empty($options['ui_locales'])) {
            $options['ui_locales'] = null;
        }

        // Default scopes MUST be included for OpenID Connect.
        // Additional scopes MAY be added by constructor or option.
        $scopes = array_merge($this->getDefaultScopes(), $this->scopes);

        if (!empty($options['scope'])) {
            $scopes = array_merge($scopes, $options['scope']);
        }

        $options['scope'] = array_unique($scopes);
        if (isset($options['client_id'])) {
            $this->clientId = $options['client_id'];
        }
        if (isset($options['redirect_uri'])) {
            $this->redirectUri = $options['redirect_uri'];
        }
        $options['redirect_uri'] = $this->redirectUri;

        return parent::getAuthorizationParameters($options);
    }

    protected function getDefaultScopes()
    {
        // "openid" MUST be the first scope in the list.
        return [
            'openid',
            'email',
            'profile',
            'address',
            'phone',
            'offline_access',
        ];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        // @codeCoverageIgnoreStart
        if (empty($data['error'])) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $code = 0;
        $error = $data['error'];

        if (is_array($error)) {
            $code = $error['code'];
            $error = $error['message'];
        }

        throw new IdentityProviderException($error, $code, $data);
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new OzwilloUser($response);

        return $user;
    }

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed $grant
     * @param  array $options
     * @throws IdentityProviderException
     * @return AccessTokenInterface
     *  $ozwilloProvider->getAccessToken('authorization_code', [
     * 'code' => $request->get('code'),
     * 'state' => $request->get('state'),
     * 'client_id' => $collectivityOzwillo->getClientId(),
     * 'client_secret' => $collectivityOzwillo->getClientSecret(),
     * 'redirect_uri' => $request->get('redirect_uri'),
     * ]);
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'code' => (isset($options['code'])) ? $options['code'] : '',
            'client_id' => (isset($options['client_id'])) ? $options['client_id'] : $this->clientId,
            'client_secret' => (isset($options['client_secret'])) ? $options['client_secret'] : $this->clientId,
            'redirect_uri' => (isset($options['redirect_uri'])) ? $options['redirect_uri'] : $this->redirectUri,
        ];

        $params = $grant->prepareRequestParameters($params, $options);
        $request = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        if (isset($params['client_id'])) {
            $response['client_id'] = $params['client_id'];
        }
        $prepared = $this->prepareAccessTokenResponse($response);
        $token = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * https://doc.ozwillo.com/#s3-3bis-provider-dismiss
     *
     * @param $instanceId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function dismissInstanceOnError($instanceId)
    {
        if (!$instanceId || empty($instanceId)) {
            return false;
        }
        $httpClient = $this->getHttpClient();
        $httpClient->request(self::METHOD_DELETE, $this->getResourceOwnerDeletePendingInstanceUrl($instanceId));

        return true;
    }
}
