<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Illuminate\Support\Collection;

class JibblePaginatedResponse
{
    public function __construct(private readonly JibbleResponse $response)
    {
    }

    /**
     * @return array<int|string, mixed>
     */
    public function items(): array
    {
        return $this->response->data();
    }

    public function collect(): Collection
    {
        return collect($this->items());
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->response->meta();
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        return $this->response->links();
    }

    public function nextPageUrl(): ?string
    {
        return $this->links()['next'] ?? null;
    }

    public function previousPageUrl(): ?string
    {
        return $this->links()['prev'] ?? null;
    }

    public function hasMorePages(): bool
    {
        $meta = $this->meta();

        if (isset($meta['current_page'], $meta['last_page'])) {
            return (int) $meta['current_page'] < (int) $meta['last_page'];
        }

        return $this->nextPageUrl() !== null;
    }

    public function toResponse(): JibbleResponse
    {
        return $this->response;
    }
}
