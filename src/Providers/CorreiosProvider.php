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

    /**
     * @throws Exception
     */
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if ($this->address($response) === null) {
            throw new Exception('Could not parse Correios provider response.');
        }
    }

    protected function toDTO(Response $response): CepResponse
    {
        $data = $this->address($response);

        return new CepResponse(
            provider: 'correios',
            cep: (string) $data->cep,
            street: (string) $data->end,
            neighborhood: (string) $data->bairro,
            city: (string) $data->cidade,
            state: (string) $data->uf,
        );
    }

    protected function address(Response $response): ?SimpleXMLElement
    {
        $xml = simplexml_load_string($this->utf8Body($response), options: LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            return null;
        }

        $address = $xml->xpath('//return')[0] ?? null;

        return (string) $address?->cep === '' ? null : $address;
    }

    protected function utf8Body(Response $response): string
    {
        $body = $response->body();

        return preg_match('//u', $body) ? $body : iconv('ISO-8859-1', 'UTF-8', $body);
    }
}
