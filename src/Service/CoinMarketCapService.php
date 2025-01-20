<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
class CoinMarketCapService
{
    private HttpClientInterface $httpClient;
    private string $apiBaseUrl;
    private string $apiKey;
    public function __construct(HttpClientInterface $httpClient, string $apiBaseUrl, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUrl = $apiBaseUrl;
        $this->apiKey = $apiKey;
    }
    public function getCryptoPrices(array $symbols = ['BTC', 'ETH', 'IOTA']): array
    {
        $endpoint = '/cryptocurrency/listings/latest';
        $url = $this->apiBaseUrl . $endpoint;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (TransportExceptionInterface $e) {

        }

        $data = $response->toArray();

        $filteredPrices = [];
        foreach ($data['data'] as $crypto) {
            if (in_array($crypto['symbol'], $symbols)) {
                $filteredPrices[$crypto['symbol']] = $crypto['quote']['USD']['price'];
            }
        }

        return $filteredPrices;
    }
}