<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

class CorreiosAltProvider extends Provider
{
    protected function request(string $cep): RequestInterface
    {
        return new Request(
            'POST',
            'https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => 'https://buscacepinter.correios.com.br/app/endereco/index.php',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
            ],
            http_build_query(['endereco' => $cep, 'tipoCEP' => 'ALL']),
        );
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to Correios-alt provider.');
        }

        $data = $this->json($response);

        if (! empty($data['erro']) || ($data['total'] ?? null) === 0 || empty($data['dados'][0]['cep'])) {
            throw new Exception('Could not parse Correios-alt provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        $address = $this->json($response)['dados'][0];

        return new CepResponse(
            provider: 'correios-alt',
            cep: $address['cep'],
            street: $address['logradouroDNEC'] ?? null,
            neighborhood: $address['bairro'] ?? null,
            city: $address['localidade'] ?? null,
            state: $address['uf'] ?? null,
        );
    }
}
