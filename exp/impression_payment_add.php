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


$events = json_decode(file_get_contents('impression_payment_add.json'), JSON_OBJECT_AS_ARRAY);

$params = ['body' => []];
$n = 0;
$k = count($events);
foreach($events as $i => $event) {
    $cid = substr($event['event_id'], 0, -2);
    $params['body'][] = [
        'update' => [
            '_index' => 'events',
            '_type' => '_doc',
            '_id' => $cid,
            'retry_on_conflict' => 5,
        ]
    ];

    $params['body'][] = [
        '_source' => 'paid_amount',
        'script' => [
            'source' => 'ctx._source.paid_amount+=params.paid_amount',
            "params" => [
                "paid_amount" => $event['paid_amount']
            ],
            'lang' => 'painless',
        ],
    ];
    $n++;

    if ($n == 1000 || $i == $k - 1) {
        $responses = $client->bulk($params);
//        print_r($responses);
        // erase the old bulk request
        $params = ['body' => []];
        $n = 0;

        // unset the bulk response when you are done to save memory
        unset($responses);
    }

}
