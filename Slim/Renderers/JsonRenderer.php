<?php

namespace Slim\Renderers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Constants\MediaType;

/**
 * A utility class for rendering JSON responses.
 * It also sets the appropriate `Content-Type` header for JSON responses.
 *
 * Example usage:
 *
 * ```php
 * $renderer = new \Slim\Renderers\JsonRenderer($streamFactory);
 * $response = $renderer->json($response, ['key' => 'value']);
 * ```
 */
final class JsonRenderer
{
    private StreamFactoryInterface $streamFactory;

    private int $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;

    private string $contentType = MediaType::APPLICATION_JSON;

    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function json(ResponseInterface $response, mixed $data = null): ResponseInterface
    {
        $response = $response->withHeader('Content-Type', $this->contentType);
        $exceptionJson = (string)json_encode($data, $this->jsonOptions);

        return $response->withBody($this->streamFactory->createStream($exceptionJson));
    }

    /**
     * Change the content type of the response
     */
    public function setContentType(string $type): self
    {
        $this->contentType = $type;

        return $this;
    }

    /**
     * Set options for JSON encoding
     *
     * @see https://php.net/manual/function.json-encode.php
     * @see https://php.net/manual/json.constants.php
     */
    public function setJsonOptions(int $options): self
    {
        $this->jsonOptions = $options;

        return $this;
    }
}
