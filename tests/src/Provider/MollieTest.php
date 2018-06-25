<?php namespace Mollie\OAuth2\Client\Test\Provider;

use Mockery as m;

class MollieTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

	protected function setUp ()
	{
		$this->provider = new \Mollie\OAuth2\Client\Provider\Mollie([
			'clientId'     => 'mock_client_id',
			'clientSecret' => 'mock_secret',
			'redirectUri'  => 'none',
		]);
	}

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/oauth2/tokens', $uri['path']);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock(\League\OAuth2\Client\Token\AccessToken::class);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/v2/organizations/me', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock(\Psr\Http\Message\ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock(\GuzzleHttp\ClientInterface::class);
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();
        $status = rand(400, 600);

        $postResponse = m::mock(\Psr\Http\Message\ResponseInterface::class);
        $postResponse->shouldReceive('getBody')->andReturn('{"error":{"type":"request","message":"'.$message.'"}}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock(\GuzzleHttp\ClientInterface::class);
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);

        $this->provider->setHttpClient($client);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testUserData()
    {
        $postResponse = m::mock(\Psr\Http\Message\ResponseInterface::class);
        $postResponse->shouldReceive('getBody')->andReturn(
            'access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token'
        );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $accountResponse = m::mock(\Psr\Http\Message\ResponseInterface::class);
        $accountResponse->shouldReceive('getBody')->andReturn(
            '{
                "resource": "organization",
                "id": "org_162634",
                "name": "Kicks To The Face B.V.",
                "email": "info@mollie.com",
                "address": {
                    "streetAndNumber": "Keizersgracht 313",
                    "postalCode": "1016 EE",
                    "city": "Amsterdam",
                    "country": "NL"
                },
                "registrationNumber": "370355724",
                "_links": {
                    "self": {
                        "href": "https://api.mollie.com/v2/organizations/me",
                        "type": "application/hal+json"
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

        $client = m::mock(\GuzzleHttp\ClientInterface::class);
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $accountResponse);

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $account = $this->provider->getResourceOwner($token);

        $array = $account->toArray();

        $this->assertEquals('org_162634', $account->getId());
        $this->assertEquals('org_162634', $array['id']);
        $this->assertEquals('Kicks To The Face B.V.', $array['name']);
        $this->assertEquals('info@mollie.com', $array['email']);
        $this->assertEquals(
            [
                "streetAndNumber" => "Keizersgracht 313",
                "postalCode" => "1016 EE",
                "city" => "Amsterdam",
                "country" => "NL",
            ],
            $array['address']
        );
        $this->assertEquals('370355724', $array['registrationNumber']);
    }
}