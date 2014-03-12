<?php
/**
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2014, inGenerator Ltd
 * @licence    BSD
 */

namespace Ingenerator;

/**
 * Generates and validates tokens
 *
 * @package Ingenerator
 * @see     spec\Ingenerator\TokenistaSpec
 */
class Tokenista
{

	/**
	 * @var string
	 */
	protected $secret;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var array
	 */
	protected static $default_options = array(
		'lifetime' => 3600
	);

	/**
	 * @param string $secret  for signing with
	 * @param array  $options see Tokenista::$default_options for available options
	 */
	public function __construct($secret, $options = array())
	{
		$this->secret = $secret;
		$this->options = array_merge(static::$default_options, $options);
	}

	/**
	 * @param int $lifetime for expiry, otherwise default will be used. Class default is 1 hour if not configured
	 *
	 * @return string token in the format vufQO8H+9pwnb5hz-1394614391-11e7158dbde10057e1488cb7f64f7e4534b457ff
	 */
	public function generate($lifetime = NULL)
	{
		if ($lifetime === NULL) {
			$lifetime = $this->options['lifetime'];
		}

		$expires = time() + $lifetime;
		$token = base64_encode(openssl_random_pseudo_bytes(12, $strong));

		return $token.'-'.$expires.'-'.$this->signToken($token, $expires);
	}

	/**
	 * @param string $token
	 * @param int    $expires
	 *
	 * @return string the signature
	 */
	protected function signToken($token, $expires)
	{
		return hash_hmac('sha1', $token.'-'.$expires, $this->secret);
	}

	/**
	 * @param string $token_string
	 *
	 * @return bool
	 */
	public function isValid($token_string)
	{
		return ! ($this->isTampered($token_string) OR $this->isExpired($token_string));
	}

	/**
	 * @param string $token_string
	 *
	 * @return bool
	 */
	public function isTampered($token_string)
	{
		$parts = $this->parseToken($token_string);

		return ($parts['signature'] !== $this->signToken($parts['token'], $parts['expires']));
	}

	/**
	 * @param string $token_string
	 *
	 * @return array
	 */
	protected function parseToken($token_string)
	{
		$parts = explode('-', $token_string);
		if (count($parts) !== 3) {
			$parts = array('', '', 'invalid');
		}

		return array_combine(array('token', 'expires', 'signature'), $parts);
	}

	/**
	 * @param string $token_string
	 *
	 * @return bool
	 */
	public function isExpired($token_string)
	{
		$parts = $this->parseToken($token_string);

		return (time() >= $parts['expires']);
	}
}
