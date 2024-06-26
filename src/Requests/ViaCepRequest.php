<?php

namespace Saade\Cep\Requests;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Plugins\AcceptsJson;

class ViaCepRequest extends SoloRequest
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(protected string $cep)
    {
    }

    public function resolveEndpoint(): string
    {
        return "https://viacep.com.br/ws/{$this->cep}/json/";
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
