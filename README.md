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
  'address'         => '', // Node address.
  'private_key'     => '', // Node private Key.
  'data'            => '', // Data sent with transaction.
  'percentage'      => 80, // Default percentage paid to delegators.
  'superAddresses'  => $superAddresses
];
```

5. To automatically run the reward script.  Firstly, run the following command in your terminal to open crontab.

```bash
$ crontab -e
```

6. Set the PHP script to run at 0:02 am everyday by adding the following line to you crontab and pressing enter.

```bash
$ 2 0 * * * /usr/bin/php /metahash-rewards/example/index.php >> /metahash-rewards/example/log.log 2>&1
```

### Debug Mode

Debug mode is initially enabled. Whilst in debug, the script will return an array of effective delegations, grouped by address. please note that payments will not be sent.

To disable debug mode, open index.php and set $node->debug to "false".

```php
$node->debug = true;
```

### License

This package is released under the MIT license.
