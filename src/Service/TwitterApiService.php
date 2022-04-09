<?php


namespace App\Service;


use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TwitterApiService
{
    private $getParams;

    public function __construct(ParameterBagInterface $getParams)
    {
        $this->getParams = $getParams;
    }

    /**
     * Make a new Twitter post
     *
     * @param string $textContent
     * @param string $mediaIdContent
     *
     * @return bool
     */
    public function newTweet(string $textContent, string $mediaIdContent): bool
    {
        $consumerKey = $this->getParams->get('TWITTER_CONSUMER_KEY');
        $consumerSecret = $this->getParams->get('TWITTER_CONSUMER_SECRET');
        $accesToken = $this->getParams->get('TWITTER_ACCESS_TOKEN');
        $accesTokenSecret = $this->getParams->get('TWITTER_ACCESS_TOKEN_SECRET');
        $connection = new TwitterOAuth($consumerKey, $consumerSecret, $accesToken, $accesTokenSecret);

        $connection->post("statuses/update", [
            "status" => $textContent,
            "media_ids" => [$mediaIdContent]
        ]);

        if ($connection->getLastHttpCode() == 200) {
            return true;
        } else {
            throw new \ErrorException("Impossible to post new tweet");
        }
    }

    /**
     * Upload image on Twitter
     *
     * @param string $imageUrl the url of the picture
     *
     * @return string|null return Twitter media URL
     * @throws \Exception
     */
    public function postUploadImage(string $imageUrl): ?string
    {
        $consumerKey = $this->getParams->get('TWITTER_CONSUMER_KEY');
        $consumerSecret = $this->getParams->get('TWITTER_CONSUMER_SECRET');
        $accesToken = $this->getParams->get('TWITTER_ACCESS_TOKEN');
        $accesTokenSecret = $this->getParams->get('TWITTER_ACCESS_TOKEN_SECRET');
        $connection = new TwitterOAuth($consumerKey, $consumerSecret, $accesToken, $accesTokenSecret);

        $result = $connection->upload("media/upload", [
            "media" => $imageUrl,
        ]);

        if ($connection->getLastHttpCode() == 200) {
            return $result->media_id_string;
        } else {
            throw new \ErrorException("Impossible to upload image on Twitter");
        }
    }
}