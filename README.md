# Metahash Rewards
Metahash Rewards is a automated PHP script to pay bonus rewards to delegators from [MetaHash](https://metahash.org ) nodes.

### Requirements

- PHP 7.1+
- composer
- xboston/metahash-php 0.2.*

### Installation

1. Open your terminal and change the current working directory to the location where you want the cloned directory to be made.

```bash
$ cd path/to/your/folder/metahash-rewards/
```

2. Clone repository by typing the following in your terminal:

```bash
$ git clone git@github.com:WPBC/metahash-rewards.git
```

3. Run the following command in your terminal.

```bash
$ composer install
```

4. Open the index.php and enter your node details in the $nodes array.

```php
$nodes = [
  'address'         => '', // Node address. Example - 0x0....
  'private_key'     => '', // Node private Key. Example 3074020... or 3077020...
  'data'            => '', // Text data sent with transaction. Example - Reward from my SuperNode
  'percentage'      => 80, // Default percentage paid to delegators.
  'superAddresses'  => $superAddresses
];
```

5. Add any addresses that get paid different percentages from default percentage to the $superAddresses array.

```php
$superAddresses = [
    '0x0072578dg76acc75554erg84es81seg4esg14se84841wfaw87' => 90, // 90%
    '0x00256841dg47csca774de4gib6154see8451ff151841ddsd11' => 85, // 83%
];
```

6. To automatically run the reward script.  Firstly, run the following command in your terminal to open crontab.

```bash
$ crontab -e
```

7. Set the PHP script to run at 0:02 am everyday by adding the following line to you crontab and pressing enter.

```bash
$ 2 0 * * * /usr/bin/php /metahash-rewards/example/index.php >> /metahash-rewards/example/log.log 2>&1
```

### Debug Mode

Debug mode is initially enabled. Whilst in debug, the script will return an array of effective delegations, grouped by address. please note that payments will not be sent.

To disable debug mode, open index.php and set $node->debug to "false".

```php
$rewards->debug = true;
```

### License

This package is released under the MIT license.
