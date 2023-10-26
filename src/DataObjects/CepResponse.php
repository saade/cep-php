<?php

namespace Saade\Cep\DataObjects;

use Illuminate\Contracts\Support\Arrayable;

class CepResponse implements Arrayable
{
    public function __construct(
        public ?string $cep,
        public ?string $state,
        public ?string $city,
        public ?string $neighborhood,
        public ?string $street,
        public string $provider,
    ) {
    }

    public function toArray(): array
    {
        return [
            'cep' => $this->cep,
            'state' => $this->state,
            'city' => $this->city,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'provider' => $this->provider,
        ];
    }
}
