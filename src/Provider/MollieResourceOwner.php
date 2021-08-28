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

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return isset($this->response['email']) ? $this->response['email'] : null;
    }

    /**
     * @return string|null
     */
    public function getRegistrationNumber()
    {
        return isset($this->response['registrationNumber']) ? $this->response['registrationNumber'] : null;
    }

    /**
     * @return string|null
     */
    public function getVatNumber()
    {
        return isset($this->response['vatNumber']) ? $this->response['vatNumber'] : null;
    }
}
