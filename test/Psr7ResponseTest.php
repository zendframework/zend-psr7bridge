<?php
/**
 * @see       http://github.com/zendframework/zend-psr7bridge for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-psr7bridge/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Psr7Bridge;

use Error;
use Iterator;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Http\Header\SetCookie;
use Zend\Http\Response as ZendResponse;
use Zend\Psr7Bridge\Psr7Response;

class Psr7ResponseTest extends TestCase
{
    public function getResponseData()
    {
        return [
            [ 'Test!', 200, [ 'Content-Type' => [ 'text/html' ] ] ],
            [ '', 204, [] ],
            [ 'Test!', 200, [
                'Content-Type'   => [ 'text/html; charset=utf-8' ],
                'Content-Length' => [ '5' ]
            ]],
            [ 'Test!', 202, [
                'Content-Type'   => [ 'text/html; level=1', 'text/html' ],
                'Content-Length' => [ '5' ]
            ]],
        ];
    }

    /**
     * @dataProvider getResponseData
     */
    public function testResponseToZend($body, $status, $headers)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($body);

        $psr7Response = new Response($stream, $status, $headers);
        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);

        $zendResponse = Psr7Response::toZend($psr7Response);
        $this->assertInstanceOf(ZendResponse::class, $zendResponse);
        $this->assertEquals($body, (string)$zendResponse->getBody());
        $this->assertEquals($status, $zendResponse->getStatusCode());

        $zendHeaders = $zendResponse->getHeaders()->toArray();
        foreach ($headers as $type => $values) {
            foreach ($values as $value) {
                $this->assertContains($value, $zendHeaders[$type]);
            }
        }
    }

    /**
     * @dataProvider getResponseData
     */
    public function testResponseToZendWithMemoryStream($body, $status, $headers)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($body);

        $psr7Response = new Response($stream, $status, $headers);
        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);

        $zendResponse = Psr7Response::toZend($psr7Response);
        $this->assertInstanceOf(ZendResponse::class, $zendResponse);
        $this->assertEquals($body, (string)$zendResponse->getBody());
        $this->assertEquals($status, $zendResponse->getStatusCode());

        $zendHeaders = $zendResponse->getHeaders()->toArray();
        foreach ($headers as $type => $values) {
            foreach ($values as $value) {
                $this->assertContains($value, $zendHeaders[$type]);
            }
        }
    }

    /**
     * @dataProvider getResponseData
     */
    public function testResponseToZendFromRealStream($body, $status, $headers)
    {
        $stream = new Stream(tempnam(sys_get_temp_dir(), 'Test'), 'wb+');
        $stream->write($body);

        $psr7Response = new Response($stream, $status, $headers);
        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);

        $zendResponse = Psr7Response::toZend($psr7Response);
        $this->assertInstanceOf(ZendResponse::class, $zendResponse);
        $this->assertEquals($body, (string)$zendResponse->getBody());
        $this->assertEquals($status, $zendResponse->getStatusCode());

        $zendHeaders = $zendResponse->getHeaders()->toArray();
        foreach ($headers as $type => $values) {
            foreach ($values as $value) {
                $this->assertContains($value, $zendHeaders[$type]);
            }
        }
    }

    public function getResponseString()
    {
        return [
            [ "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\n\r\nTest!" ],
            [ "HTTP/1.1 204 OK\r\n\r\n" ],
            [ "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 5\r\n\r\nTest!" ],
            [ "HTTP/1.1 200 OK\r\nContent-Type: text/html, text/xml\r\nContent-Length: 5\r\n\r\nTest!" ],
        ];
    }

    /**
     * @dataProvider getResponseString
     */
    public function testResponseFromZend($response)
    {
        $zendResponse = ZendResponse::fromString($response);
        $this->assertInstanceOf(ZendResponse::class, $zendResponse);
        $psr7Response = Psr7Response::fromZend($zendResponse);
        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);
        $this->assertEquals((string)$psr7Response->getBody(), $zendResponse->getBody());
        $this->assertEquals($psr7Response->getStatusCode(), $zendResponse->getStatusCode());

        $zendHeaders = $zendResponse->getHeaders()->toArray();
        foreach ($psr7Response->getHeaders() as $type => $values) {
            foreach ($values as $value) {
                $this->assertContains($value, $zendHeaders[$type]);
            }
        }
    }

    /**
     * @requires PHP 7
     */
    public function testPrivateConstruct()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(sprintf('Call to private %s::__construct', Psr7Response::class));
        new Psr7Response();
    }

    public function testConvertedHeadersAreInstanceOfTheirAppropriateClasses()
    {
        $psr7Response = (new Response(tmpfile()))->withAddedHeader('Set-Cookie', 'foo=bar;domain=.zendframework.com');
        $zendResponse = Psr7Response::toZend($psr7Response);

        $cookies = $zendResponse->getHeaders()->get('Set-Cookie');
        $this->assertInstanceOf(Iterator::class, $cookies);
        $this->assertCount(1, $cookies);
        /** @var SetCookie $cookie */
        $cookie = $cookies[0];
        $this->assertInstanceOf(SetCookie::class, $cookie);
        $this->assertSame('.zendframework.com', $cookie->getDomain());
    }
}
