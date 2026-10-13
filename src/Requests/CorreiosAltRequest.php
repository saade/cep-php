<?php

namespace Saade\Cep\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CorreiosAltRequest extends SoloRequest implements HasBody
{
    use AcceptsJson;
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $cep)
    {
    }

    protected function defaultBody(): array
    {
        return [
            'endereco' => $this->cep,
            'tipoCEP' => 'ALL',
        ];
    }

    public function resolveEndpoint(): string
    {
        return 'https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Referer' => 'https://buscacepinter.correios.com.br/app/endereco/index.php',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 5,
        ];
    }
}
