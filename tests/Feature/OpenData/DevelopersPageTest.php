<?php

it('lists every API address from the OpenAPI document, with its scope, the limits and the changelog', function () {
    $document = json_decode((string) file_get_contents(resource_path('api/openapi-v1.json')), true);

    $response = $this->get('/en/developers')->assertOk()
        ->assertSee('/api/v1/datasets')
        ->assertSee('/api/v1/projects/{key}')
        ->assertSee('60 requests a minute per key', false)
        ->assertSee('Open datasets added');

    foreach (array_keys($document['paths']) as $path) {
        $response->assertSee('/api/v1'.$path);
    }

    $this->get('/am/developers')->assertOk()->assertSee('ለገንቢዎች');
});
