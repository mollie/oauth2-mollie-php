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
		$this->assertEquals('/oauth2/token', $uri['path']);
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

		$this->assertEquals('/v1/organization', $uri['path']);
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
		$postResponse->shouldReceive('getBody')->andReturn('{"error":{"type":"request","message":"' . $message . '"}}');
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
		$id                  = uniqid();
		$name                = uniqid();
		$email               = uniqid();
		$phone               = uniqid();
		$address             = uniqid();
		$postalCode          = uniqid();
		$city                = uniqid();
		$country             = uniqid();
		$registrationType    = uniqid();
		$registrationNumber  = uniqid();
		$legalRepresentative = uniqid();

		$postResponse = m::mock(\Psr\Http\Message\ResponseInterface::class);
		$postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token');
		$postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
		$postResponse->shouldReceive('getStatusCode')->andReturn(200);

		$accountResponse = m::mock(\Psr\Http\Message\ResponseInterface::class);
		$accountResponse->shouldReceive('getBody')->andReturn(
			'{"id":"' . $id . '","name":"' . $name . '","email":"' . $email . '","phone":"' . $phone . '","address":"' . $address . '","postalCode":"' . $postalCode . '","city":"' . $city . '","country":"' . $country . '","registrationType":"' . $registrationType . '","registrationNumber":"' . $registrationNumber . '","legalRepresentative":"' . $legalRepresentative . '"}'
		);
		$accountResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
		$accountResponse->shouldReceive('getStatusCode')->andReturn(200);

		$client = m::mock(\GuzzleHttp\ClientInterface::class);
		$client->shouldReceive('send')
			->times(2)
			->andReturn($postResponse, $accountResponse);

		$this->provider->setHttpClient($client);
		$token   = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
		$account = $this->provider->getResourceOwner($token);

		$this->assertEquals($id, $account->getId());
		$this->assertEquals($id, $account->toArray()['id']);
		$this->assertEquals($name, $account->getName());
		$this->assertEquals($name, $account->toArray()['name']);
		$this->assertEquals($email, $account->getEmail());
		$this->assertEquals($email, $account->toArray()['email']);
		$this->assertEquals($phone, $account->getPhone());
		$this->assertEquals($phone, $account->toArray()['phone']);
		$this->assertEquals($address, $account->getAddress());
		$this->assertEquals($address, $account->toArray()['address']);
		$this->assertEquals($postalCode, $account->getPostalCode());
		$this->assertEquals($postalCode, $account->toArray()['postalCode']);
		$this->assertEquals($city, $account->getCity());
		$this->assertEquals($city, $account->toArray()['city']);
		$this->assertEquals($country, $account->getCountry());
		$this->assertEquals($country, $account->toArray()['country']);
		$this->assertEquals($registrationType, $account->getRegistrationType());
		$this->assertEquals($registrationType, $account->toArray()['registrationType']);
		$this->assertEquals($registrationNumber, $account->getRegistrationNumber());
		$this->assertEquals($registrationNumber, $account->toArray()['registrationNumber']);
		$this->assertEquals($legalRepresentative, $account->getLegalRepresentative());
		$this->assertEquals($legalRepresentative, $account->toArray()['legalRepresentative']);
	}
}