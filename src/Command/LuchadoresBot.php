<?php


namespace App\Command;


use App\Service\OpenSeaApiService;
use App\Service\TwitterApiService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LuchadoresBot extends Command
{
    private $openseaApi;
    private $twitterApi;
    private $getParams;
    private $container;
    protected static $defaultName = 'bot:post';

    /**
     * LuchadoresBot constructor.
     *
     * @param TwitterApiService $twitterApi
     * @param OpenSeaApiService $openSeaApi
     * @param ParameterBagInterface $getParams
     * @param ContainerInterface $container
     */
    public function __construct(TwitterApiService $twitterApi, OpenSeaApiService $openSeaApi, ParameterBagInterface $getParams, ContainerInterface $container)
    {
        parent::__construct();
        $this->twitterApi = $twitterApi;
        $this->openseaApi = $openSeaApi;
        $this->getParams = $getParams;
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lastTweetTime = $this->twitterApi->getLastTweetDateTime();
        $lastUpdateDateTime = $lastTweetTime !== null ? $lastTweetTime : new \DateTime("10 minutes ago");
        $io = new SymfonyStyle($input, $output);
        $this->sales($io, $lastUpdateDateTime);
        return Command::SUCCESS;
    }




    /**
     * Publish all the sales
     *
     * @param SymfonyStyle $io
     * @param \DateTime $lastUpdateDateTime
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sales(SymfonyStyle $io, \DateTime $lastUpdateDateTime){
        $allSales = $this->openseaApi->getListLastSalesAfter($lastUpdateDateTime, 'luchadores-io');
        $actualTime = new \DateTime();
        $contract = $this->getParams->get('LUCHADORES_CONTRACT_ADRESS');
        $luchadoreTokenUrl = $this->getParams->get('LUCHADORES_TOKEN_URL');

        $io->info('--- Start tweet new sales ('.$actualTime->format('m-d-Y H:i:s').') ---');
        foreach ($allSales as $sale){
            $tokenId = $sale["asset"]["token_id"];
            $luchadore = $this->openseaApi->getAllDataNFT($contract, $tokenId);
            if($luchadore == null){
                $io->error('[ERROR] Impossible to get all data for Luchadores #'.$tokenId);
                continue;
            }

            $imagePath = $this->getImageUrl($io, $tokenId);
            $twitterMediaId = $this->twitterApi->postUploadImage($imagePath);
            if($twitterMediaId !== null) {
                $number = count($luchadore['traits']) - 1;
                $numberAttribute = $luchadore['traits'][$number]['value'];
                $textContent = 'Luchador #' . $tokenId . ' with ';
                if ($numberAttribute <= 1 ) {
                    $textContent .= $numberAttribute .' attribute' ;
                } else {
                    $textContent .= $numberAttribute .' attributes' ;
                }
                $numberOfTokenSale = $sale["total_price"] /  pow(10, $sale["payment_token"]["decimals"]);
                $sellerAdresse = $sale["seller"]["user"]["username"] !== null ? $sale["seller"]["user"]["username"] : substr($sale["seller"]["address"], 0, 8);
                $buyerAdresse = $sale["winner_account"]["user"]["username"] !== null ? $sale["winner_account"]["user"]["username"] : substr($sale["winner_account"]["address"], 0, 8);
                $usdPrice = $numberOfTokenSale * $sale["payment_token"]["usd_price"];
                $textContent .= ' bought for ' . $numberOfTokenSale . ' ' . $sale["payment_token"]["symbol"] . '($'. $usdPrice . ') by ' . $buyerAdresse . ' from ' . $sellerAdresse . '.'. chr(13) . chr(10) . $luchadoreTokenUrl . $tokenId;

                $result = $this->twitterApi->newTweet($textContent, $twitterMediaId);
                if ($result == false) {
                    $io->error('[ERROR] Impossible to make a new Tweet for Luchadores #'.$tokenId);
                } else {
                    $io->info('[INFO] New tweet for Luchadores #'.$tokenId);
                }
            } else {
                $io->error('[ERROR] Impossible to post picture on Twitter for Luchadores #'.$tokenId);
                continue;
            }
        }
        $io->info('--- End tweet new sales ---');
    }



    /**
     * Return the path for an image by ID send
     *
     * @param SymfonyStyle $io
     * @param int $tokenId
     *
     * @return string
     */
    private function getImageUrl(SymfonyStyle $io, int $tokenId): string
    {
        $imageUrl = $this->getParams->get('LUCHADORES_IMAGE_URL');
        $imageExtension = $this->getParams->get('LUCHADORES_IMAGE_EXTENSION');
        $imageFolder = $this->getParams->get('LUCHADORES_IMAGE_FOLDER');
        $projectRoot = $this->container->get('kernel')->getProjectDir();
        $imageName = $tokenId . '.' . $imageExtension;

        // if image is not in local, grab it on a server to save it in local
        if (($data = @file_get_contents($projectRoot . DIRECTORY_SEPARATOR .'public'. DIRECTORY_SEPARATOR. $imageFolder . DIRECTORY_SEPARATOR . $imageName)) === false) {
            $io->info("[INFO] Save image for a Luchadores : " . $tokenId);
            $imageCompleteUrl = $imageUrl . $imageName;
            file_put_contents($projectRoot . DIRECTORY_SEPARATOR .'public'. DIRECTORY_SEPARATOR. $imageFolder . DIRECTORY_SEPARATOR . $imageName, file_get_contents($imageCompleteUrl));
        }

        return $projectRoot . DIRECTORY_SEPARATOR .'public'. DIRECTORY_SEPARATOR. $imageFolder . DIRECTORY_SEPARATOR . $imageName;
    }
}