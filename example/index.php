<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

// Set timezone to match Metahash.
date_default_timezone_set('UTC');

// Allow enough memory to complete transactions.
ini_set('memory_limit', '1024M');

// Add any addresses you would like to skip.
$skipAddresses = [
    'SKIP_ADDRESS_1',
    'SKIP_ADDRESS_1'
];

// Add any addresses that get paid different percentages from default percentage.
$superAddresses = [
    'SUPER_ADDRESS_1' => 85, // Pays 85% to specified address.
    'SUPER_ADDRESS_2' => 100, // pays 100% to specified address.
];

// Set node information.
$nodes = [
    'address'        => '', // Node address.
    'private_key'    => '', // Node private Key.
    'data'           => '', // Data sent with transaction.
    'percentage'     => 80, // Default percentage paid to delegators.
    'superAddresses' => $superAddresses,
];

use MetahashPro\Rewards;

$rewards = new Rewards();

$rewards->debug = true;

$rewards->setSkipList($skipAddresses);

$payees = $rewards->getPayees($nodes);

if (! isset($payees['error']) && ! $rewards->debug) {
    $results = $rewards->sendPayments($payees, $nodes);
}

if (isset($results)) {
    echo '<pre>';
    print_r($results);
    echo '</pre>';
}

// DEBUGGING
if ($rewards->debug) {
    echo '<pre>';
    print_r($payees);
    echo '</pre>';
}
