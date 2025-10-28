<?php

namespace Gpos\FilamentJibble\Services\Jibble\Exceptions;

use RuntimeException;

class JibbleException extends RuntimeException
{
    public static function missingApiToken(): self
    {
        return new self('Jibble API token is not configured. Please set the JIBBLE_API_TOKEN environment variable.');
    }

    public static function missingBaseUrl(): self
    {
        return new self('Jibble API base URL is not configured. Please set the JIBBLE_BASE_URL environment variable.');
    }

    public static function missingOrganizationUuid(): self
    {
        return new self('An organization UUID is required for this endpoint. Please set the JIBBLE_ORGANIZATION_UUID environment variable or pass one explicitly.');
    }

    public static function missingClientCredentials(): self
    {
        return new self('Jibble API credentials are not configured. Set either JIBBLE_API_TOKEN or both JIBBLE_CLIENT_ID and JIBBLE_CLIENT_SECRET.');
    }

    public static function tokenRequestFailed(string $message = 'Token request failed.'): self
    {
        return new self('Unable to obtain a Jibble access token: '.$message);
    }

    public static function unexpectedResponse(): self
    {
        return new self('The Jibble API returned an unexpected response structure.');
    }
}
