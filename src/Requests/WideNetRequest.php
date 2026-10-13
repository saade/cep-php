<?php

namespace Saade\Cep\Requests;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Plugins\AcceptsJson;

use function Saade\Cep\Helpers\sanitizeCEP;

class WideNetRequest extends SoloRequest
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function __construct(protected string $cep) {}

    public function resolveEndpoint(): string
    {
        $cep = sanitizeCEP($this->cep, formatted: true);

        return "https://cdn.apicep.com/file/apicep/{$cep}.json";
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 5,
        ];
    }
}
