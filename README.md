# address-php-sdk

PHP library for ADDRESS API integration. Full ADDRESS API features documentation can be found [here](https://docs.address.so/).

## 1. Prerequisites

* PHP 7.2 or later

## 2. Installation

The ADDRESS PHP SDK can be installed using Composer by running the following command:

```sh
composer require address-so/address-php-sdk
```

## 3. Initialization

Create AddressApiClient object using the following code:

```php
$label = 'btc';
$api_key = 'your_api_key';
$secret_token = 'your_secret_token';

$address = new \AddressSo\AddressApiClient($label, $api_key, $secret_token);
```

## 4. Request sample
 
Example of sending funds from wallet
 
```php
$label = 'btc';
$api_key = 'your_api_key';
$secret_token = 'your_secret_token';

$address = new \AddressSo\AddressApiClient($label, $api_key, $secret_token);

$wallet_id = 1;
$amount = 0.1;
$recipient = 'bitcoin_address';
$payment_password = 'your_payment_password';

$address->sendFromWallet($wallet_id, $amount, $recipient, $payment_password);
```