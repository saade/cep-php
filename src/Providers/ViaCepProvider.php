<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\ViaCepRequest;
use Saloon\Http\Response;

class ViaCepProvider extends Provider
{
    protected static string $request = ViaCepRequest::class;

    /**
     * @param  array  $data
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to ViaCep provider.');
        }

        if (! $data || $response->json('erro', false)) {
            throw new Exception('Could not parse ViaCep provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return new CepResponse(
            cep: str_replace('-', '', data_get($data, 'cep')),
            state: data_get($data, 'uf'),
            city: data_get($data, 'localidade'),
            neighborhood: data_get($data, 'bairro'),
            street: data_get($data, 'logradouro'),
            provider: 'viacep',
        );
    }
}
