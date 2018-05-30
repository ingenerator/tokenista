<?php

namespace Ingenerator\Tests;

use Ingenerator\Tokenista;
use PHPUnit\Framework\TestCase;

class TokenistaTest extends TestCase
{
    const SECRET = 'our-secret';

    protected $tokenista;

    protected function setUp()
    {
        $this->tokenista = new Tokenista(self::SECRET);
    }

    public function test_it_is_initializable()
    {
        $this->assertInstanceOf(Tokenista::class, $this->tokenista);
    }

    public function test_it_generates_token_strings_in_expected_format()
    {
        $this->assertRegExp('/^[A-Za-z0-9+\/]{16}-[0-9]+-[A-Za-z0-9+\/]{40}$/', $this->tokenista->generate());
    }

    public function test_it_generates_new_random_tokens_each_time()
    {
        $tokenString = $this->tokenista->generate();

        $this->assertNotEquals($tokenString, $this->tokenista->generate());
    }

    public function test_it_generates_tokens_with_default_expiry_if_no_expiry_passed()
    {
        $expiryToken = new Tokenista(self::SECRET, ['lifetime' => 2]);
        $tokenString = $expiryToken->generate();

        $this->assertFalse($expiryToken->isExpired($tokenString));
    }

    public function test_it_validates_token_within_expiry_time()
    {
        $tokenString = $this->tokenista->generate(2);

        $this->assertTrue($this->tokenista->isValid($tokenString));
    }

    public function test_it_does_not_validate_token_after_expiry_time()
    {
        $tokenString = $this->tokenista->generate(0);

        $this->assertFalse($this->tokenista->isValid($tokenString));
    }

    public function test_it_does_not_validate_tampered_token()
    {
        $tokenString = $this->tokenista->generate(3600);
        $tokenString[rand(0, strlen($tokenString) - 1)] = '~';

        $this->assertFalse($this->tokenista->isValid($tokenString));
    }

    public function test_it_does_not_validate_token_signed_with_different_secret()
    {
        $otherToken = new Tokenista('first-secret');
        $otherTokenString = $otherToken->generate(2);

        $this->assertFalse($this->tokenista->isValid($otherTokenString));
        $this->assertTrue($this->tokenista->isTampered($otherTokenString));
    }

    public function oldSecretDataProvider()
    {
        return [
            ['retired-not-valid', [], false],
            [null, [], true],
            ['oldest-secret', [], true],
            ['older-secret', [], true],
            ['new-secret', [
                'old_secrets' => [
                    null,
                    'oldest-secret',
                    'older-secret'
                ]
            ], true],
        ];
    }

    /**
     * @dataProvider oldSecretDataProvider
     */
    public function test_it_optionally_validates_token_signed_with_old_secret_during_rotation($secretString, $option, $expectedValue)
    {
        $tokenista = new Tokenista($secretString, $option);
        $newTokenista = new Tokenista('new-secret', [
            'old_secrets' => [
                null,
                'oldest-secret',
                'older-secret'
            ]
        ]);

        $this->assertSame($expectedValue, $newTokenista->isValid($tokenista->generate(10)));
    }

    public function test_it_handles_invalid_token_format_without_error()
    {
        $this->assertFalse($this->tokenista->isValid('some random string'));
    }

    public function test_it_can_check_if_token_is_expired()
    {
        $this->assertFalse($this->tokenista->isExpired($this->tokenista->generate(2)));
        $this->assertTrue($this->tokenista->isExpired($this->tokenista->generate(0)));
    }

    public function test_it_can_check_if_token_is_tampered()
    {
        $this->assertFalse($this->tokenista->isTampered($this->tokenista->generate()));
        $this->assertTrue($this->tokenista->isTampered('some random string'));
    }

    public function test_it_validates_token_signed_with_additional_parameters()
    {
        $tokenString = $this->tokenista->generate(3600, ['email' => 'test@123.456.com']);

        $this->assertTrue($this->tokenista->isValid($tokenString, ['email' => 'test@123.456.com']));
        $this->assertFalse($this->tokenista->isTampered($tokenString, ['email' => 'test@123.456.com']));
    }

    public function test_it_does_not_validate_token_signed_with_additional_parameters_if_not_provided_to_verify()
    {
        $token = $this->tokenista->generate(3600, ['email' => 'test@123.456.com']);

        $this->assertFalse($this->tokenista->isValid($token));
        $this->assertTrue ($this->tokenista->isTampered($token));
    }

    public function test_it_does_not_validate_token_signed_with_additional_parameters_if_tampered()
    {
        $token = $this->tokenista->generate(3600, ['email' => 'test@123.456.com']);

        $this->assertFalse($this->tokenista->isValid($token, ['email' => 'bad@bad.com']));
        $this->assertTrue($this->tokenista->isTampered($token, ['email' => 'bad@bad.com']));
    }

    public function test_it_does_not_validate_token_that_had_no_additional_parameters_if_tampered()
    {
        $token = $this->tokenista->generate(3600);

        $this->assertFalse($this->tokenista->isValid($token, ['email' => 'bad@bad.com']));
        $this->assertTrue($this->tokenista->isTampered($token, ['email' => 'bad@bad.com']));
    }

    public function test_it_validates_token_with_out_of_sequence_additional_parameters()
    {
        $token = $this->tokenista->generate(
            3600,
            ['email' => 'test@123.456.com', 'stuff' => 'whatever']
        );

        $this->assertTrue($this->tokenista->isValid($token, ['stuff' => 'whatever', 'email' => 'test@123.456.com']));
        $this->assertFalse($this->tokenista->isTampered($token, ['stuff' => 'whatever', 'email' => 'test@123.456.com']));
    }

}
