<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Psr7Bridge;

use Psr\Http\Message\ServerRequestInterface;

final class Psr7ServerRequest
{
    /**
     * Convert a PSR-7 ServerRequest to a Zend\Http server-side request.
     *
     * @param ServerRequestInterface $psr7Request
     * @param bool $shallow Whether or not to convert without body/file
     *     parameters; defaults to false, meaning a fully populated request
     *     is returned.
     * @return Zend\Request
     */
    public static function toZend(ServerRequestInterface $psr7Request, $shallow = false)
    {
        if ($shallow) {
            return new Zend\Request(
                $psr7Request->getMethod(),
                $psr7Request->getUri(),
                $psr7Request->getHeaders(),
                $psr7Request->getCookieParams(),
                $psr7Request->getQueryParams(),
                [],
                [],
                $psr7Request->getServerParams()
            );
        }

        $zendRequest = new Zend\Request(
            $psr7Request->getMethod(),
            $psr7Request->getUri(),
            $psr7Request->getHeaders(),
            $psr7Request->getCookieParams(),
            $psr7Request->getQueryParams(),
            $psr7Request->getParsedBody() ?: [],
            self::convertUploadedFiles($psr7Request->getUploadedFiles()),
            $psr7Request->getServerParams()
        );
        $zendRequest->setContent($psr7Request->getBody());

        return $zendRequest;
    }

    /**
     * Convert a PSR-7 uploaded files structure to a $_FILES structure
     *
     * @param \Psr\Http\Message\UploadedFileInterface[]
     * @return array
     */
    private static function convertUploadedFiles(array $uploadedFiles)
    {
        $files = [];
        foreach ($uploadedFiles as $name => $upload) {
            if (is_array($upload)) {
                $files[$name] = self::convertUploadedFiles($upload);
                continue;
            }

            $files[$name] = [
                'name'     => $upload->getClientFilename(),
                'type'     => $upload->getClientMediaType(),
                'size'     => $upload->getSize(),
                'tmp_name' => $upload->getStream(),
                'error'    => $upload->getError(),
            ];
        }
        return $files;
    }

    /**
     * Do not allow instantiation.
     */
    private function __construct()
    {
    }
}
