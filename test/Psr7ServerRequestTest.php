<?php
/**
 * @see       http://github.com/zendframework/zend-psr7bridge for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-psr7bridge/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Psr7Bridge;

use Error;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\UploadedFile;
use Zend\Http\Header\Cookie;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Request as ZendRequest;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Psr7Bridge\Zend\Request as BridgeRequest;
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
        $requestUri = '/foo/bar?baz=bat';
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
        $this->assertInstanceOf(Request::class, $zendRequest);
        $this->assertInstanceOf(ZendRequest::class, $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf(BridgeRequest::class, $zendRequest);

        // Assert shallow conditions
        // (content, files, and body parameters are not injected)
        $this->assertEmpty($zendRequest->getContent());
        $this->assertCount(0, $zendRequest->getFiles());
        $this->assertCount(0, $zendRequest->getPost());

        // Assert all other Request metadata
        $this->assertEquals($requestUri, $zendRequest->getRequestUri());
        $this->assertEquals($uri, $zendRequest->getUri()->toString());
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
        $this->assertInstanceOf(Cookie::class, $cookie);
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
        $requestUri = preg_replace('#^[^/:]+://[^/]+#', '', $uri);

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
        $this->assertInstanceOf(Request::class, $zendRequest);
        $this->assertInstanceOf(ZendRequest::class, $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf(BridgeRequest::class, $zendRequest);

        $this->assertEquals($requestUri, $zendRequest->getRequestUri());
        $this->assertEquals($uri, $zendRequest->getUri()->toString());
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
        $this->assertInstanceOf(Cookie::class, $cookie);
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


    public function testCanCastErroneousUploadToZendRequest()
    {
        $server = [
            'SCRIPT_NAME'     => __FILE__,
            'SCRIPT_FILENAME' => __FILE__,
        ];

        $uploadedFiles = [
            'foo' => new UploadedFile(
                __FILE__,
                0,
                UPLOAD_ERR_NO_FILE,
                '',
                ''
            ),
        ];

        $uri = 'https://example.com/foo/bar?baz=bat';
        $requestUri = preg_replace('#^[^/:]+://[^/]+#', '', $uri);

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
        $this->assertInstanceOf(Request::class, $zendRequest);
        $this->assertInstanceOf(ZendRequest::class, $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf(BridgeRequest::class, $zendRequest);

        $this->assertEquals($requestUri, $zendRequest->getRequestUri());
        $this->assertEquals($uri, $zendRequest->getUri()->toString());
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
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertTrue(isset($cookie['PHPSESSID']));
        $this->assertEquals($cookies['PHPSESSID'], $cookie['PHPSESSID']);

        $this->assertEquals(file_get_contents(__FILE__), (string) $zendRequest->getContent());

        $test = $zendRequest->getFiles();
        $this->assertCount(1, $test);
        $this->assertTrue(isset($test['foo']));
        $upload = $test->get('foo');
        $this->assertArrayHasKey('name', $upload);
        $this->assertEquals($upload['name'], '');
        $this->assertArrayHasKey('type', $upload);
        $this->assertEquals($upload['type'], '');
        $this->assertArrayHasKey('size', $upload);
        $this->assertEquals($upload['size'], 0);
        $this->assertArrayHasKey('tmp_name', $upload);
        $this->assertEquals($upload['tmp_name'], '');
        $this->assertArrayHasKey('error', $upload);
        $this->assertEquals($upload['error'], UPLOAD_ERR_NO_FILE);

        $this->assertEquals($bodyParams, $zendRequest->getPost()->getArrayCopy());

        $test = $zendRequest->getServer();
        $this->assertCount(2, $test);
        $this->assertEquals(__FILE__, $test->get('SCRIPT_NAME'));
        $this->assertEquals(__FILE__, $test->get('SCRIPT_FILENAME'));
    }

    public function testNestedFileParametersArePassedCorrectlyToZendRequest()
    {
        $uploadedFiles = [
            'foo-bar' => [
                new UploadedFile(
                    __FILE__,
                    0,
                    UPLOAD_ERR_NO_FILE,
                    '',
                    ''
                ),
                new UploadedFile(
                    __FILE__,
                    123,
                    UPLOAD_ERR_OK,
                    basename(__FILE__),
                    'plain/text'
                ),
            ]
        ];

        $psr7Request = new ServerRequest([], $uploadedFiles);

        $zendRequest = Psr7ServerRequest::toZend($psr7Request);

        // This needs to be a ZF request
        $this->assertInstanceOf(Request::class, $zendRequest);
        $this->assertInstanceOf(ZendRequest::class, $zendRequest);

        // But, more specifically, an instance where we do not use superglobals
        // to inject it
        $this->assertInstanceOf(BridgeRequest::class, $zendRequest);

        $test = $zendRequest->getFiles();
        $this->assertCount(1, $test);
        $this->assertTrue(isset($test['foo-bar']));
        $upload = $test->get('foo-bar');
        $this->assertCount(2, $upload);
        $this->assertTrue(isset($upload[0]));
        $this->assertTrue(isset($upload[1]));

        $this->assertArrayHasKey('name', $upload[0]);
        $this->assertEquals('', $upload[0]['name']);
        $this->assertArrayHasKey('type', $upload[0]);
        $this->assertEquals('', $upload[0]['type']);
        $this->assertArrayHasKey('size', $upload[0]);
        $this->assertEquals(0, $upload[0]['size']);
        $this->assertArrayHasKey('tmp_name', $upload[0]);
        $this->assertEquals('', $upload[0]['tmp_name']);
        $this->assertArrayHasKey('error', $upload[0]);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $upload[0]['error']);

        $this->assertArrayHasKey('name', $upload[1]);
        $this->assertEquals(basename(__FILE__), $upload[1]['name']);
        $this->assertArrayHasKey('type', $upload[1]);
        $this->assertEquals('plain/text', $upload[1]['type']);
        $this->assertArrayHasKey('size', $upload[1]);
        $this->assertEquals(123, $upload[1]['size']);
        $this->assertArrayHasKey('tmp_name', $upload[1]);
        $this->assertEquals(__FILE__, $upload[1]['tmp_name']);
        $this->assertArrayHasKey('error', $upload[1]);
        $this->assertEquals(UPLOAD_ERR_OK, $upload[1]['error']);
    }

    public function testCustomHttpMethodsDoNotRaiseAnExceptionDuringConversionToZendRequest()
    {
        $psr7Request = new ServerRequest([], [], null, 'CUSTOM_METHOD');

        $zendRequest = Psr7ServerRequest::toZend($psr7Request);
        $this->assertSame('CUSTOM_METHOD', $zendRequest->getMethod());
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
                            'tmp_name' => __FILE__,
                            'error' => 0,
                            'size' => 1,
                        ],
                        'test2' => [
                            'name' => 'test2.txt',
                            'type' => 'text/plain',
                            'tmp_name' => __FILE__,
                            'error' => 0,
                            'size' => 1,
                        ]
                    ]
                ], // files
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
                        'name' => 'test2.txt',
                        'type' => 'text/plain',
                        'tmp_name' => __FILE__,
                        'error' => 0,
                        'size' => 1,
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
        $this->assertInstanceOf(ServerRequest::class, $psr7Request);
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
        $this->compareUploadedFiles($files, $psr7Request->getUploadedFiles());
    }

    private function compareUploadedFiles($zend, $psr7)
    {
        if (! $psr7 instanceof UploadedFileInterface) {
            $this->assertEquals(count($zend), count($psr7), 'number of files should be same');
        }

        foreach ($zend as $name => $value) {
            if (is_array($value)) {
                $this->compareUploadedFiles($zend[$name], $psr7[$name]);
                continue;
            }

            $this->assertEquals($zend['name'], $psr7->getClientFilename());
            $this->assertEquals($zend['type'], $psr7->getClientMediaType());
            $this->assertEquals($zend['size'], $psr7->getSize());
            $this->assertEquals($zend['tmp_name'], $psr7->getStream()->getMetadata('uri'));
            $this->assertEquals($zend['error'], $psr7->getError());
            break;
        }
    }

    public function testFromZendConvertsCookies()
    {
        $request = new ZendRequest();
        $zendCookieData = ['foo' => 'test', 'bar' => 'test 2'];
        $request->getHeaders()->addHeader(new Cookie($zendCookieData));

        $psr7Request = Psr7ServerRequest::fromZend($request);

        $psr7CookieData = $psr7Request->getCookieParams();

        $this->assertEquals(count($zendCookieData), count($psr7CookieData));
        $this->assertEquals($zendCookieData['foo'], $psr7CookieData['foo']);
        $this->assertEquals($zendCookieData['bar'], $psr7CookieData['bar']);
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

    /**
     * @see https://github.com/zendframework/zend-psr7bridge/issues/27
     */
    public function testBaseUrlFromGlobal()
    {
        $_SERVER = [
            'HTTP_HOST' => 'host.com',
            'SERVER_PORT' => '80',
            'REQUEST_URI' => '/test/path/here?foo=bar',
            'SCRIPT_FILENAME' => '/c/root/test/path/here/index.php',
            'PHP_SELF' => '/test/path/here/index.php',
            'SCRIPT_NAME' => '/test/path/here/index.php',
            'QUERY_STRING' => 'foo=bar'
        ];

        $psr7 = ServerRequestFactory::fromGlobals();
        $converted = Psr7ServerRequest::toZend($psr7);
        $zendRequest = new Request();

        $this->assertSame($zendRequest->getBaseUrl(), $converted->getBaseUrl());
    }

    /**
     * @requires PHP 7
     */
    public function testPrivateConstruct()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(sprintf('Call to private %s::__construct', Psr7ServerRequest::class));
        new Psr7ServerRequest();
    }
}
