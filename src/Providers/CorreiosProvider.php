<?php

namespace Saade\Cep\Providers;

use Exception;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\CorreiosRequest;
use Saloon\Http\Response;

class CorreiosProvider extends Provider
{
    protected static string $request = CorreiosRequest::class;
    
    /**     
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {        
        if (! $response->ok()) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if (! $response->xml()) {
            throw new Exception('Could not parse Correios provider response.');
        }
    }
    
    protected function toDTO(Response $response): CepResponse
    {
        $xml = simplexml_load_string(iconv('ISO-8859-1', 'UTF-8', $response->body()));
        $data = $xml->xpath('//return')[0] ?? null;

        return new CepResponse(
            provider: 'correios',
            cep: (string) $data['cep'] ?? null,
            street: (string) $data['end'] ?? null,
            neighborhood: (string) $data['bairro'] ?? null,
            city: (string) $data['cidade'] ?? null,
            state: (string) $data['uf'] ?? null,
        );
    }
}
