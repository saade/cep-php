<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Requests\CorreiosAltRequest;

class CorreiosAltProvider extends Provider
{
    protected static string $request = CorreiosAltRequest::class;

    protected static function getRequest(string $cep): Request
    {
        return new Request(
            method: 'POST',
            uri: 'https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php',
            headers: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Cache-Control' => 'no-cache',
                'Origin' => 'https://buscacepinter.correios.com.br',
                'Referer' => 'https://buscacepinter.correios.com.br/app/endereco/index.php',
            ],
            body: json_encode([
                'endereco' => $cep,
                'tipoCEP' => 'LOG',
            ])
        );
    }

    /**
     * @param  array  $data
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if ($response->getStatusCode() !== 200) {
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
