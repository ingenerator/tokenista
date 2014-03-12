<?php
/**
 * Defines TokenistaSpec - specifications for Ingenerator\Tokenista
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2014, inGenerator Ltd
 * @licence    proprietary
 */

namespace spec\Ingenerator;

use Ingenerator\Tokenista;
use Prophecy\Argument;
use spec\ObjectBehavior;

/**
 *
 * @see Ingenerator\Tokenista
 */
class TokenistaSpec extends ObjectBehavior
{
	const SECRET = 'our-secret';

	/**
	 * Use $this->subject to get proper type hinting for the subject class
	 *
	 * @var \Ingenerator\Tokenista
	 */
	protected $subject;

	function let()
	{
		$this->beConstructedWith(self::SECRET);
	}

	function it_is_initializable()
	{
		$this->subject->shouldHaveType('Ingenerator\Tokenista');
	}

	function it_generates_token_strings_in_expected_format()
	{
		$token = $this->subject->generate();
		$token->shouldBeString();
		$token->shouldMatch('_^[A-Za-z0-9+/]{16}-[0-9]+-[A-Za-z0-9+/]{40}$_');
	}

	function it_generates_new_random_tokens_each_time()
	{
		$token1 = $this->subject->generate();
		$this->subject->generate()->shouldNotBe($token1);
	}

	function it_generates_tokens_with_default_expiry_if_no_expiry_passed()
	{
		$this->beConstructedWith(self::SECRET, array('lifetime' => 2));
		$token = $this->subject->generate();
		$this->subject->isExpired($token)->shouldBe(FALSE);
	}

	function it_validates_token_within_expiry_time()
	{
		$token = $this->subject->generate(2);
		$this->subject->isValid($token)->shouldBe(TRUE);
	}

	function it_does_not_validate_token_after_expiry_time()
	{
		$token = $this->subject->generate(0);
		$this->subject->isValid($token)->shouldBe(FALSE);
	}

	function it_does_not_validate_tampered_token()
	{
		$token = $this->subject->generate(3600)->getWrappedObject();
		$token[rand(0, strlen($token))] = '~';
		$this->subject->isValid($token)->shouldBe(FALSE);
	}

	function it_does_not_validate_token_signed_with_different_secret()
	{
		$other = new Tokenista('first-secret');
		$other_token = $other->generate(2);

		$this->subject->isValid($other_token)->shouldBe(FALSE);
		$this->subject->isTampered($other_token)->shouldBe(TRUE);
	}

	function it_handles_invalid_token_format_without_error()
	{
		$this->subject->isValid('some random string')->shouldBe(FALSE);
	}

	function it_can_check_if_token_is_expired()
	{
		$this->subject->isExpired($this->subject->generate(2))->shouldBe(FALSE);
		$this->subject->isExpired($this->subject->generate(0))->shouldBe(TRUE);
	}

	function it_can_check_if_token_is_tampered()
	{
		$this->subject->isTampered($this->subject->generate())->shouldBe(FALSE);
		$this->subject->isTampered('some random string')->shouldBe(TRUE);
	}



}
