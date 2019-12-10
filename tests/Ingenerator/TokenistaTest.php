<?php

namespace Ingenerator\Tests;

use Ingenerator\Tokenista;
use Ingenerator\TokenistaValidationResult;
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
        $tokenString[\rand(0, \strlen($tokenString) - 1)] = '~';

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
        $this->assertFalse(
            $this->tokenista->isTampered($token, ['stuff' => 'whatever', 'email' => 'test@123.456.com'])
        );
    }

    public function provider_validate()
    {
        return [
            [
                ['ttl' => 3600, 'params' => []],
                ['params' => []],
                [
                    'is_expired'  => FALSE,
                    'is_tampered' => FALSE,
                    'is_valid'    => TRUE,
                    'status'      => TokenistaValidationResult::VALID,
                ],
            ],
            [
                ['ttl' => -3600, 'params' => []],
                ['params' => []],
                [
                    'is_expired'  => TRUE,
                    'is_tampered' => FALSE,
                    'is_valid'    => FALSE,
                    'status'      => TokenistaValidationResult::INVALID_EXPIRED,
                ],
            ],
            [
                ['ttl' => 3600, 'params' => ['foobar']],
                ['params' => ['foobar']],
                [
                    'is_expired'  => FALSE,
                    'is_tampered' => FALSE,
                    'is_valid'    => TRUE,
                    'status'      => TokenistaValidationResult::VALID,
                ],
            ],
            [
                ['ttl' => 3600, 'params' => ['foobar']],
                ['params' => ['you changed me']],
                [
                    'is_expired'  => FALSE,
                    'is_tampered' => TRUE,
                    'is_valid'    => FALSE,
                    'status'      => TokenistaValidationResult::INVALID_TAMPERED,
                ],
            ],
            [
                ['ttl' => -3600, 'params' => ['foobar']],
                ['params' => ['you changed me']],
                [
                    'is_expired'  => TRUE,
                    'is_tampered' => TRUE,
                    'is_valid'    => FALSE,
                    'status'      => TokenistaValidationResult::INVALID_TAMPERED.','.TokenistaValidationResult::INVALID_EXPIRED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider_validate
     */
    public function test_its_validate_method_returns_validation_result_with_expected_state(
        $token_params,
        $validate_params,
        $expect_status
    ) {
        $token  = $this->tokenista->generate($token_params['ttl'], $token_params['params']);
        $result = $this->tokenista->validate($token, $validate_params['params']);
        $this->assertSame(
            $expect_status,
            [
                'is_expired'  => $result->isExpired(),
                'is_tampered' => $result->isTampered(),
                'is_valid'    => $result->isValid(),
                'status'      => $result->getStatusCodes(),
            ]
        );
    }

    /**
     * @testWith [-1800]
     *           [1800]
     */
    public function test_its_validation_result_includes_token_expiry_time($ttl)
    {
        $token  = $this->tokenista->generate($ttl);
        $result = $this->tokenista->validate($token);
        $this->assertEqualsWithDelta(time() + $ttl, $result->getTokenExpiry()->getTimestamp(), 1);
    }

}
