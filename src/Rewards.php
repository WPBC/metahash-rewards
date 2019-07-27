<?php declare(strict_types=1);

namespace MetahashPro;

use Exception;
use FG\ASN1\Exception\ParserException;
use Metahash\Metahash;

class Rewards
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
  public function txs(string $address) : array
  {
      if (! $this->getMetahash()->checkAddress($address)) {
          throw new \Exception("Node address not valid", 1);
      }
      return $this->fetchFullHistory($address);
  }

  /**
   * Fetch full transaction history from methash.
   *
   * @param string $address
   *
   * @return array
   * @throws Exception
   */
  public function fetchFullHistory(string $address) : array
  {
      $maxLimit = MetaHash::HISTORY_LIMIT;
      $balance = $this->getMetahash()->fetchBalance($address);
      if ($balance['result']['count_txs'] <= $maxLimit) {
          return $this->getMetahash()->fetchHistory($address, $maxLimit);
      }
      $pages = \ceil($balance['result']['count_txs'] / $maxLimit) - 1;
      $options = [[]];
      for ($index = 0; $index <= $pages; $index++) {
          $history = $this->getMetahash()->fetchHistory($address, $maxLimit, $index * $maxLimit);
          $options[] = $history['result'];
      }
      $result = [
          'id'     => 1,
          'result' => \array_merge(...$options),
      ];
      return $result;
  }

  /**
   * Get all effective delegations ready for payments.
   *
   * @param array $node
   *
   * @return array
   * @throws Exception
   */
  public function getPayees(array $node) : array
  {
      try {
          $txs = $this->txs($node['address']);
      } catch (\Exception $e) {
          return $results['error'] = $e->getMessage();
      }

      $reward = $this->reward($txs);
      $beforeDate = strtotime(date('Y-m-d 00:00:00', strtotime('-24 hour')));

      $delegators = [];
      foreach ($txs["result"] as $tx) {
          if ($this->verify($tx) && $tx["to"] == $node['address']) {
              $delegators[] = array (
                "address"   => $tx["from"],
                "delegated" => 0,
                "reward"    => 0,
                "due"       => 0
              );
          }
      }

      $delegators = array_values(array_map("unserialize", array_unique(array_map("serialize", $delegators))));

      $total = 0;
      foreach ($txs["result"] as $tx) {
          if ($this->verify($tx) && $tx["to"] == $node['address']) {
              foreach ($delegators as $i => $d) {
                  if ($d['address'] == $tx["from"]) {
                      if ($tx["isDelegate"]) {
                          $total += $tx["delegate"];
                          $delegators[$i]["delegated"] += $tx["delegate"];
                      } else {
                          $total -= $tx["delegate"];
                          $delegators[$i]["delegated"] -= $tx["delegate"];
                      }
                  }
              }
          }
      }

      foreach ($delegators as $i => $d) {
        if ($d['delegated'] === 0) {
            unset($delegators[$i]);
        }

        if (isset($delegators[$i]['address'])) {
            $percentage = $node['percentage'];
            if (array_key_exists($d['address'], $node['superAddresses'])) {
                $percentage = $node['superAddresses'][$d['address']];
            }
            $roi = $this->roi($total, $reward, $d["delegated"]);
            $due = $this->percentage($roi, $percentage);

            $delegators[$i]["delegated"]  = floor($d["delegated"]);
            $delegators[$i]["reward"]     = floor($roi);
            $delegators[$i]["due"]        = floor($due);
        }
      }

      return array_values($delegators);
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
  public function sendPayments(array $payees, array $node) : array
  {
      $results = [];
      foreach ($payees as $payee) { $payee["due"] = (int) $payee["due"];
          if ($node["address"] == $payee["address"]) {
              continue;
          }
          $nonce = $this->getMetahash()->getNonce($node["address"]);
          try {
              $results[] = $this->getMetahash()->sendTx($node["private_key"], $payee["address"], $payee["due"], $node["data"], $nonce);
          } catch (\Exception $e) {
              $results[] = ['message' => $e->getMessage()];
          }
          sleep(3);
      }
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
  public function reward(array $txs = []) : float
  {
      $today = strtotime( date('Y-m-d 00:00:00') );

      $reward = 0;
      foreach ($txs["result"] as $tx) {
          if ($tx["from"] == "InitialWalletTransaction" && $tx["intStatus"] == 102 && $tx["timestamp"] >= $today) {
              $reward = $tx["value"];
          }
      }
      if ($reward) {
          return $reward;
      } else {
          throw new \Exception("No reward today", 1);
      }
  }

  /**
   * Verify transaction is an effective delegation.
   *
   * @param array $txs
   *
   * @return array
   */
  public function verify(array $tx) : bool
  {
      $beforeDate = strtotime(date('Y-m-d 00:00:00', strtotime('-24 hour')));

      if (isset($tx["isDelegate"]) && $tx["status"] == "ok" && $tx["timestamp"] < $beforeDate) {
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
  public function roi(int $total, float $reward, int $delegation = 0) : float
  {
      if ($delegation) {
          return $reward / $total * $delegation;
      } else {
          return $reward / $total * 1000;
      }
  }

  /**
   * Calculate percentage from ROI.
   *
   * @param float $roi
   * @param int $percentage
   *
   * @return float
   */
  public function percentage(float $roi, int $percentage) : float
  {
      return $roi / 100 * $percentage;
  }

}
