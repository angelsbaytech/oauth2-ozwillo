<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\OzwilloUser;
use PHPUnit\Framework\TestCase;

class OzwilloUserTest extends TestCase
{
    public function testUserDefaults()
    {
        // Mock
        $user = new OzwilloUser([
            'sub' => '12345',
            'email' => 'mock.name@example.com',
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
            'phone_number' => '0101010101',
            'gender' => 'male',
            'locale' => 'en',
        ]);
        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('mock name', $user->getName());
        $this->assertEquals('mock', $user->getFirstName());
        $this->assertEquals('name', $user->getLastName());
        $this->assertEquals('en', $user->getLocale());
        $this->assertEquals('mock.name@example.com', $user->getEmail());
        $this->assertEquals('0101010101', $user->getPhoneNumber());
        $this->assertEquals('male', $user->getGender());
    }
    public function testUserPartialData()
    {
        $user = new OzwilloUser([
            'sub' => '12345',
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
        ]);
        $this->assertEquals(null, $user->getEmail());
        $this->assertEquals(null, $user->getPhoneNumber());
        $this->assertEquals(null, $user->getGender());
    }
}