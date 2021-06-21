<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Controller;

use Symfony\Component\HttpFoundation\Response;
use Elasticsearch\ClientBuilder;

class InfoController
{
    public function info(): Response
    {
        $client = ClientBuilder::create()
            ->setHosts([])
            ->build();
        return new Response('nothing');
    }
}
