<?php

namespace Vx\Sendifico\Api;

use RuntimeException;

final class SendificoApiException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        private readonly ?string $remoteMessageCode = null,
        private readonly array $responsePayload = []
    ) {
        parent::__construct($message, $code);
    }

    public function getRemoteMessageCode(): ?string
    {
        return $this->remoteMessageCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponsePayload(): array
    {
        return $this->responsePayload;
    }
}
