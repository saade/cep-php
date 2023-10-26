<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\CorreiosAltRequest;
use Saloon\Http\Response;

class CorreiosAltProvider extends Provider
{
    protected static string $request = CorreiosAltRequest::class;

    /**
     * @param  array  $data
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if (! data_get($data, 'erro', false)) {
            throw new Exception('Could not parse Correios-alt provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return new CepResponse(
            cep: data_get($data, 'dados.0.cep'),
            state: data_get($data, 'dados.0.uf'),
            city: data_get($data, 'dados.0.localidade'),
            neighborhood: data_get($data, 'dados.0.bairro'),
            street: data_get($data, 'dados.0.logradouroDNEC'),
            provider: 'correios-alt',
        );
    }
}
