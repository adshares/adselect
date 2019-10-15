<?php
/**
 * Created by PhpStorm.
 * User: jacek
 * Date: 09.10.19
 * Time: 17:52
 */

$rpms = [100,
99,
80,
70,
60,
50,
40,
35,
30,
    25,25,25,25,25,25,25,25,25,
];

$stats = [];

$i=0;
while($i++ < 1000000) {

    $rand = array_map(function($x) {
        return $x * mt_rand() / mt_getrandmax();
    }, $rpms);

    arsort($rand);
    $winner = key($rand);
    @$stats[$winner]++;
//    echo "$winner\n";
}

arsort($stats);

$sum = array_sum($stats);
print_r(array_map(function($x) use($sum) {
    return $x / $sum * 100;
}, $stats));