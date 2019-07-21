<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Set timezone to match Metahash.
date_default_timezone_set('UTC');

// Allow enough memory to complete transactions.
ini_set('memory_limit', '1024M');

// Address where the remaining coins will be sent.
$remaining = '0x0023511e8f42ff0c5597c4a1369bf023435a41bae55fcbe9f8'; // Not included yet...

// Any addresses that get paid different percentages from default percentage.
$superAddresses = [
  '0x0023511e8f42ff0c5597c4a1369bf023435a41bae55fcbe9f8' => 99,
  '0x00722914d7a6f22cd4a3ec43a2b60ee304131028acd1966c85' => 99,
  '0x0023511e8f42ff0c5597c4a1369bf023435a41bae55fcbe9f8' => 99,
];
$nodes = [
  /*
  [
    "address"       => "0x00d5b768fee94349103e2f69484dff207a3bbb2a5077defd6e",
    "private_key"   => "",
    "data"          => "Daisy || @metahashpro || Bonus Reward",
    "percentage"    => 95,
    "superAddrs"    => $superAddresses
  ],
  [
    "address"       => "0x00f8d738666669983a41a86ea05d9a62312e54b46b5c27a479",
    "private_key"   => "",
    "data"          => "Henry || @metahashpro || Bonus Reward",
    "percentage"    => 95,
    "superAddrs"    => $superAddresses
  ],
  */

    "address"       => "0x00fc51dee3a9aabf97633b6a9e1e1191c172aa504e7629a8bb",
    "private_key"   => "",
    "data"          => "Arthur || @metahashpro || Bonus Reward",
    "percentage"    => 95,
    "superAddresses"    => $superAddresses

];

use MetahashPro\Node;

$node = new Node();

$node->percentage = 95;

$node->debug = true;

$payees = $node->getPayees($nodes);

if(!isset($payees['errors'])) : // put all caught error here... not yet included
  //$results[] = $node->sendPayments($payees, $nodes);
endif;

//$node->log($results); // maybe add this to each fuction.

if($node->debug) :
  echo '<pre>';
  print_r($payees);
  echo '</pre>';
endif;

/*
try {
  $results = $node->getPayees($addr);
} catch (\Exception $e) {
  $results['errors'] = ['message' => $e->getMessage()];
}

if(!isset($results['errors'])) :
  try {
    $results = $node->sendPayments($results);
  } catch (\Exception $e) {
    $results['errors'] = ['message' => $e->getMessage()];
  }
endif;

if(isset($results['errors'])) :
  $node->log($results['errors'],); // maybe add this to each fuction.
  $node->notify($results['errors']);
endif;
*/
