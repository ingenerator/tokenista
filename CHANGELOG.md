# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 1.6.0 (2022-10-16)

* Support PHP 8.1 and PHP 8.2

## 1.5.0 (2021-04-19)

* Support PHP8

## 1.4.0 (2020-10-29)

* Support php7.4

## 1.3.0 (2019-12-10)

* [DEPRECATION] Deprecate the `isExpired`, `isValid` and `isTampered` methods on 
  Tokenista itself in favour of using the new `validate` method to get a more fully
  -formed result.
* Introduce new Tokenista::validate method and a validation result object to simplify
  flows where the handling of an invalid token is the same but you want to e.g. log an
  expired token differently to a tampered one.

## 1.2.0 (2019-04-03)

* Drop support for php5 and test on php7

## 1.1.0 (2018-02-26)

* Add support for rotating secrets with an `old_secrets` config option
* Drop support for PHP <=5.5

## 1.0.2 / 2017-03-02

* [BUGFIX] Fix composer autoloader for development classes so it doesn't conflict
  with root project dependencies
  
## 1.0.1 / 2015-05-11

* Extract methods for easier stubbing and mocking

## 1.0.0 / 2015-05-11

* [FEATURE] First release - all the features!
