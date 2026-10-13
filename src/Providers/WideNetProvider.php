<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

use function Saade\Cep\Helpers\sanitizeCEP;

class WideNetProvider extends Provider
{
    protected function request(string $cep): RequestInterface
    {
        $cep = sanitizeCEP($cep, formatted: true);

        return new Request('GET', "https://cdn.apicep.com/file/apicep/{$cep}.json", ['Accept' => 'application/json']);
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to WideNet provider.');
        }

        $data = $this->json($response);

        if (($data['ok'] ?? null) === false || ($data['status'] ?? null) !== 200 || empty($data['code'])) {
            throw new Exception('Could not parse WideNet provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        $data = $this->json($response);

        return new CepResponse(
            provider: 'widenet',
            cep: str_replace('-', '', $data['code']),
            street: $data['address'] ?? null,
            neighborhood: $data['district'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
        );
    }
}
