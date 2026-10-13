<?php

namespace Saade\Cep;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
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
        $winner = null;
        $lookups = [];
        $settled = [];

        foreach ($providers as $provider) {
            $lookups[] = $provider::getAsync($cep);
        }

        foreach ($lookups as $lookup) {
            $settled[] = $lookup->then(
                function (CepResponse $response) use (&$winner, $lookups) {
                    $winner ??= $response;

                    array_map(fn (PromiseInterface $lookup) => $lookup->cancel(), $lookups);
                },
                fn () => null,
            );
        }

        Utils::all($settled)->wait();

        return $winner;
    }
}
