<?php
use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
use React\Filesystem\Filesystem;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/download.php';

$loop = \React\EventLoop\Factory::create();
$client = new Browser($loop);

$data = file_get_contents('php://input');
$data = json_decode($data, true);
if (isset($data['url']) && !empty($data['url'])) {
    preg_match('/^https:\/\/www.instagram.com\/p\/(.*?)\//', $data['url'], $match);
    if (empty($match)) {
        exit(json_encode(['code' => 1, 'msg' => 'not match']));
    }

    $download = new Download(new Browser($loop), Filesystem::create($loop));
    $client->get('https://www.instagram.com/p/' . $match[1])->then(function(ResponseInterface $response) use($download) {
        $html = (string) $response->getBody();
        preg_match('/<script type="text\/javascript">window._sharedData = (.*?);<\/script>/', $html, $match);
        $data = json_decode($match[1], true);
        $temp = [];
        $shortcode_media = $data['entry_data']['PostPage'][0]['graphql']['shortcode_media'];
        if (isset($shortcode_media['edge_sidecar_to_children'])) {
            foreach($shortcode_media['edge_sidecar_to_children']['edges'] as $page)
            {
                $path = $page['node']['display_url'];
                preg_match('/\d+_\d+_\d+_n.\w+/', $path, $match);

                $temp[] = ['remote' => $path, 'local' => 'images/' . $match[0]];
                $download->run($path, $match[0]);
            }

        } else {
            $singlePic = end($shortcode_media['display_resources'])['src'];
            preg_match('/\d+_\d+_\d+_n.\w+/', $singlePic, $res);
            $temp[] = ['remote' => $singlePic, 'local' => 'images/' . $res[0]];
            $download->run($singlePic, $res[0]);
        }

        $res = ['data' => $temp];
        echo json_encode($res);

    });

    $loop->run();
}


