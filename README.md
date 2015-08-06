# zend-psr7bridge

[![Build Status](https://secure.travis-ci.org/zendframework/zend-psr7bridge.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-psr7bridge)

Code for converting [PSR-7](http://www.php-fig.org/psr/psr-7/) messages to
[zend-http](https://github.com/zendframework/zend-http) messages, and vice
versa.

**Note: This project is a work in progress.**

Initial functionality is only covering conversion of non-body request data from
PSR-7 to zend-http in order to facilitate routing in
[zend-expressive](https://github.com/zendframework/zend-expressive); we plan to
expand this once initial work on zend-expressive is complete.

## Installation

Install this library using composer:

```console
$ composer require zendframework/zend-psr7bridge
```

## Documentation

Documentation is [in the doc tree](doc/), and can be compiled using [bookdown](http://bookdown.io):

```console
$ bookdown doc/bookdown.json
$ php -S 0.0.0.0:8080 -t doc/html/ # then browse to http://localhost:8080/
```

> ### Bookdown
>
> You can install bookdown globally using `composer global require bookdown/bookdown`. If you do
> this, make sure that `$HOME/.composer/vendor/bin` is on your `$PATH`.
