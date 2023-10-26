<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\CorreiosRequest;
use Saloon\Http\Response;
use SimpleXMLElement;

class CorreiosProvider extends Provider
{
    protected static string $request = CorreiosRequest::class;

    protected static function processResponse(Response $response): mixed
    {
        $xml = simplexml_load_string(iconv('ISO-8859-1', 'UTF-8', $response->body()));

        return $xml->xpath('//return')[0] ?? null;
    }

    /**
     * @param  SimpleXMLElement  $data
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if (! $data) {
            throw new Exception('Could not parse Correios provider response.');
        }
    }

    /**
     * @param  SimpleXMLElement  $data
     */
    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return new CepResponse(
            cep: (string) data_get($data, 'cep'),
            state: (string) data_get($data, 'uf'),
            city: (string) data_get($data, 'cidade'),
            neighborhood: (string) data_get($data, 'bairro'),
            street: (string) data_get($data, 'end'),
            provider: 'correios',
        );
    }
}
