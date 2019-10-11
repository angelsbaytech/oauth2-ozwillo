<?php

namespace League\OAuth2\Client\Provider;

class OzwilloUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

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
     * @return string
     */
    public function getFirstName()
    {
        return $this->response['given_name'];
    }

    /**
     * Get preferred last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->response['family_name'];
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        if (array_key_exists('locale', $this->response)) {
            return $this->response['locale'];
        }
        return null;
    }

    /**
     * Get email address.
     *
     * @return string|null
     */
    public function getEmail()
    {
        if (array_key_exists('email', $this->response)) {
            return $this->response['email'];
        }
        return null;
    }

    /**
     * Get phone_number
     *
     * @return string|null
     */
    public function getPhoneNumber()
    {
        if (array_key_exists('phone_number', $this->response)) {
            return $this->response['phone_number'];
        }
        return null;
    }

    /**
     * Get nickname
     *
     * @return string|null
     */
    public function getNickname()
    {
        if (array_key_exists('nickname', $this->response)) {
            return $this->response['nickname'];
        }
        return null;
    }

    /**
     * Get gender
     *
     * @return string|null
     */
    public function getGender()
    {
        if (array_key_exists('gender', $this->response)) {
            return $this->response['gender'];
        }
        return null;
    }

    /**
     * Get birthdate
     *
     * @return string|null
     */
    public function getBirthdate()
    {
        if (array_key_exists('birthdate', $this->response)) {
            return $this->response['birthdate'];
        }
        return null;
    }

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        if (array_key_exists('updated_at', $this->response)) {
            return $this->response['updated_at'];
        }
        return null;
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
}
