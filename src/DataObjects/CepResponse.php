<?php

namespace Saade\Cep\DataObjects;

class CepResponse
{
    public function __construct(
        public string $provider,
        public ?string $cep,
        public ?string $street,
        public ?string $neighborhood,
        public ?string $city,
        public ?string $state,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'cep' => $this->cep,
            'street' => $this->street,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
