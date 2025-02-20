<?php

namespace Mollie\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class Mollie extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Version of this client.
     */
    const CLIENT_VERSION = "2.8.4";

    /**
     * The base url to the Mollie API.
     *
     * @const string
     */
    const MOLLIE_API_URL = 'https://api.mollie.com';

    /**
     * The base url to the Mollie web application.
     *
     * @const string
     */
    const MOLLIE_WEB_URL = 'https://my.mollie.com';

    /**
     * The prefix for the Client ID
     *
     * @const string
     */
    const CLIENT_ID_PREFIX = 'app_';

    /**
     * @var string HTTP method used to revoke tokens.
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * @var string Token type hint for Mollie access tokens.
     */
    const TOKEN_TYPE_ACCESS = 'access_token';

    /**
     * @var string Token type hint for Mollie refresh tokens.
     */
    const TOKEN_TYPE_REFRESH = 'refresh_token';

    /**
     * Shortcuts to the available Mollie scopes.
     *
     * In order to access the Mollie API endpoints on behalf of your app user, your
     * app should request the appropriate scope permissions.
     *
     * @see https://docs.mollie.com/oauth/permissions
     */
    const SCOPE_PAYMENTS_READ       = 'payments.read';
    const SCOPE_PAYMENTS_WRITE      = 'payments.write';
    const SCOPE_REFUNDS_READ        = 'refunds.read';
    const SCOPE_REFUNDS_WRITE       = 'refunds.write';
    const SCOPE_CUSTOMERS_READ      = 'customers.read';
    const SCOPE_CUSTOMERS_WRITE     = 'customers.write';
    const SCOPE_MANDATES_READ       = 'mandates.read';
    const SCOPE_MANDATES_WRITE      = 'mandates.write';
    const SCOPE_SUBSCRIPTIONS_READ  = 'subscriptions.read';
    const SCOPE_SUBSCRIPTIONS_WRITE = 'subscriptions.write';
    const SCOPE_PROFILES_READ       = 'profiles.read';
    const SCOPE_PROFILES_WRITE      = 'profiles.write';
    const SCOPE_INVOICES_READ       = 'invoices.read';
    const SCOPE_SETTLEMENTS_READ    = 'settlements.read';
    const SCOPE_ORDERS_READ         = 'orders.read';
    const SCOPE_ORDERS_WRITE        = 'orders.write';
    const SCOPE_SHIPMENTS_READ      = 'shipments.read';
    const SCOPE_SHIPMENTS_WRITE     = 'shipments.write';
    const SCOPE_ORGANIZATIONS_READ  = 'organizations.read';
    const SCOPE_ORGANIZATIONS_WRITE = 'organizations.write';
    const SCOPE_ONBOARDING_READ     = 'onboarding.read';
    const SCOPE_ONBOARDING_WRITE    = 'onboarding.write';
    const SCOPE_PAYMENT_LINKS_READ    = 'payment-links.read';
    const SCOPE_PAYMENT_LINKS_WRITE = 'payment-links.write';
    const SCOPE_BALANCES_READ        = 'balances.read';
    const SCOPE_TERMINALS_READ       = 'terminals.read';
    const SCOPE_TERMINALS_WRITE      = 'terminals.write';

    /**
     * @var string
     */
    private $mollieApiUrl = self::MOLLIE_API_URL;

    /**
     * @var string
     */
    private $mollieWebUrl = self::MOLLIE_WEB_URL;

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (isset($options["clientId"]) && strpos($options["clientId"], self::CLIENT_ID_PREFIX) !== 0) {
            throw new \DomainException("Mollie needs the client ID to be prefixed with " . self::CLIENT_ID_PREFIX . ".");
        }
    }

    /**
     * Define Mollie api URL
     *
     * @param string $url
     * @return Mollie
     */
    public function setMollieApiUrl($url): self
    {
        $this->mollieApiUrl = $url;

        return $this;
    }

    /**
     * Define Mollie web URL
     *
     * @param string $url
     * @return Mollie
     */
    public function setMollieWebUrl($url): self
    {
        $this->mollieWebUrl = $url;

        return $this;
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->mollieWebUrl . '/oauth2/authorize';
    }

    /**
     * Returns the base URL for requesting or revoking an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->mollieApiUrl . '/oauth2/tokens';
    }

    /**
     * Returns the URL for requesting the app user's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return static::MOLLIE_API_URL . '/v2/organizations/me';
    }

    /**
     * Revoke a Mollie access token.
     *
     * @param string $accessToken
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function revokeAccessToken($accessToken): ResponseInterface
    {
        return $this->revokeToken(self::TOKEN_TYPE_ACCESS, $accessToken);
    }

    /**
     * Revoke a Mollie refresh token.
     *
     * @param string $refreshToken
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function revokeRefreshToken($refreshToken): ResponseInterface
    {
        return $this->revokeToken(self::TOKEN_TYPE_REFRESH, $refreshToken);
    }

    /**
     * Revoke a Mollie access token or refresh token.
     *
     * @param string $type
     * @param string $token
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function revokeToken($type, $token): ResponseInterface
    {
        return $this->getRevokeTokenResponse([
            'token_type_hint' => $type,
            'token' => $token,
        ]);
    }

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param mixed $grant
     * @param array<string, mixed> $options
     * @return AccessTokenInterface
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
    */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        if (isset($options['scope']) && is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
        ];

        if (!empty($this->pkceCode)) {
            $params['code_verifier'] = $this->pkceCode;
        }

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Sends a token revocation request and returns an response instance.
     *
     * @param array $params
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getRevokeTokenResponse(array $params): ResponseInterface
    {
        $params['client_id'] = $this->clientId;
        $params['client_secret'] = $this->clientSecret;
        $params['redirect_uri'] = $this->redirectUri;

        $options = ['headers' => ['content-type' => 'application/x-www-form-urlencoded']];
        $options['body'] = $this->buildQueryString($params);

        $request = $this->getRequest(
            self::METHOD_DELETE,
            $this->getBaseAccessTokenUrl([]),
            $options
        );

        return $this->getHttpClient()->send($request);
    }

    /**
     * The Mollie OAuth provider requests access to the organizations.read scope
     * by default to enable retrieving the app user's details.
     *
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [
            self::SCOPE_ORGANIZATIONS_READ,
        ];
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string      $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        if (!isset($data['error'])) {
            throw new IdentityProviderException($response->getReasonPhrase(), $response->getStatusCode(), $response);
        }

        if (isset($data['error']['type']) && isset($data['error']['message'])) {
            $message = sprintf('[%s] %s', $data['error']['type'], $data['error']['message']);
        } else {
            $message = $data['error'];
        }

        if (isset($data['error']['field'])) {
            $message .= sprintf(' (field: %s)', $data['error']['field']);
        }

        throw new IdentityProviderException($message, $response->getStatusCode(), $response);
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array       $response
     * @param  AccessToken $token
     * @return MollieResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token): MollieResourceOwner
    {
        return new MollieResourceOwner($response);
    }

    /**
     * Returns the default headers used by this provider.
     *
     * Typically this is used to set 'Accept' or 'Content-Type' headers.
     *
     * @return array
     */
    protected function getDefaultHeaders()
    {
        return [
            'User-Agent' => implode(' ', [
                "MollieOAuth2PHP/" . self::CLIENT_VERSION,
                "PHP/" . phpversion(),
            ])
        ];
    }
}
