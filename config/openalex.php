<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAlex Base URL
    |--------------------------------------------------------------------------
    |
    | REST base for the OpenAlex Institutions API, used only to assist
    | filling in an academic credential's Institution field. Optional
    | enrichment — never a hard dependency for saving a credential.
    |
    */

    'base_url' => env('OPENALEX_BASE_URL', 'https://api.openalex.org'),

    /*
    |--------------------------------------------------------------------------
    | OpenAlex API Key
    |--------------------------------------------------------------------------
    |
    | Optional. Basic institution lookup works without one; OpenAlex only
    | requires it for the premium/polite pool. Never rendered to the
    | browser — read server-side only, inside the Infrastructure adapter.
    |
    */

    'api_key' => env('OPENALEX_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for OpenAlex before treating the request as failed.
    | Bounded to a sensible range so a misconfigured env value can't hang
    | the credential form.
    |
    */

    'timeout' => min(max((int) env('OPENALEX_TIMEOUT', 5), 1), 15),

    /*
    |--------------------------------------------------------------------------
    | Institution Suggestion Limit
    |--------------------------------------------------------------------------
    |
    | Maximum suggestions returned per search. Bounded to keep the
    | dropdown usable and the upstream request cheap.
    |
    */

    'institution_limit' => min(max((int) env('OPENALEX_INSTITUTION_LIMIT', 8), 1), 20),

    /*
    |--------------------------------------------------------------------------
    | Suggestion Cache TTL
    |--------------------------------------------------------------------------
    |
    | Seconds an identical normalized query's result is cached for, to
    | avoid re-hitting OpenAlex on every keystroke debounce. Uses the
    | app's default cache store — no dedicated infra required.
    |
    */

    'cache_ttl' => (int) env('OPENALEX_CACHE_TTL', 900),

];
