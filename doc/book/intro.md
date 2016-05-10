# zend-psr7bridge

Code for converting [PSR-7](http://www.php-fig.org/psr/psr-7/) messages to
[zend-http](https://github.com/zendframework/zend-http) messages, and vice
versa.

**Note: This project is a work in progress.**

Initial functionality is only covering conversion of non-body request data from
PSR-7 to zend-http in order to facilitate routing in
[zend-expressive](https://github.com/zendframework/zend-expressive); we plan to
expand this once initial work on zend-expressive is complete.
