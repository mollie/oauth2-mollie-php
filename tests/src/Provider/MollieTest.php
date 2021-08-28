<?php namespace Mollie\OAuth2\Client\Test\Provider;

use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Mockery as m;
use Mollie\OAuth2\Client\Provider\Mollie;
use Mollie\OAuth2\Client\Provider\MollieResourceOwner;
use Psr\Http\Message\ResponseInterface;

class MollieTest extends \PHPUnit_Framework_TestCase
{
    const MOCK_CLIENT_ID = 'app_mock_client_id';
    const MOCK_SECRET = 'mock_secret';
    const REDIRECT_URI = 'none';

    const OPTIONS = [
        'clientId' => self::MOCK_CLIENT_ID,
        'clientSecret' => self::MOCK_SECRET,
        'redirectUri' => self::REDIRECT_URI,
    ];

    protected $provider;

    protected function setUp()
    {
        $this->provider = new Mollie(self::OPTIONS);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testClientIdShouldThrowExceptionWhenNotPrefixed()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Mollie needs the client ID to be prefixed with " . Mollie::CLIENT_ID_PREFIX . ".");

        new Mollie([
            'clientId'     => 'not_pefixed_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
        ]);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);

        $this->assertEquals('https://api.mollie.com/oauth2/tokens', $url);
    }

    public function testAuthorizationUrl()
    {
        $authUrl = $this->provider->getAuthorizationUrl();

        list($url, $queryString) = explode('?', $authUrl);
        parse_str($queryString, $query);

        $this->assertEquals('https://www.mollie.com/oauth2/authorize', $url);
        $this->assertEquals([
            'state' => $this->provider->getState(),
            'client_id' => self::MOCK_CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'scope' => 'organizations.read',
            'response_type' => 'code',
            'approval_prompt' => 'auto',
        ], $query);
        $this->assertRegExp('/^[a-f0-9]{32}$/i', $this->provider->getState());
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock(AccessToken::class);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        $this->assertEquals('https://api.mollie.com/v2/organizations/me', $url);
    }

    public function testGetAccessToken()
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testRevokeToken()
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('{"client_id":'.self::MOCK_CLIENT_ID.', "client_secret":'.self::MOCK_SECRET.', "redirect_uri":'.self::REDIRECT_URI.', "token_type_hint":"access_token":"mock_access_token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(204);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $result = $this->provider->revokeAccessToken('mock_access_token');

        $this->assertEquals($result->getStatusCode(), 204);
    }

    public function testRevokeAccessToken()
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('{"client_id":'.self::MOCK_CLIENT_ID.', "client_secret":'.self::MOCK_SECRET.', "redirect_uri":'.self::REDIRECT_URI.', "token_type_hint":"access_token":"mock_access_token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(204);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $result = $this->provider->revokeAccessToken('mock_access_token');

        $this->assertEquals($result->getStatusCode(), 204);
    }

    public function testRevokeRefreshToken()
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('{"client_id":'.self::MOCK_CLIENT_ID.', "client_secret":'.self::MOCK_SECRET.', "redirect_uri":'.self::REDIRECT_URI.', "token_type_hint":"refresh_token":"mock_refresh_token"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(204);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $result = $this->provider->revokeRefreshToken('mock_refresh_token');

        $this->assertEquals($result->getStatusCode(), 204);
    }

    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();
        $status = rand(400, 600);

        $postResponse = m::mock(ResponseInterface::class);
        $postResponse->shouldReceive('getBody')->andReturn('{"error":{"type":"request","message":"'.$message.'"}}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);

        $this->expectException(IdentityProviderException::class);

        $this->provider->setHttpClient($client);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testUserData()
    {
        $postResponse = m::mock(ResponseInterface::class);
        $postResponse->shouldReceive('getBody')->andReturn(
            'access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token'
        );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $accountResponse = m::mock(ResponseInterface::class);
        $accountResponse->shouldReceive('getBody')->andReturn(
            '{
                "resource": "organization",
                "id": "org_12345678",
                "name": "Mollie B.V.",
                "email": "info@mollie.com",
                "address": {
                    "streetAndNumber": "Keizersgracht 126",
                    "postalCode": "1015 CW",
                    "city": "Amsterdam",
                    "country": "NL"
                },
                "registrationNumber": "30204462",
                "vatNumber": "NL815839091B01"
                "_links": {
                    "self": {
                        "href": "https://api.mollie.com/v2/organizations/org_12345678",
                        "type": "application/hal+json"
                    },
                    "dashboard": {
                        "href": "https://mollie.com/dashboard/org_12345678",
                        "type": "text/html"
                    },
                    "chargebacks": {
                        "href": "https://api.mollie.com/v2/chargebacks",
                        "type": "application/hal+json"
                    },
                    "customers": {
                        "href": "https://api.mollie.com/v2/customers",
                        "type": "application/hal+json"
                    },
                    "invoices": {
                        "href": "https://api.mollie.com/v2/invoices",
                        "type": "application/hal+json"
                    },
                    "payments": {
                        "href": "https://api.mollie.com/v2/payments",
                        "type": "application/hal+json"
                    },
                    "profiles": {
                        "href": "https://api.mollie.com/v2/profiles",
                        "type": "application/hal+json"
                    },
                    "refunds": {
                        "href": "https://api.mollie.com/v2/refunds",
                        "type": "application/hal+json"
                    },
                    "settlements": {
                        "href": "https://api.mollie.com/v2/settlements",
                        "type": "application/hal+json"
                    },
                    "documentation": {
                        "href": "https://docs.mollie.com/reference/v2/organizations-api/me",
                        "type": "text/html"
                    }
                }
            }'
        );
        $accountResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $accountResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $accountResponse);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $account = $this->provider->getResourceOwner($token);

        assert($account instanceof MollieResourceOwner);

        $array = $account->toArray();

        $this->assertEquals('org_12345678', $account->getId());
        $this->assertEquals('org_12345678', $array['id']);
        $this->assertEquals('Mollie B.V.', $array['name']);
        $this->assertEquals('info@mollie.com', $array['email']);
        $this->assertEquals('info@mollie.com', $account->getEmail());
        $this->assertEquals(
            [
                "streetAndNumber" => "Keizersgracht 126",
                "postalCode" => "1015 CW",
                "city" => "Amsterdam",
                "country" => "NL",
            ],
            $array['address']
        );
        $this->assertEquals('30204462', $array['registrationNumber']);
        $this->assertEquals('30204462', $account->getRegistrationNumber());
        $this->assertEquals('NL815839091B01', $array['vatNumber']);
        $this->assertEquals('NL815839091B01', $account->getVatNumber());
    }

    public function testWhenDefiningADifferentMollieApiUrlThenUseThisOnApiCalls()
    {
        $this->provider->setMollieApiUrl('https://api.mollie.nl');

        $this->assertEquals('https://api.mollie.nl/oauth2/tokens', $this->provider->getBaseAccessTokenUrl([]));
    }

    public function testWhenDefiningADifferentMollieWebUrlThenUseThisForAuthorize()
    {
        $this->provider->setMollieWebUrl('https://www.mollie.nl');

        list($url) = explode('?', $this->provider->getAuthorizationUrl());
        $this->assertEquals('https://www.mollie.nl/oauth2/authorize', $url);
    }
}
