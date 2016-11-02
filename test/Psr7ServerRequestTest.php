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
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;
use Zend\Http\PhpEnvironment\Request;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Http\Request as ZendRequest;
use Zend\Stdlib\Parameters;

class Psr7ServerRequestTest extends TestCase
{
    public function testToZendWithShallowOmitsBody()
    {
        $server = [
            'SCRIPT_NAME'     => __FILE__,
            'SCRIPT_FILENAME' => __FILE__,
        ];

        $uploadedFiles = [
            'foo' => new UploadedFile(
                __FILE__,
                100,
                UPLOAD_ERR_OK,
                'foo.txt',
                'text/plain'
            ),
        ];

        $uri = 'https://example.com/foo/bar?baz=bat';

        $method = 'PATCH';

        $body = fopen(__FILE__, 'r');

        $headers = [
            'Host'         => [ 'example.com' ],
            'X-Foo'        => [ 'bar' ],
            'Content-Type' => [ 'multipart/form-data' ],
        ];

        $cookies = [
            'PHPSESSID' => uniqid(),
        ];

        $bodyParams = [
            'foo' => 'bar',
        ];

        $psr7Request = (new ServerRequest(
            $server,
            $uploadedFiles,
            $uri,
            $method,
            $body,
            $headers
        ))
            ->withCookieParams($cookies)
            ->withParsedBody($bodyParams);

        $zendRequest = Psr7ServerRequest::toZend($psr7Request, $shallow = true);

        // This needs to be a ZF2 request
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Request', $zendRequest);
        $this->assertInstanceOf('Zend\Http\Request', $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf('Zend\Psr7Bridge\Zend\Request', $zendRequest);

        // Assert shallow conditions
        // (content, files, and body parameters are not injected)
        $this->assertEmpty($zendRequest->getContent());
        $this->assertCount(0, $zendRequest->getFiles());
        $this->assertCount(0, $zendRequest->getPost());

        // Assert all other Request metadata
        $this->assertEquals($uri, $zendRequest->getRequestUri());
        $this->assertEquals($method, $zendRequest->getMethod());

        $zf2Headers = $zendRequest->getHeaders();
        $this->assertTrue($zf2Headers->has('Host'));
        $this->assertTrue($zf2Headers->has('X-Foo'));
        $this->assertTrue($zf2Headers->has('Content-Type'));
        $this->assertEquals('example.com', $zf2Headers->get('Host')->getFieldValue());
        $this->assertEquals('bar', $zf2Headers->get('X-Foo')->getFieldValue());
        $this->assertEquals('multipart/form-data', $zf2Headers->get('Content-Type')->getFieldValue());

        $this->assertTrue($zf2Headers->has('Cookie'));
        $cookie = $zf2Headers->get('Cookie');
        $this->assertInstanceOf('Zend\Http\Header\Cookie', $cookie);
        $this->assertTrue(isset($cookie['PHPSESSID']));
        $this->assertEquals($cookies['PHPSESSID'], $cookie['PHPSESSID']);

        $test = $zendRequest->getServer();
        $this->assertCount(2, $test);
        $this->assertEquals(__FILE__, $test->get('SCRIPT_NAME'));
        $this->assertEquals(__FILE__, $test->get('SCRIPT_FILENAME'));
    }

    public function testCanCastFullRequestToZend()
    {
        $server = [
            'SCRIPT_NAME'     => __FILE__,
            'SCRIPT_FILENAME' => __FILE__,
        ];

        $uploadedFiles = [
            'foo' => new UploadedFile(
                __FILE__,
                100,
                UPLOAD_ERR_OK,
                'foo.txt',
                'text/plain'
            ),
        ];

        $uri = 'https://example.com/foo/bar?baz=bat';

        $method = 'PATCH';

        $body = fopen(__FILE__, 'r');

        $headers = [
            'Host'         => [ 'example.com' ],
            'X-Foo'        => [ 'bar' ],
            'Content-Type' => [ 'multipart/form-data' ],
        ];

        $cookies = [
            'PHPSESSID' => uniqid(),
        ];

        $bodyParams = [
            'foo' => 'bar',
        ];

        $psr7Request = (new ServerRequest(
            $server,
            $uploadedFiles,
            $uri,
            $method,
            $body,
            $headers
        ))
            ->withCookieParams($cookies)
            ->withParsedBody($bodyParams);

        $zendRequest = Psr7ServerRequest::toZend($psr7Request);

        // This needs to be a ZF2 request
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Request', $zendRequest);
        $this->assertInstanceOf('Zend\Http\Request', $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf('Zend\Psr7Bridge\Zend\Request', $zendRequest);

        $this->assertEquals($uri, $zendRequest->getRequestUri());
        $this->assertEquals($method, $zendRequest->getMethod());

        $zf2Headers = $zendRequest->getHeaders();
        $this->assertTrue($zf2Headers->has('Host'));
        $this->assertTrue($zf2Headers->has('X-Foo'));
        $this->assertTrue($zf2Headers->has('Content-Type'));
        $this->assertEquals('example.com', $zf2Headers->get('Host')->getFieldValue());
        $this->assertEquals('bar', $zf2Headers->get('X-Foo')->getFieldValue());
        $this->assertEquals('multipart/form-data', $zf2Headers->get('Content-Type')->getFieldValue());

        $this->assertTrue($zf2Headers->has('Cookie'));
        $cookie = $zf2Headers->get('Cookie');
        $this->assertInstanceOf('Zend\Http\Header\Cookie', $cookie);
        $this->assertTrue(isset($cookie['PHPSESSID']));
        $this->assertEquals($cookies['PHPSESSID'], $cookie['PHPSESSID']);

        $this->assertEquals(file_get_contents(__FILE__), (string) $zendRequest->getContent());

        $test = $zendRequest->getFiles();
        $this->assertCount(1, $test);
        $this->assertTrue(isset($test['foo']));
        $upload = $test->get('foo');
        $this->assertArrayHasKey('name', $upload);
        $this->assertArrayHasKey('type', $upload);
        $this->assertArrayHasKey('size', $upload);
        $this->assertArrayHasKey('tmp_name', $upload);
        $this->assertArrayHasKey('error', $upload);

        $this->assertEquals($bodyParams, $zendRequest->getPost()->getArrayCopy());

        $test = $zendRequest->getServer();
        $this->assertCount(2, $test);
        $this->assertEquals(__FILE__, $test->get('SCRIPT_NAME'));
        $this->assertEquals(__FILE__, $test->get('SCRIPT_FILENAME'));
    }

    public function testNestedFileParametersArePassedCorrectlyToZendRequest()
    {
        $this->markTestIncomplete('Functionality is written but untested');
    }

    public function testCustomHttpMethodsDoNotRaiseAnExceptionDuringConversionToZendRequest()
    {
        $this->markTestIncomplete('Functionality is written but untested');
    }

    public function getResponseData()
    {
        return [
            [
                'http://framework.zend.com/', // uri
                'GET', // http method
                [ 'Content-Type' => 'text/html' ], // headers
                '<html></html>', // body
                [ 'foo' => 'bar' ], // query params
                [], // post
                [], // files
            ],
            [
                'http://framework.zend.com/', // uri
                'POST', // http method
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cookie' => sprintf("PHPSESSID=%s;foo=bar", uniqid())
                ], // headers
                '', // body
                [ 'foo' => 'bar' ], // query params
                [ 'baz' => 'bar' ], // post
                [], // files
            ],
            [
                'http://framework.zend.com/', // uri
                'POST', // http method
                [ 'Content-Type' => 'multipart/form-data' ], // headers
                file_get_contents(__FILE__), // body
                [ 'foo' => 'bar' ], // query params
                [], // post
                [
                    'file' => [
                        'test1' => [
                            'name' => 'test1.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/tmp/phpXXX',
                            'error' => 0,
                            'size' => 1,
                        ],
                        'test2' => [
                            'name' => 'test2.txt',
                            'type' => 'text/plain',
                            'tmp_name' => '/tmp/phpYYY',
                            'error' => 0,
                            'size' => 1,
                        ]
                    ]
                ], // files
            ]
        ];
    }

    /**
     * @dataProvider getResponseData
     */
    public function testFromZend($uri, $method, $headers, $body, $query, $post, $files)
    {
        $zendRequest = new ZendRequest();
        $zendRequest->setUri($uri);
        $zendRequest->setMethod($method);
        $zendRequest->getHeaders()->addHeaders($headers);
        $zendRequest->setContent($body);
        $zendRequest->getQuery()->fromArray($query);
        $zendRequest->getPost()->fromArray($post);
        $zendRequest->getFiles()->fromArray($files);

        $psr7Request = Psr7ServerRequest::fromZend($zendRequest);
        $this->assertInstanceOf('Zend\Diactoros\ServerRequest', $psr7Request);
        // URI
        $this->assertEquals($uri, (string) $psr7Request->getUri());
        // HTTP method
        $this->assertEquals($method, $psr7Request->getMethod());
        // headers
        $psr7Headers = $psr7Request->getHeaders();
        foreach ($headers as $key => $value) {
            $this->assertContains($value, $psr7Headers[$key]);
        }
        // body
        $this->assertEquals($body, (string) $psr7Request->getBody());
        // query params
        $this->assertEquals($query, $psr7Request->getQueryParams());
        // post
        $this->assertEquals($post, $psr7Request->getParsedBody());
        // files
        foreach ($psr7Request->getUploadedFiles() as $name => $upload) {
            $this->assertEquals($files['file'][$name]['name'], $upload->getClientFilename());
            $this->assertEquals($files['file'][$name]['type'], $upload->getClientMediaType());
            $this->assertEquals($files['file'][$name]['size'], $upload->getSize());
            $this->assertEquals($files['file'][$name]['tmp_name'], $upload->getStream());
            $this->assertEquals($files['file'][$name]['error'], $upload->getError());
        }
    }

    public function testServerParams()
    {
        $zendRequest = new Request();
        $zendRequest->setServer(new Parameters(['REMOTE_ADDR' => '127.0.0.1']));

        $psr7Request = Psr7ServerRequest::fromZend($zendRequest);

        $params = $psr7Request->getServerParams();
        $this->assertArrayHasKey('REMOTE_ADDR', $params);
        $this->assertSame('127.0.0.1', $params['REMOTE_ADDR']);
    }
}
