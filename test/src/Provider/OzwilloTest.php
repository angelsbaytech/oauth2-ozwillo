<?php


namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Ozwillo as OzwilloProvider;
use League\OAuth2\Client\Provider\OzwilloUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;


class OzwilloTest extends TestCase
{
    /** @var OzwilloProvider */
    protected $provider;
    protected function setUp()
    {
        $this->provider = new OzwilloProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none'
        ]);
    }
    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('response_mode', $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('nonce', $query);

        $this->assertArrayNotHasKey('code_challenge', $query);
        $this->assertArrayNotHasKey('code_challenge_method', $query);
        $this->assertArrayNotHasKey('prompt', $query);
        $this->assertArrayNotHasKey('id_token_hint', $query);
        $this->assertArrayNotHasKey('max_age', $query);
        $this->assertArrayNotHasKey('claims', $query);
        $this->assertArrayNotHasKey('ui_locales', $query);

        $this->assertContains('offline_access', $query['scope']);
        $this->assertContains('phone', $query['scope']);
        $this->assertContains('address', $query['scope']);
        $this->assertContains('email', $query['scope']);
        $this->assertContains('profile', $query['scope']);
        $this->assertContains('openid', $query['scope']);
        $this->assertAttributeNotEmpty('state', $this->provider);
    }
    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);
        $this->assertEquals('/a/token', $uri['path']);
    }
    /**
     * @link https://accounts.google.com/.well-known/openid-configuration
     */
    public function testResourceOwnerDetailsUrl()
    {
        $token = $this->mockAccessToken();
        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $this->assertEquals('https://accounts.ozwillo-preprod.eu/a/userinfo', $url);
    }

    /**
     * @throws \Exception
     */
    public function testUserData()
    {
        // Mock
        $response = [
            'sub' => '12345',
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
            'middle_name' => 'middle',
            'nickname' => 'nickname',
            'gender' => 'female',
            'birthdate' => '1985-09-21',
            'locale' => 'fr-FR',
            'email' => 'mock.name@example.com',
            'email_verified' => true,
            'phone_number' => '0101010101',
            'phone_number_verified' => true,
            'updated_at' => '1570811822',
        ];
        $token = $this->mockAccessToken();
        $provider = Phony::partialMock(OzwilloProvider::class);
        $provider->fetchResourceOwnerDetails->returns($response);
        $ozwillo = $provider->get();
        // Execute
        $user = $ozwillo->getResourceOwner($token);
        // Verify
        Phony::inOrder(
            $provider->fetchResourceOwnerDetails->called()
        );
        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);
        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('mock name', $user->getName());
        $this->assertEquals('mock', $user->getFirstName());
        $this->assertEquals('name', $user->getLastName());
        $this->assertEquals('mock.name@example.com', $user->getEmail());
        $user = $user->toArray();
        $this->assertArrayHasKey('sub', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('given_name', $user);
        $this->assertArrayHasKey('family_name', $user);
        $this->assertArrayHasKey('middle_name', $user);
        $this->assertArrayHasKey('nickname', $user);
        $this->assertArrayHasKey('gender', $user);
        $this->assertArrayHasKey('birthdate', $user);
        $this->assertArrayHasKey('locale', $user);
        $this->assertArrayHasKey('updated_at', $user);
    }
    public function testErrorResponse()
    {
        // Mock
        $error_json = '{"error": {"code": 400, "message": "I am an error"}}';
        $response = Phony::mock('GuzzleHttp\Psr7\Response');
        $response->getHeader->returns(['application/json']);
        $response->getBody->returns($error_json);
        $provider = Phony::partialMock(OzwilloProvider::class);
        $provider->getResponse->returns($response);
        $ozwillo = $provider->get();
        $token = $this->mockAccessToken();
        // Expect
        $this->expectException(IdentityProviderException::class);
        // Execute
        $user = $ozwillo->getResourceOwner($token);
        // Verify
        Phony::inOrder(
            $provider->getResponse->calledWith($this->instanceOf('GuzzleHttp\Psr7\Request')),
            $response->getHeader->called(),
            $response->getBody->called()
        );
    }
    /**
     * @return AccessToken
     */
    private function mockAccessToken()
    {
        return new AccessToken([
            'access_token' => 'mock_access_token',
        ]);
    }
}