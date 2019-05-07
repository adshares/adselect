<?php

declare(strict_types=1);


namespace Adshares\Application\Controller;

use Symfony\Component\HttpFoundation\Response;
use Elasticsearch\ClientBuilder;

class InfoController
{
    public function info(): Response
    {
        $client = ClientBuilder::create()->build();
        return new Response('nothing');
    }
}
