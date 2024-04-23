<?php

namespace Saade\Cep\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CorreiosAltRequest extends SoloRequest implements HasBody
{
    use HasFormBody;
    use AcceptsJson;

    protected Method $method = Method::POST;

    public function __construct(protected string $cep)
    {
    }

    protected function defaultBody(): array
    {
        return [
            'endereco' => $this->cep,
            'tipoCEP' => 'LOG',
        ];
    }

    public function resolveEndpoint(): string
    {
        return 'https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Origin' => 'https://buscacepinter.correios.com.br',
            'Referer' => 'https://buscacepinter.correios.com.br/app/endereco/index.php',
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
