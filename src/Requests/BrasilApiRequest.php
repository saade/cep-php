<?php

namespace Saade\Cep\Requests;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Plugins\AcceptsJson;

class BrasilApiRequest extends SoloRequest
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(protected string $cep) {}

    public function resolveEndpoint(): string
    {
        return "https://brasilapi.com.br/api/cep/v1/{$this->cep}";
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 5,
        ];
    }
}
