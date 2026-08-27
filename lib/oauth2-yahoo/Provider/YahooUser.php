<?php

namespace Hayageek\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class YahooUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;


    /**
     * @var image URL
     */
    private $imageUrl;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId()
    {
        return $this->response['sub'];
    }

    /**
     * Get preferred display name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->response['name'];
    }

    /**
     * Get preferred first name.
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->getResponseValue('given_name');
    }

    /**
     * Get preferred last name.
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->getResponseValue('family_name');
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->getResponseValue('locale');
    }

    /**
     * Get email address.
     *
     * @return string|null
     */
    public function getEmail()
    {
        if (!empty($this->response['profile']['emails'])) {
            return $this->response['profile']['emails'][0]['handle'];
        }
    }

    /**
     * Get avatar image URL.
     *
     * @return string|null
     */
    public function getAvatar()
    {
        return $this->getResponseValue('picture');
    }

    /**
     * Get nickname.
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->getResponseValue('nickname');
    }

    /**
     * Get preferred username.
     *
     * @return string|null
     */
    public function getPreferredUsername()
    {
        return $this->getResponseValue('preferred_username');
    }

    /**
     * Get birth date.
     *
     * @return string|null
     */
    public function getBirthYear()
    {
        return $this->getResponseValue('birthdate');
    }

    /**
     * Get phone number.
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->getResponseValue('phone_number');
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    private function getResponseValue($key)
    {
        if (array_key_exists($key, $this->response)) {
            return $this->response[$key];
        }
        return null;
    }
}
