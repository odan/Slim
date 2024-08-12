<?php

namespace Slim\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Formatting\ContentNegotiationResult;

/**
 * This handler determines the response based on the media type (mime)
 * specified in the HTTP request `Accept` header.
 */
interface ContentNegotiatorInterface
{
    public function negotiate(ServerRequestInterface $request): ContentNegotiationResult;
}
