<?php
/**
 * @see       http://github.com/zendframework/zend-psr7bridge for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-psr7bridge/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Psr7Bridge\Zend;

use PHPUnit\Framework\TestCase as TestCase;
use Zend\Psr7Bridge\Zend\Request;
use Zend\Uri\Http as Uri;

class RequestTest extends TestCase
{
    public function testConstructor()
    {
        $method  = 'GET';
        $path    = '/foo';
        $request = new Request($method, $path, [], [], [], [], [], []);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame($method, $request->getMethod());
        $this->assertSame($path, $request->getRequestUri());
        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertSame($path, $request->getUri()->getPath());
        $this->assertEmpty($request->getHeaders());
        $this->assertEmpty($request->getCookie());
        $this->assertEmpty($request->getQuery());
        $this->assertEmpty($request->getPost());
        $this->assertEmpty($request->getFiles());
    }
}
