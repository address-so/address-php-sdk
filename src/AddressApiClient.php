<?php


namespace AddressSo;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class AddressApiClient
{
    private const BASE_URI = 'https://api.address.so/api/';

    /**
     * Guzzle client
     *
     * @var Client
     */
    private $client;

    /**
     * Current coin (btc, eth, ltc, etc...)
     *
     * @var string
     */
    private $coin;

    /**
     * X-Auth-token for use API
     * You can get in personal area
     *
     * @var string
     */
    private $api_token;

    /**
     * Secret-token for signing params
     * You can get in personal area
     *
     * @var string
     */
    private $secret_token;

    /**
     * @var int $timeout
     */
    private $timeout;

    /**
     * AddressApiClient constructor
     *
     * @param string $coin
     * @param string $api_token
     * @param string $secret_token
     * @param int $timeout
     */
    public function __construct(string $coin, string $api_token, string $secret_token, int $timeout = 2)
    {
        $this->coin = $coin;
        $this->api_token = $api_token;
        $this->secret_token = $secret_token;
        $this->timeout = $timeout;

        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'timeout' => $this->timeout
        ]);
    }

    /**
     * Get all coins
     *
     * @return array
     * @throws GuzzleException
     */
    public function getCoins(): array
    {
        return $this->request('GET', $this->makeUrl('coins', 'all'));
    }

    /**
     * Get coin info by symbol
     *
     * @return array
     * @throws GuzzleException
     */
    public function getCoin(): array
    {
        return $this->request('GET', $this->makeUrl('coins', 'read'));
    }

    /**
     * Get all wallets
     *
     * @return array
     * @throws GuzzleException
     */
    public function getWallets(): array
    {
        return $this->request('GET', $this->makeUrl('wallet', 'all'));
    }

    /**
     * Get wallet by id
     *
     * @param int $wallet_id
     * @return array
     * @throws GuzzleException
     */
    public function getWallet(int $wallet_id): array
    {
        return $this->request('GET', $this->makeUrl('wallet', 'read', $wallet_id));
    }

    /**
     * Create new wallet
     *
     * @param string $wallet_name
     * @return array
     * @throws GuzzleException
     */
    public function createWallet(string $wallet_name): array
    {
        $params = [
            'label' => $wallet_name
        ];

        return $this->request('POST', $this->makeUrl('wallet', 'create'), $params);
    }

    /**
     * Update wallet by id
     *
     * @param int $wallet_id
     * @param string $wallet_name
     * @return array
     * @throws GuzzleException
     */
    public function updateWallet(int $wallet_id, string $wallet_name): array
    {
        $params = [
            'label' => $wallet_name
        ];

        return $this->request('PUT', $this->makeUrl('wallet', 'update', $wallet_id), $params);
    }

    /**
     * Delete wallet by id. Work only if balance is 0
     *
     * @param int $wallet_id
     * @return array
     * @throws GuzzleException
     */
    public function deleteWallet(int $wallet_id): array
    {
        return $this->request('DELETE', $this->makeUrl('wallet', 'delete', $wallet_id));
    }

    /**
     * Get all transactions by wallet id
     *
     * @param int $wallet_id
     * @param int $limit
     * @param int|null $tag
     * @return array
     * @throws GuzzleException
     */
    public function getWalletTransactions(int $wallet_id, int $limit = 100, ?int $tag = null): array
    {
        $params = [
            'limit' => $limit
        ];

        if ($tag) {
            $params = array_merge($params, [
                'tag' => $tag
            ]);
        }

        return $this->request('GET', $this->makeUrl('wallet', 'transactions', $wallet_id), $params);
    }

    /**
     * Send funds from wallet
     *
     * Example of allowed additional params:
     *
     * $additionalParams = [
     *     'odd_address' => 'address',
     *     'token_label' => 'erc20',
     *     'fee_priority' => (1 - NORMAl, 2 - MEDIUM, 3 - HIGH),
     *     'tag' => 39381 (for Ripple)
     * ]
     *
     * @param int $wallet_id
     * @param float $amount
     * @param string $recipient
     * @param string $payment_password
     * @param array $additionalParams
     * @return array
     * @throws GuzzleException
     */
    public function sendFromWallet(
        int $wallet_id,
        float $amount,
        string $recipient,
        string $payment_password,
        array $additionalParams = []
    ): array
    {
        $params = $this->generateSendParams($amount, $recipient, $payment_password, $additionalParams);

        return $this->request('POST', $this->makeUrl('wallet', 'send', $wallet_id), $params, true);
    }

    /**
     * Get all accounts by wallet id
     *
     * @param int $wallet_id
     * @return array
     * @throws GuzzleException
     */
    public function getAccounts(int $wallet_id): array
    {
        return $this->request('GET', $this->makeUrl('account', 'all', $wallet_id));
    }

    /**
     * Get account by id
     *
     * @param int $wallet_id
     * @param int $account_id
     * @return array
     * @throws GuzzleException
     */
    public function getAccount(int $wallet_id, int $account_id): array
    {
        return $this->request('GET', $this->makeUrl('account', 'read', $wallet_id, $account_id));
    }

    /**
     * Create new account
     *
     * @param int $wallet_id
     * @return array
     * @throws GuzzleException
     */
    public function createAccount(int $wallet_id): array
    {
        return $this->request('POST', $this->makeUrl('account', 'create', $wallet_id));
    }

    /**
     * Delete account by id. Work only if balance is 0
     *
     * @param int $wallet_id
     * @param int $account_id
     * @return array
     * @throws GuzzleException
     */
    public function deleteAccount(int $wallet_id, int $account_id): array
    {
        return $this->request('DELETE', $this->makeUrl('account', 'delete', $wallet_id, $account_id));
    }

    /**
     * Archive accounts (soft delete)
     *
     * @param int $wallet_id
     * @param array $accounts
     * @return array
     * @throws GuzzleException
     */
    public function archiveAccounts(int $wallet_id, array $accounts): array
    {
        $params = [
            'accounts' => $accounts
        ];

        return $this->request('DELETE', $this->makeUrl('account', 'archive', $wallet_id), $params);
    }

    /**
     * Get all account transactions
     *
     * @param int $wallet_id
     * @param int $account_id
     * @param int $limit
     * @return array
     * @throws GuzzleException
     */
    public function getAccountTransactions(int $wallet_id, int $account_id, int $limit = 100): array
    {
        $params = [
            'limit' => $limit
        ];

        return $this->request('GET', $this->makeUrl('account', 'transactions', $wallet_id, $account_id), $params);
    }

    /**
     * Send funds from account
     *
     * Example of allowed additional params:
     *
     * $additionalParams = [
     *     'odd_address' => 'address',
     *     'token_label' => 'erc20',
     *     'fee_priority' => (1 - NORMAl, 2 - MEDIUM, 3 - HIGH),
     *     'tag' => 39381 (for Ripple)
     * ]
     *
     * @param int $wallet_id
     * @param int $account_id
     * @param float $amount
     * @param string $recipient
     * @param string $payment_password
     * @param array $additionalParams
     * @return array
     * @throws GuzzleException
     */
    public function sendFromAccount(
        int $wallet_id,
        int $account_id,
        float $amount,
        string $recipient,
        string $payment_password,
        array $additionalParams = []
    ): array
    {
        $params = $this->generateSendParams($amount, $recipient, $payment_password, $additionalParams);

        return $this->request('POST', $this->makeUrl('account', 'send', $wallet_id, $account_id), $params, true);
    }

    /**
     * Set permissions to wallet to user
     * Accept array of available permissions: view, order, transfer, admin
     *
     * @param int $wallet_id
     * @param int $user_id
     * @param array $permissions
     * @return array
     * @throws GuzzleException
     */
    public function setPermissions(int $wallet_id, int $user_id, array $permissions = []): array
    {
        $params = [
            'user_id' => $user_id,
            'permissions' => $permissions
        ];

        return $this->request('POST', $this->makeUrl('wallet', 'permissions', $wallet_id), $params);
    }

    /**
     * Remove all permissions for wallet
     *
     * @param int $wallet_id
     * @param int $user_id
     * @return array
     * @throws GuzzleException
     */
    public function removeAllPermissions(int $wallet_id, int $user_id): array
    {
        $params = [
            'user_id' => $user_id,
            'permissions' => ['0']
        ];

        return $this->request('POST', $this->makeUrl('wallet', 'permissions', $wallet_id), $params);
    }

    /**
     * Make url for request
     *
     * @param string $type
     * @param string $method
     * @param int|null $wallet_id
     * @param int|null $account_id
     * @return string
     */
    private function makeUrl(string $type, string $method, int $wallet_id = null, int $account_id = null): string
    {
        $coins = 'coins';
        $coin = sprintf('coins/%s/', $this->coin);
        $wallets = 'wallets/';
        $wallet = sprintf('%s/', $wallet_id);
        $accounts = 'accounts/';
        $account = sprintf('%s/', $account_id);
        $transactions = 'transactions/';
        $send = 'send/';
        $permissions = 'permissions/';
        $archive = 'archive/';

        $urls = [
            'coins' => [
                'all' => $coins,
                'read' => $coin
            ],
            'wallet' => [
                'all' => $coin . $wallets,
                'create' => $coin . $wallets,
                'read' => $coin . $wallets . $wallet,
                'update' => $coin . $wallets . $wallet,
                'delete' => $coin . $wallets . $wallet,
                'send' => $coin . $wallets . $wallet . $send,
                'permissions' => $coin . $wallets . $wallet . $permissions,
                'transactions' => $coin . $wallets . $wallet . $transactions,
            ],
            'account' => [
                'all' => $coin . $wallets . $wallet . $accounts,
                'create' => $coin . $wallets . $wallet . $accounts,
                'read' => $coin . $wallets . $wallet . $accounts . $account,
                'delete' => $coin . $wallets . $wallet . $accounts . $account,
                'archive' => $coin . $wallets . $wallet . $accounts . $archive,
                'send' => $coin . $wallets . $wallet . $accounts . $account . $send,
                'transactions' => $coin . $wallets . $wallet . $accounts . $account . $transactions,
            ],
        ];

        return $urls[$type][$method];
    }

    /**
     * Request method (Guzzle HTTP client)
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param bool $sign
     * @return array
     * @throws GuzzleException
     */
    private function request(string $method, string $url, array $params = [], bool $sign = false): array
    {
        try {
            $query['form_params'] = $params;
            $query['headers']['X-Api-Token'] = $this->api_token;

            if ($sign) {
                $query['form_params']['sign'] = $this->signParams($params);
            }

            $request = $this->client->request($method, $url, $query);

            return json_decode($request->getBody()->getContents(), true);
        } catch (RequestException $exception) {
            throw new RequestException($exception->getMessage(), $exception->getRequest());
        }
    }

    /**
     * Sign params with secret key
     *
     * @param array $params
     * @return string
     */
    private function signParams(array $params): string
    {
        ksort($params);

        $secret = hash('sha512', $this->secret_token);

        return hash_hmac('sha512', http_build_query($params), $secret);
    }

    /**
     * @param float $amount
     * @param string $recipient
     * @param string $paymentPassword
     * @param array|null $additionalParams
     * @return array
     */
    private function generateSendParams(
        float $amount,
        string $recipient,
        string $paymentPassword,
        ?array $additionalParams
    ): array
    {
        $params = [
            'amount' => $amount,
            'recipient' => $recipient,
            'payment_password' => $paymentPassword
        ];

        if ($additionalParams) {
            $params = array_merge($params, $additionalParams);
        }

        $additionalParamsAllowed = [
            'amount',
            'recipient',
            'payment_password',
            'odd_address',
            'token_label',
            'fee_priority',
            'tag'
        ];

        return array_intersect_key($params, array_flip($additionalParamsAllowed));
    }
}