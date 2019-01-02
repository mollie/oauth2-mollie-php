![Mollie](https://www.mollie.com/files/Mollie-Logo-Style-Small.png) 

[![Build Status](https://travis-ci.org/mollie/oauth2-mollie-php.svg?branch=master)](https://travis-ci.org/mollie/oauth2-mollie-php)

# Mollie Connect in PHP #

This package provides Mollie OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client). Visit our [API documentation](https://www.mollie.com/en/docs/oauth/overview) for more information about the Mollie implementation of OAuth2.

Use Mollie Connect (OAuth) to easily connect Mollie Merchant accounts to your application. [Mollie Connect](https://www.mollie.com/en/connect) also makes it possible to charge additional fees to your costumers with [Application Fee](https://www.mollie.com/en/docs/reference/payments/create#pfp-params).

## Installation ##

By far the easiest way to install the Mollie API client is to require it with [Composer](http://getcomposer.org/doc/00-intro.md).

	$ composer require mollie/oauth2-mollie-php ^2.0

	    {
	        "require": {
	            "mollie/oauth2-mollie-php": "^2.0"
	        }
	    }


You may also git checkout or [download all the files](https://github.com/mollie/oauth2-mollie-php/archive/master.zip), and include the OAuth 2.0 provider manually.

## Usage

Usage is the same as The League's OAuth client, using `\Mollie\OAuth2\Client\Provider\Mollie` as the provider.

### Authorization Code Flow

```php
$provider = new \Mollie\OAuth2\Client\Provider\Mollie([
    'clientId'     => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',
    'redirectUri'  => 'https://your-redirect-uri',
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code']))
{
    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl([
        // Optional, only use this if you want to ask for scopes the user previously denied.
        'approval_prompt' => 'force', 
        
        // Optional, a list of scopes. Defaults to only 'organizations.read'.
        'scope' => [
	    \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ORGANIZATIONS_READ, 
	    \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_READ,
	], 
    ]);

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state']))
{
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

else
{
    try
    {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
        
        // Using the access token, we may look up details about the resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);
        
        print_r($resourceOwner->toArray());
    }
    catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e)
    {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
```

### Refreshing A Token

```php
$provider = new \Mollie\OAuth2\Client\Provider\Mollie([
    'clientId'     => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',
    'redirectUri'  => 'https://your-redirect-uri',
]);

$grant = new \League\OAuth2\Client\Grant\RefreshToken();
$token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
```

## API documentation ##
If you wish to learn more about our APIs, please visit the [Mollie Developer Portal](https://www.mollie.com/en/developers).

## Want to help us make our API client even better? ##

Want to help us make our API client even better? We take [pull requests](https://github.com/mollie/mollie-api-php/pulls?utf8=%E2%9C%93&q=is%3Apr), sure. But how would you like to contribute to a [technology oriented organization](https://www.mollie.com/nl/blog/post/werken-bij-mollie-sfeer-kansen-en-mogelijkheden/)? Mollie is hiring developers and system engineers. [Check out our vacancies](https://www.mollie.com/nl/jobs) or [get in touch](mailto:personeel@mollie.com).

## License ##
[BSD (Berkeley Software Distribution) License](http://www.opensource.org/licenses/bsd-license.php).
Copyright (c) 2015-2018, Mollie B.V.

## Support ##
Contact: [www.mollie.com](https://www.mollie.com) — info@mollie.com — +31 20-612 88 55

+ [More information about Mollie Connect](https://www.mollie.com/en/connect/)
