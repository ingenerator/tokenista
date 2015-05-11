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
	 * @param int   $lifetime     for expiry, otherwise default will be used. Class default is 1 hour if not configured
	 * @param array $extra_values extra values that should be signed with the token - the token will not be valid
	 *                            unless the same values are presented when the token needs to be verified.
	 *
	 * @return string token in the format vufQO8H+9pwnb5hz-1394614391-11e7158dbde10057e1488cb7f64f7e4534b457ff
	 */
	public function generate($lifetime = NULL, $extra_values = array())
	{
		if ($lifetime === NULL) {
			$lifetime = $this->options['lifetime'];
		}

		$expires = $this->calculateExpiry($lifetime);
		$token   = $this->makeToken();

		return $token.'-'.$expires.'-'.$this->signToken($token, $expires, $extra_values);
	}
	
	/**
	 * @return int
	 */
	protected function calculateExpiry($lifetime)
	{
		return time() + $lifetime;
	}
	
	/**
	 * @return string
	 */
	protected function makeToken()
	{
		return base64_encode(openssl_random_pseudo_bytes(12, $strong));
	}

	/**
	 * @param string $token
	 * @param int    $expires
	 * @param array  $extra_values
	 *
	 * @return string the signature
	 */
	protected function signToken($token, $expires, array $extra_values)
	{
		$sign_string = $token.'-'.$expires;
		if ($extra_values) {
			ksort($extra_values);
			$sign_string .= ':'.json_encode($extra_values);
		}

		return hash_hmac('sha1', $sign_string, $this->secret);
	}

	/**
	 * @param string $token_string
	 *
	 * @param array  $extra_values
	 *
	 * @return bool
	 */
	public function isValid($token_string, array $extra_values = array())
	{
		return ! ($this->isTampered($token_string, $extra_values) OR $this->isExpired($token_string));
	}

	/**
	 * @param string $token_string
	 *
	 * @param array  $extra_values
	 *
	 * @return bool
	 */
	public function isTampered($token_string, array $extra_values = array())
	{
		$parts = $this->parseToken($token_string);

		return ($parts['signature'] !== $this->signToken($parts['token'], $parts['expires'], $extra_values));
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
