<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Selector\Selector;
use League\Csv\Writer;

//$result = $client->request('GET', 'https://store.steampowered.com/search/?sort_by=&sort_order=0&special_categories=&filter=topsellers&tags=492&page=1',[
//$result = $client->request('GET', 'https://store.steampowered.com/contenthub/querypaginated/tags/TopSellers/render?cc=%22RU%22&count=15&l=%22russian%22&start=15&v=4',[

Route::get('/collect',function () {
    $main = new \App\Http\Controllers\SteamDataCollector();
    $main->collectRankData('TopSellers','Indie',100);
});

/** @noinspection PhpUndefinedClassInspection */
Route::get('/',
    function () {
        $game = \App\Game::where('steam_id',0);
        if($game)
            echo $game->count();
        dd($game);
        die;

        $client = new GuzzleHttp\Client();
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        $rank = 0;
        for ($start = 0; $start < 100; $start += 15) {

            $result = $client->request('GET', 'https://store.steampowered.com/contenthub/querypaginated/tags/ConcurrentUsers/render',[
                "query" => [
                    "cc" => "RU",
                    //"count" => 30,
                    //"pagesize" => 100,
                    "l" => "russian",
                    "query" => "",
                    "start" => $start,
                    "tag" => "Indie"
                ],
                "connect_timeout" => 10.0
            ]);


            $json = json_decode($result->getBody()->getContents());
            $html = $json->results_html;

            $dom = new Dom();
            $dom->load($html);

            $items = $dom->find('a.tab_item');

            foreach ($items as $item) {
                $row = [];
                $parser = new \PHPHtmlParser\Selector\Parser();

                $rank++;
                $row['rank'] = $rank;

                $tag = $item->getTag();
                $href = $tag->getAttribute('href')['value'];
                $cleanHref = strstr($href, '?', true);
                if ($cleanHref) $href = $cleanHref;
                $row['href'] = $href;
                $row['app_id'] = $tag->getAttribute('data-ds-appid')['value'];
                $row['tag_ids'] = $tag->getAttribute('data-ds-tagids')['value'];

                $selectCost = new PHPHtmlParser\Selector\Selector('div.discount_block', $parser);
                $costDiv = $selectCost->find($item);
                $costTag = $costDiv->getTag();
                $row['price'] = $costTag->getAttribute('data-price-final')['value'];

                $selectName = new PHPHtmlParser\Selector\Selector('div.tab_item_name', $parser);
                $nameDiv = $selectName->find($item);
                $row['name'] = $nameDiv->text();

                $selectTagNames = new PHPHtmlParser\Selector\Selector('div.tab_item_details span.top_tag', $parser);
                $tagNames = $selectTagNames->find($item);
                $tagNamesStr = '';
                foreach ($tagNames as $tagName) {
                    $tagNamesStr .= $tagName->text();
                }
                $row['tag_names'] = $tagNamesStr;

                $csv->insertOne($row);

                $game = new \App\Game();
                $game->name = $row['name'];
                $game->url = $row['href'];
                $game->steam_id = $row['app_id'];
                $game->save();

//                $table->integer('steam_id')->unique();
//                $table->string('name');
//                $table->string('url');
//                $table->date('release_date');
//                $table->timestamps();
            }
        }

        //$csv->output('data.csv');
        return;


        /*
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig('../credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $tokenPath = '../token.json';
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);

        $spreadsheetId = '1BBLnJyKaIH-tL1aac-MWepClZG5mqMQJBEct5xVYvDg';
        $service = new Google_Service_Sheets($client);

        $body = new Google_Service_Sheets_ValueRange([
            'values' => $values
        ]);
        $result = $service->spreadsheets_values->append($spreadsheetId, 'Sheet1', $body);
        //printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());



        print_r($result);

        return;
        */

        // Что мне нужно
        // ссылка
        // название
        // id
        // tags
        // rank


        //print_r( $body->getContents() );
        //return;


        //  'headers' => ['Accept-Language' => 'en-US'],


        /*$client = new GuzzleHttp\Client();
        $client->
        $result = $client->get('https://store.steampowered.com/search/?sort_by=&sort_order=0&special_categories=&filter=topsellers&tags=492&page=1');
        $body = $result->getBody();

        print_r( $body->getContents() );
        return;

        $dom = new Dom();
        $dom->load($body->getContents());
        //$dom->loadFromUrl('http://store.steampowered.com');


        echo $a->text;
        //echo $body->getContents();
        //print_r($body);
        //return $result;

        //return view('welcome');*/

    });
