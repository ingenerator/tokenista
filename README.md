# Tokenista - generate and validate signed tokens

- [![Master Build Status](https://travis-ci.org/ingenerator/tokenista.png?branch=master)](https://travis-ci.org/ingenerator/tokenista)

Tokenista is a lightweight library for generating and validating signed tokens that can be used for password reset links,
authentication, CSRF or anything else you may require. It aims to be secure (though you should always review all
security related code) and to have minimum external dependencies.

## Installation

Add tokenista to your composer.json and run `composer update` to install it.

```json
{
  "require": { "ingenerator/tokenista": "0.1.*@dev" }
}
```

## Basic Usage

```php
$secret = 'some-constant-secret-value';
$tokenista = new \Ingenerator\Tokenista($secret, array('lifetime' => 3600));

// Generate with default lifetime from constructor options
$token = $tokenista->generate();

// Overall check if token is valid
if ($tokenista->isValid($token)) {
  // Do whatever
}

// Or for more control use:
$tokenista->isExpired($token);
$tokenista->isTampered($token);
```

Tokenista generates tokens as a single string of the form {random}-{expirytime}-{signature}, base64 encoded so suitable
for inclusion in most places.

## Testing and developing

tokenista has a full suite of [PhpSpec](http://phpspec.net) specifications - run them with `bin/phpspec run`.
Contributions will only be accepted if they are accompanied by well structured specs. Installing with composer should
get you everything you need to work on the project.

## License

tokenista is copyright 2014 inGenerator Ltd and released under the [BSD license](LICENSE).
