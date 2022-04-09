<?php

namespace  App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenSeaApiService
{
    private $client;
    private $getParams;

    /**
     * OpenSeaApiService constructor.
     *
     * @param HttpClientInterface $client
     * @param ParameterBagInterface $getParams
     */
    public function __construct(HttpClientInterface $client, ParameterBagInterface $getParams)
    {
        $this->client = $client;
        $this->getParams = $getParams;
    }

    /**
     * Return an array of all successful sales after a DateTime and a Collection given
     *
     * @param \DateTime $dateTime
     * @param string $collection
     *
     * @return array|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getListLastSalesAfter(\DateTime $dateTime, string $collection): ?array
    {
        $apiKey = $this->getParams->get('OPENSEA_API_KEY');

        $response = $this->client->request(
            'GET',
            'https://api.opensea.io/api/v1/events',
            [
                'headers' => [
                    'x-api-key' => $apiKey,
                ],
                'query' => [
                    'collection_slug' => $collection,
                    'event_type' => 'successful',
                    'only_opensea' => 'false',
                    'occurred_after' => $dateTime->format('c'),
                ]
            ]
        );


        if($response->getStatusCode() == 200){
            return $response->toArray()['asset_events'];
        } else {
            throw new \ErrorException("Call to get all sales impossible");
        }

    }


    /**
     * Return all the data for an NFT
     *
     * @param string $contract contract of the NFT
     * @param int $tokenId id of the NFT
     *
     * @return array|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getAllDataNFT(string $contract, int $tokenId): ?array
    {
        $apiKey = $this->getParams->get('OPENSEA_API_KEY');
        $url = 'https://api.opensea.io/api/v1/asset/'.$contract.'/'.$tokenId;

        $response = $this->client->request(
            'GET',
            $url,
            [
                'headers' => [
                    'x-api-key' => $apiKey,
                ]
            ]
        );

        if($response->getStatusCode() == 200){
            return $response->toArray();
        } else {
            throw new \ErrorException("Call to get all data from OpenSea for #".$tokenId." impossible");
        }
    }
}