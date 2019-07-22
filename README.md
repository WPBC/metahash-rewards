# Metahash Rewards
Metahash Rewards is a automated PHP script to pay bonus rewards to delegators from [MetaHash](https://metahash.org ) nodes.

### Requirements

- PHP 7.1+
- ext-gmp
- ext-curl
- composer
- xboston/metahash-php 0.2.2

### Installation

1. Open your terminal and change the current working directory to the location where you want the cloned directory to be made.

```bash
$ cd path/to/your/folder/metahash-rewards/
```

2. Clone repository by typing the following in your terminal:

```bash
$ git clone https://github.com/WPBC/metahash-rewards.git
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

5. Setup cron job to automatically run the script at a specific time everyday. 

#### Debug Mode

The script is initially set in debug mode. To update debug mode, open index.php and set $node->debug to "true" or "false".

```php
$node->debug = true;
```

##### Please note:
If debug mode is set to "false", the script is live. When index.php is opened in your browser, the script will run and proceed to calculate and pay rewards.

### License

This package is released under the MIT license.
