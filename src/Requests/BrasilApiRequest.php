<?php

namespace Saade\Cep\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;

class BrasilApiRequest extends SoloRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::GET;

    public function __construct(protected string $cep)
    {
    }

    public function resolveEndpoint(): string
    {
        return "https://brasilapi.com.br/api/cep/v2/{$this->cep}";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
