<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;
use SimpleXMLElement;

class CorreiosProvider extends Provider
{
    protected function request(string $cep): RequestInterface
    {
        return new Request(
            'POST',
            'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente',
            [
                'Content-Type' => 'text/xml;charset=UTF-8',
                'Cache-Control' => 'no-cache',
            ],
            <<<XML
            <?xml version="1.0"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cli="http://cliente.bean.master.sigep.bsb.correios.com.br/">
                  <soapenv:Header />
                  <soapenv:Body>
                    <cli:consultaCEP>
                      <cep>$cep</cep>
                    </cli:consultaCEP>
                  </soapenv:Body>
                </soapenv:Envelope>
            XML,
        );
    }

    /**
     * @throws Exception
     */
    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to Correios provider.');
        }

        if ($this->address($response) === null) {
            throw new Exception('Could not parse Correios provider response.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
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

    protected function address(ResponseInterface $response): ?SimpleXMLElement
    {
        $xml = simplexml_load_string($this->utf8Body($response), options: LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            return null;
        }

        $address = $xml->xpath('//return')[0] ?? null;

        return (string) $address?->cep === '' ? null : $address;
    }

    protected function utf8Body(ResponseInterface $response): string
    {
        $body = (string) $response->getBody();

        return preg_match('//u', $body) ? $body : iconv('ISO-8859-1', 'UTF-8', $body);
    }
}
