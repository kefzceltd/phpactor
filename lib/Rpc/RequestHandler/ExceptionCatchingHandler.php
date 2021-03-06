<?php

namespace Phpactor\Rpc\RequestHandler;

use Phpactor\Rpc\RequestHandler;
use Phpactor\Rpc\Response\ErrorResponse;
use Phpactor\Rpc\Request;
use Phpactor\Rpc\Response;

class ExceptionCatchingHandler implements RequestHandler
{
    /**
     * @var RequestHandler
     */
    private $innerHandler;

    public function __construct(RequestHandler $innerHandler)
    {
        $this->innerHandler = $innerHandler;
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->innerHandler->handle($request);
        } catch (\Exception $exception) {
            return ErrorResponse::fromException($exception);
        }
    }
}
