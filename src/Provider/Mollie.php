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
	const MOLLIE_API_URL = 'https://api.mollie.nl';

	/**
	 * The base url to the Mollie web application.
	 *
	 * @const string
	 */
	const MOLLIE_WEB_URL = 'https://www.mollie.com';

	/**
	 * Returns the base URL for authorizing a client.
	 *
	 * Eg. https://oauth.service.com/authorize
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl ()
	{
		return static::MOLLIE_WEB_URL . '/oauth2/authorize';
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
		return static::MOLLIE_API_URL . '/oauth2/tokens';
	}

	/**
	 * Returns the URL for requesting the resource owner's details.
	 *
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl (AccessToken $token)
	{
		return static::MOLLIE_API_URL . '/v1/organizations/me';
	}

	/**
	 * Returns the default scopes used by this provider.
	 *
	 * This should only be the scopes that are required to request the details
	 * of the resource owner, rather than all the available scopes.
	 *
	 * @return array
	 */
	protected function getDefaultScopes ()
	{
		return ['organizations.read'];
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
				$message = sprintf('[%s] %s', $data['error']['type'], $data['error']['message']);

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