<?php

namespace Saade\Cep;

use GuzzleHttp\Promise\Utils;
use Saade\Cep\Connectors\RaceConnector;
use Saade\Cep\DataObjects\CepResponse;
use Throwable;

use function Saade\Cep\Helpers\sanitizeCEP;

class Cep
{
    public static function get(string $cep): ?CepResponse
    {
        $cep = sanitizeCEP($cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        try {
            return Providers\OpenCepProvider::get($cep);
        } catch (Throwable) {
            return static::race($cep, [
                Providers\CorreiosProvider::class,
                Providers\ViaCepProvider::class,
                Providers\WideNetProvider::class,
                Providers\CorreiosAltProvider::class,
                Providers\BrasilApiProvider::class,
            ]);
        }
    }

    /**
     * @param  array<int, class-string<Providers\Provider>>  $providers
     */
    public static function race(string $cep, array $providers): ?CepResponse
    {
        $connector = new RaceConnector;
        $winner = null;
        $promises = [];

        foreach ($providers as $provider) {
            $promises[] = $provider::getAsync($cep, $connector)->then(
                function (CepResponse $response) use (&$winner, $connector) {
                    $winner ??= $response;

                    $connector->cancelTransfers();
                },
                fn () => null,
            );
        }

        Utils::all($promises)->wait();

        return $winner;
    }
}
