<?php

namespace Saade\Cep\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasXmlBody;

class CorreiosRequest extends SoloRequest implements HasBody
{
    use HasXmlBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $cep)
    {
    }

    protected function defaultBody(): string
    {
        return <<<XML
        <?xml version="1.0"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cli="http://cliente.bean.master.sigep.bsb.correios.com.br/">
              <soapenv:Header />
              <soapenv:Body>
                <cli:consultaCEP>
                  <cep>$this->cep</cep>
                </cli:consultaCEP>
              </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function resolveEndpoint(): string
    {
        return 'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
