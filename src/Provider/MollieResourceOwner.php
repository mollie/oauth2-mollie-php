<?php namespace Mollie\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class MollieResourceOwner implements ResourceOwnerInterface
{
	/**
	 * Raw response
	 *
	 * @var array
	 */
	protected $response;

	/**
	 * Set response
	 *
	 * @param array $response
	 */
	public function __construct(array $response)
	{
		$this->response = $response;
	}

	/**
	 * Returns the identifier of the authorized resource owner.
	 *
	 * @return string
	 */
	public function getId ()
	{
		return $this->response['id'];
	}

	/**
	 * Return all of the owner details available as an array.
	 *
	 * @return array
	 */
	public function toArray ()
	{
		return $this->response;
	}
}