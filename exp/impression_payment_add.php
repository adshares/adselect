<?php
use Elasticsearch\ClientBuilder;

require '../vendor/autoload.php';

$client = ClientBuilder::create();
$client->setTracer(new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutputInterface::VERBOSITY_DEBUG)));

$client = $client->build();


$params = [
    'index' => 'events',
];

if(! $client->indices()->exists($params) ) {
    CreateEventIndex($client);
} else {
//    $params['body'] = [
//        'query' => ['match_all' => (object)[]]
//    ];
//    $client->deleteByQuery($params);
}
