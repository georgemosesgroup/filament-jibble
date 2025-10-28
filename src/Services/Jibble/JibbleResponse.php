<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Gpos\FilamentJibble\Services\Jibble\Exceptions\JibbleException;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class JibbleResponse
{
    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function response(): HttpResponse
    {
        return $this->response;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        return $this->response->json($key, $default);
    }

    public function collect(?string $key = null): Collection
    {
        return $this->response->collect($key);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function data(): array
    {
        $json = $this->json();

        if (! is_array($json)) {
            throw JibbleException::unexpectedResponse();
        }

        $data = Arr::get($json, 'data');

        if (is_array($data)) {
            return $data;
        }

        $value = Arr::get($json, 'value');

        if (is_array($value)) {
            return $value;
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        $json = $this->json();

        return is_array($json)
            ? Arr::get($json, 'meta', [])
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        $json = $this->json();

        return is_array($json)
            ? Arr::get($json, 'links', [])
            : [];
    }

    public function successful(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function status(): int
    {
        return $this->response->status();
    }
}
