<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Psr7Bridge;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Psr7Bridge\Psr7Response;
use Zend\Http\Response as ZendResponse;

class Psr7ResponseTest extends TestCase
{
    public function getResponseData()
    {
        return [
            [ 'Test!', 200, [ 'Content-Type' => [ 'text/html' ] ] ],
            [ '', 204, [] ],
            [ 'Test!', 200, [
                'Content-Type'   => [ 'text/html; charset=utf-8' ],
                'Content-Length' => [ 5 ]
            ]],
            [ 'Test!', 202, [
                'Content-Type'   => [ 'text/html; level=1', 'text/html' ],
                'Content-Length' => [ 5 ]
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
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $psr7Response);

        $zendResponse = Psr7Response::toZend($psr7Response);
        $this->assertInstanceOf('Zend\Http\Response', $zendResponse);
        $this->assertEquals($body, (string) $zendResponse->getBody());
        $this->assertEquals($status, $zendResponse->getStatusCode());
        $this->assertEquals($headers, $this->zendToPsr7Headers($zendResponse->getHeaders()));
    }

    /**
     * Transform a Zend headers into Psr7 headers
     */
    protected function zendToPsr7Headers($headers)
    {
        $zendHeaders = $headers->toArray();
        foreach ($zendHeaders as $type => $value) {
            $zendHeaders[$type] = explode(', ', $value);
        }
        return $zendHeaders;
    }

    public function getResponseString()
    {
        return [
            [ "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\n\r\nTest!" ],
            [ "HTTP/1.1 204 OK\r\n\r\n" ],
            [ "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 5\r\n\r\nTest!" ],
        ];
    }

    /**
     * @dataProvider getResponseString
     */
    public function testResponseFromZend($response)
    {
        $zendResponse = ZendResponse::fromString($response);
        $this->assertInstanceOf('Zend\Http\Response', $zendResponse);
        $psr7Response = Psr7Response::fromZend($zendResponse);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $psr7Response);
        $this->assertEquals((string) $psr7Response->getBody(), $zendResponse->getBody());
        $this->assertEquals($psr7Response->getStatusCode(), $zendResponse->getStatusCode());
        $this->assertEquals($psr7Response->getHeaders(), $this->zendToPsr7Headers($zendResponse->getHeaders()));
    }
}
