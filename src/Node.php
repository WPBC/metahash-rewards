<?php declare(strict_types=1);

namespace MetahashPro;

use Exception;
use FG\ASN1\Exception\ParserException;
use Metahash\Metahash;

class Node
{
  /**
   * @var bool
   */
  public $debug = false;
  /**
   * @var MetaHash
   */
  private $metahash;

  function __construct()
  {

  }

  /**
   * @return MetaHash
   */
  public function getMetahash() : MetaHash
  {
    if ($this->metahash === null) {
      $this->metahash = new MetaHash();
    }
    return $this->metahash;
  }

  /**
   * @param MetaHash $metahash
   */
  public function setMetahash(MetaHash $metahash): void
  {
    $this->metahash = $metahash;
  }

  /**
   * Get all transactions.
   *
   * @param string $address
   *
   * @return array
   * @throws Exception
   */
  public function txs( string $address ) : array
  {
    if (!$this->getMetahash()->checkAddress($address)) {
      throw new \Exception("Node address not valid", 1);
    }
    return $this->getMetahash()->fetchHistory($address);
  }


  /**
   * Fetch full transaction history from methash.
   *
   * @param string $address
   *
   * @return array
   * @throws Exception
   */
  public function fetchFullHistory($address) : array
  {
    // if transaction history is to long, loop through and merge arrays.
  }

  /**
   * Get all effective delegations ready for payments.
   *
   * @param array $node
   *
   * @return array
   * @throws Exception
   */
  public function getPayees( array $node ) : array
  {
    // Fetch Transactions
    try {
      $txs = $this->txs($node['address']);
      $this->log("Successfully received all transactions from metahash.");
    } catch (\Exception $e) {
      $this->log($e->getMessage());
      return $results['error'] = $e->getMessage();
    }

    $reward = $this->reward($txs); // need to catch exception
    $beforeDate = strtotime(date('Y-m-d 00:00:00', strtotime('-24 hour')));
    $delegators = [];

    // Create initial delegators array.
    foreach( $txs["result"] as $tx ){
      if( $this->verify($tx) && $tx["to"]==$node['address'] ){
        $delegators[] = array (
          "address"   => $tx["from"],
          "amount"    => 0,
          "roi"       => 0,
          "due"       => 0
        );
      }
    }

    // Remove all duplicate addresses and rest array keys.
    $delegators = array_values(array_map("unserialize", array_unique(array_map("serialize", $delegators))));

    // Calculate each addresses delegations and add to delegators array.
    $total = 0;
    foreach( $txs["result"] as $tx ) {
      if( $this->verify($tx) && $tx["to"]==$node['address'] ){
        foreach ( $delegators as $i => $d ) {
          if( $d['address'] == $tx["from"] ){
            if( $tx["isDelegate"] ){
              $total += $tx["delegate"];
              $delegators[$i]["amount"] += $tx["delegate"];
            } else {
              $total -= $tx["delegate"];
              $delegators[$i]["amount"] -= $tx["delegate"];
            }
          }
        }
      }
    }

    foreach ( $delegators as $i => $d ) {

      // Unset all ineffective delegations.
      if ($d['amount'] === 0) {
        unset($delegators[$i]);
      }

      // Calculate amount due from node percentage and roi.
      if( isset($delegators[$i]['address'] )){

        $percentage = $node['percentage'];
        if( array_key_exists($d['address'], $node['superAddresses']) ){
          $percentage = $node['superAddresses'][$d['address']];
        }
        $roi = $this->roi( $total, $reward, $d["amount"] );
        $due = $this->percentage( $roi, $percentage );

        $delegators[$i]["due"] = $this->numberFormat( $due );
        $delegators[$i]["amount"] = $this->numberFormat( $d["amount"] );
        $delegators[$i]["roi"] = $this->numberFormat( $roi );
      }
    }

    return array_values( $delegators );
  }

  /**
   * Send payments to delegators.
   *
   * @param array $payees
   * @param array $config
   *
   * @return float
   * @throws Exception
   */
  public function sendPayments( array $payees, array $node ) : array
  {
    $results = [];
    foreach ($payees as $payee) {

      $nonce = $metaHash->getNonce($node["address"]);

      try {
        $results[] = $metaHash->sendTx($node["private_key"], $payee["address"], $payee["due"], $node["data"], $nonce);
      } catch (\Exception $e) {
        $results[] = ['message' => $e->getMessage()];
      }

      sleep(1);
    }

    $this->log($results);

    return $results;
  }

  /**
   * Get today's reward.
   *
   * @param array $txs
   *
   * @return float
   * @throws Exception
   */
  public function reward( array $txs = [] ) : float
  {
    $today = strtotime(date('Y-m-d 00:00:00'));

    $reward = 0;

    foreach( $txs["result"] as $tx ) {
      if ($tx["from"] == "InitialWalletTransaction" && $tx["intStatus"] == 102 && $tx["timestamp"] >= $today){
        $reward = $tx["value"];
      }
    }

    if($reward):
      return $reward;
    else:
      throw new \Exception("No reward today", 1);
    endif;
  }

  /**
   * Verify transaction is an effective delegation.
   *
   * @param array $txs
   *
   * @return array
   */
  public function verify( array $tx ) : bool
  {
    $beforeDate = strtotime(date('Y-m-d 00:00:00', strtotime('-24 hour')));

    if(isset($tx["isDelegate"]) && $tx["status"]=="ok" && $tx["timestamp"] < $beforeDate){
      return true;
    }

    return false;
  }

  /**
   * Calculate ROI.
   *
   * @param float $total
   * @param int $reward
   * @param int $delegation (optional)
   *
   * @return float
   */
  public function roi( int $total, float $reward, int $delegation = 0 ) : float
  {
    if($delegation)
      return $reward / $total * $delegation;
    else
      return $reward / $total * 1000;
  }

  /**
   * Calculate percentage from ROI.
   *
   * @param float $roi
   * @param int $percentage
   *
   * @return float
   */
  public function percentage( float $roi, int $percentage ) : float
  {
    return $roi / 100 * $percentage;
  }

  /**
   * Format Numbers for metahash payments.
   *
   * @param array $number
   *
   * @return float
   */
  public function numberFormat ( float $number ) : float
  {
    $number = $number / 1e6; // Dont like this, find another way. turn float to string
    return (float) number_format($number, 6, '.', '');
  }

}
