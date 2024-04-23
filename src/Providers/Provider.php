<?php

namespace Saade\Cep\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use Saade\Cep\DataObjects\CepResponse;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;

use function Saade\Cep\Helpers\sanitizeCEP;

abstract class Provider
{   
    /**
     * @var class-string<SoloRequest>
     */
    protected static string $request;

    public static function get(string $cep): ?CepResponse
    {
        return (new static)->send($cep);
    }

    public static function getAsync(string $cep): PromiseInterface
    {
        return (new static)->sendAsync($cep);
    }

    public function send(string $cep): ?CepResponse
    {
        $cep = sanitizeCEP($cep);

        $response = (new static::$request($cep))->send();

        $this->handleErrors($response);

        return $this->toDTO($response);
    }
    
    public function sendAsync(string $cep): PromiseInterface
    {
        $cep = sanitizeCEP($cep);

        return (new static::$request($cep))
            ->sendAsync()
            ->then(
                function (Response $response): ?CepResponse {
                    $this->handleErrors($response);

                    return $this->toDTO($response);
                }
            );
    }

    /**
     * @throws Exception
     */
    protected abstract function handleErrors(Response $response): void;

    protected abstract function toDTO(Response $response): CepResponse;
}
