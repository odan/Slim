<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Formatting\HtmlErrorFormatter;
use Slim\Formatting\JsonErrorFormatter;
use Slim\Formatting\PlainTextErrorFormatter;
use Slim\Formatting\XmlErrorFormatter;
use Slim\Handlers\ExceptionHandler;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\Traits\AppTestTrait;

final class ErrorHandlerTest extends TestCase
{
    use AppTestTrait;

    public function setUp(): void
    {
        $this->markTestSkipped();
        $this->setUpApp();
    }

    private function createMockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    public function testDetermineRenderer()
    {
        $handler = $this->container->get(ExceptionHandler::class);
        $class = new ReflectionClass(ExceptionHandler::class);

        $reflectionProperty = $class->getProperty('contentType');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, 'application/json');

        $method = $class->getMethod('determineRenderer');
        $method->setAccessible(true);

        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(JsonErrorFormatter::class, $renderer[0]);

        $reflectionProperty->setValue($handler, 'application/xml');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(XmlErrorFormatter::class, $renderer[0]);

        $reflectionProperty->setValue($handler, 'text/plain');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(PlainTextErrorFormatter::class, $renderer[0]);

        // Test the default error renderer
        $reflectionProperty->setValue($handler, 'text/unknown');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(HtmlErrorFormatter::class, $renderer[0]);
    }

    public function testDetermineStatusCode()
    {
        $request = $this->createServerRequest('GET', '/');
        $handler = $this->container->get(ExceptionHandler::class);
        $class = new ReflectionClass(ExceptionHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('exception');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, new HttpNotFoundException($request));

        $method = $class->getMethod('determineStatusCode');
        $method->setAccessible(true);

        $statusCode = $method->invoke($handler);
        $this->assertSame($statusCode, 404);

        $reflectionProperty->setValue($handler, new MockCustomException());

        $statusCode = $method->invoke($handler);
        $this->assertSame($statusCode, 500);
    }

    /**
     * Test if we can force the content type of all error handler responses.
     */
    public function testForceContentType()
    {
        $request = $this
            ->createServerRequest('GET', '/not-defined')
            ->withHeader('Accept', 'text/plain,text/xml');

        $handler = $this->container->get(ExceptionHandler::class);
        $handler->forceContentType('application/json');

        $exception = new HttpNotFoundException($request);

        /** @var ResponseInterface $response */
        $response = $handler->__invoke($request, $exception, false, false, false);

        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
    }

    public function testHalfValidContentType()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Content-Type', 'unknown/json+');

        $handler = $this->container->get(ExceptionHandler::class);
        $newErrorRenderers = [
            'application/xml' => XmlErrorFormatter::class,
            'text/xml' => XmlErrorFormatter::class,
            'text/html' => HtmlErrorFormatter::class,
        ];

        $class = new ReflectionClass(ExceptionHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $newErrorRenderers);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertNull($contentType);
    }

    public function testDetermineContentTypeTextPlainMultiAcceptHeader()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Accept', 'text/plain,text/xml');

        $handler = $this->container->get(ExceptionHandler::class);

        $errorRenderers = [
            'text/plain' => PlainTextErrorFormatter::class,
            'text/xml' => XmlErrorFormatter::class,
        ];

        $class = new ReflectionClass(ExceptionHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $errorRenderers);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertSame('text/xml', $contentType);
    }

    public function testDetermineContentTypeApplicationJsonOrXml()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Content-Type', 'text/json')
            ->withHeader('Accept', 'application/xhtml+xml');

        $handler = $this->container->get(ExceptionHandler::class);

        $errorRenderers = [
            'application/xml' => XmlErrorFormatter::class,
        ];

        $class = new ReflectionClass(ExceptionHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $errorRenderers);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertSame('application/xml', $contentType);
    }

    /**
     * Ensure that an acceptable media-type is found in the Accept header even
     * if it's not the first in the list.
     */
    public function testAcceptableMediaTypeIsNotFirstInList()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/plain,text/html');

        // provide access to the determineContentType() as it's a protected method
        $class = new ReflectionClass(ExceptionHandler::class);
        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        // use a mock object here as ErrorHandler cannot be directly instantiated
        $handler = $this->container->get(ExceptionHandler::class);

        // call determineContentType()
        $return = $method->invoke($handler, $request);

        $this->assertSame('text/html', $return);
    }

    public function testRegisterErrorRenderer()
    {
        $handler = new ExceptionHandler($this->getCallableResolver(), $this->getResponseFactory());
        $handler->registerErrorRenderer('application/slim', PlainTextErrorFormatter::class);

        $reflectionClass = new ReflectionClass(ExceptionHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $errorRenderers = $reflectionProperty->getValue($handler);

        $this->assertArrayHasKey('application/slim', $errorRenderers);
    }

    public function testSetDefaultErrorRenderer()
    {
        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
        $handler->setDefaultErrorRenderer('text/plain', PlainTextErrorFormatter::class);

        $reflectionClass = new ReflectionClass(ExceptionHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('defaultErrorRenderer');
        $reflectionProperty->setAccessible(true);
        $defaultErrorRenderer = $reflectionProperty->getValue($handler);

        $defaultErrorRendererContentTypeProperty = $reflectionClass->getProperty('defaultErrorRendererContentType');
        $defaultErrorRendererContentTypeProperty->setAccessible(true);
        $defaultErrorRendererContentType = $defaultErrorRendererContentTypeProperty->getValue($handler);

        $this->assertSame(PlainTextErrorFormatter::class, $defaultErrorRenderer);
        $this->assertSame('text/plain', $defaultErrorRendererContentType);
    }

    public function testOptions()
    {
        $request = $this->createServerRequest('/', 'OPTIONS');
        $handler = new ExceptionHandler($this->getCallableResolver(), $this->getResponseFactory());
        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['POST', 'PUT']);

        /** @var ResponseInterface $res */
        $res = $handler->__invoke($request, $exception, true, false, true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertSame('POST, PUT', $res->getHeaderLine('Allow'));
    }

    public function testWriteToErrorLog()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $logger = $this->createMockLogger();

        $handler = $this->container->get(ExceptionHandler::class);

        $logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(static function (string $error) {
                self::assertStringNotContainsString(
                    'set "displayErrorDetails" to true in the ErrorHandler constructor',
                    $error
                );
            });

        $exception = new HttpNotFoundException($request);
        $handler->__invoke($request, $exception);
    }

    public function testWriteToErrorLogShowTip()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Accept', 'application/json');

        $logger = $this->createMockLogger();

        $handler = $this->container->get(ExceptionHandler::class);

        $logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(static function (string $error) {
                self::assertStringContainsString(
                    'set "displayErrorDetails" to true in the ErrorHandler constructor',
                    $error
                );
            });

        $exception = new HttpNotFoundException($request);
        $handler->__invoke($request, $exception, false, true, true);
    }

    public function testWriteToErrorLogDoesNotShowTipIfErrorLogRendererIsNotPlainText()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $logger = $this->createMockLogger();
        $handler = $this->container->get(ExceptionHandler::class);
        $handler = new ErrorHandler(
            $this->getCallableResolver(),
            $this->getResponseFactory(),
            $logger
        );

        $handler->setLogErrorRenderer(HtmlErrorFormatter::class);

        $logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(static function (string $error) {
                self::assertStringNotContainsString(
                    'set "displayErrorDetails" to true in the ErrorHandler constructor',
                    $error
                );
            });

        $exception = new HttpNotFoundException($request);
        $handler->__invoke($request, $exception, false, true, true);
    }

    public function testDefaultErrorRenderer()
    {
        $request = $this
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/unknown');

        $handler = $this->container->get(ExceptionHandler::class);
        $exception = new RuntimeException();

        /** @var ResponseInterface $res */
        $res = $handler->__invoke($request, $exception, true, false, true);

        $this->assertTrue($res->hasHeader('Content-Type'));
        $this->assertSame('text/html', $res->getHeaderLine('Content-Type'));
    }

    public function testLogErrorRenderer()
    {
        $renderer = function () {
            return '';
        };

        $callableResolverProphecy = $this->prophesize(ContainerResolverInterface::class);
        $callableResolverProphecy
            ->resolveCallable('logErrorRenderer')
            ->willReturn($renderer)
            ->shouldBeCalledOnce();

        $handler = new ErrorHandler($callableResolverProphecy->reveal(), $this->getResponseFactory());
        $handler = $this->container->get(ExceptionHandler::class);

        $handler->setLogErrorRenderer('logErrorRenderer');

        $displayErrorDetailsProperty = new ReflectionProperty($handler, 'displayErrorDetails');
        $displayErrorDetailsProperty->setAccessible(true);
        $displayErrorDetailsProperty->setValue($handler, true);

        $exception = new RuntimeException();
        $exceptionProperty = new ReflectionProperty($handler, 'exception');
        $exceptionProperty->setAccessible(true);
        $exceptionProperty->setValue($handler, $exception);

        $writeToErrorLogMethod = new ReflectionMethod($handler, 'writeToErrorLog');
        $writeToErrorLogMethod->setAccessible(true);
        $writeToErrorLogMethod->invoke($handler);
    }
}
