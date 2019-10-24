<?php namespace Mollie\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Mollie extends AbstractProvider
{
	use BearerAuthorizationTrait;

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
	const MOLLIE_WEB_URL = 'https://www.mollie.com';

	/**
	 * The prefix for the Client ID
	 *
	 * @const string
	 */
	const CLIENT_ID_PREFIX = 'app_';

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
    public function setMollieApiUrl ($url)
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
    public function setMollieWebUrl ($url)
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
	public function getBaseAuthorizationUrl ()
	{
		return $this->mollieWebUrl . '/oauth2/authorize';
	}

	/**
	 * Returns the base URL for requesting an access token.
	 *
	 * Eg. https://oauth.service.com/token
	 *
	 * @param array $params
	 * @return string
	 */
	public function getBaseAccessTokenUrl (array $params)
	{
		return $this->mollieApiUrl . '/oauth2/tokens';
	}

	/**
	 * Returns the URL for requesting the app user's details.
	 *
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl (AccessToken $token)
	{
		return static::MOLLIE_API_URL . '/v2/organizations/me';
	}

	/**
	 * The Mollie OAuth provider requests access to the organizations.read scope
	 * by default to enable retrieving the app user's details.
	 *
	 * @return string[]
	 */
	protected function getDefaultScopes ()
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
	protected function getScopeSeparator ()
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
	protected function checkResponse (ResponseInterface $response, $data)
	{
		if ($response->getStatusCode() >= 400)
		{
			if (isset($data['error']))
			{
				if (isset($data['error']['type']) && isset($data['error']['message'])) {
					$message = sprintf('[%s] %s', $data['error']['type'], $data['error']['message']);
				} else {
					$message = $data['error'];
				}

				if (isset($data['error']['field']))
				{
					$message .= sprintf(' (field: %s)', $data['error']['field']);
				}
			}
			else
			{
				$message = $response->getReasonPhrase();
			}

			throw new IdentityProviderException($message, $response->getStatusCode(), $response);
		}
	}

	/**
	 * Generates a resource owner object from a successful resource owner
	 * details request.
	 *
	 * @param  array       $response
	 * @param  AccessToken $token
	 * @return ResourceOwnerInterface
	 */
	protected function createResourceOwner (array $response, AccessToken $token)
	{
		return new MollieResourceOwner($response);
	}
}
