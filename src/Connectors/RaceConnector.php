<?php

namespace Saade\Cep\Connectors;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Saloon\Contracts\Sender;
use Saloon\Http\Connector;
use Saloon\Http\Senders\GuzzleSender;

class RaceConnector extends Connector
{
    /**
     * @var array<int, PromiseInterface>
     */
    protected array $transfers = [];

    public function resolveBaseUrl(): string
    {
        return '';
    }

    public function cancelTransfers(): void
    {
        foreach ($this->transfers as $transfer) {
            $transfer->cancel();
        }

        $this->transfers = [];
    }

    protected function defaultSender(): Sender
    {
        return (new GuzzleSender)->addMiddleware(
            fn (callable $handler) => fn (RequestInterface $request, array $options) => $this->transfers[] = $handler($request, $options)
        );
    }
}
