<?php

use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use React\Filesystem\Filesystem;

final class Download
{
    private $client;
    private $filesystem;

    public function __construct(Browser $client, Filesystem $filesystem)
    {
        $this->client = $client;
        $this->filesystem = $filesystem;
    }

    public function run(String $url, $file)
    {
        $filePath = './images/'.$file;
        if (!file_exists($filePath)) {
            $this->client->get($url)->then(function (ResponseInterface $response) use ($filePath){
                file_put_contents($filePath, $response->getBody());
                // TODO IN PI3, HAVE SOMETHING ERRORS
                // $this->filesystem->file($filePath)->open('cwt')->then(function ($stream) use ($response) {
                //     $stream->end($response->getBody());
                // });
            });
        }
    }
}
