<?php

namespace App\Http\Controllers;

use App\Game;
use App\Rank;
use App\SteamTabItem;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

class SteamDataCollector extends Controller
{
    /*
     * Есть процесс который регулярно стартует и загружает ранги
     *  В этот момент грузятся всякие данные по игре, но они не полные
     *  Все это сохраняется в таблицу рангов, связаывается с игрой, если она уже есть
     *  Если игры еще нет, то в таблицу игр заносится игра с пометкой incomplete
     *
     * Есть процесс, который регулярно просматривает incomplete игры и загружает по ним остаток данных
     *  После этого игра становится complete
     *
     * Если при загрузке рангов возникла проблема, то
     *  Если мы не смогли достучаться до сервиса в принципе - сохраняем лог
     *  Если не смогли распарсить данные - сохраняем лог, если получается найти пару игра-ранг+дата,
     *      то записываем с пометкой, что данные битые; если не получается, то ничего не записываем
     */

    private const ITEMS_PER_RANK_PAGE = 30;

    private function getRequestUrl($rankType) {
        return 'https://store.steampowered.com/contenthub/querypaginated/tags/' . $rankType . '/render';
    }

    private function requestRankPageHTML($rankType, $category, $start) {

        $json = null;

        if (!isset($rankType, $category, $start))
            return null;

        try {
            // request Store for data
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $this->getRequestUrl($rankType),[
                "query" => [
                    "cc" => "RU",
                    "l" => "russian",
                    "query" => "",
                    "start" => $start,
                    "tag" => $category
                ],
                "connect_timeout" => 10.0
            ]);

            if ($response->getStatusCode() != 200) {
                // LOG request failed and exit
                return null;
            }

            $json = json_decode($response->getBody()->getContents());
            if (!$json) {
                // LOG failed to decode response contents
                return null;
            }
        }
        catch (\Exception $e) {
            // TODO log errors
        }

        return $json->results_html;
    }

    private function getTagsBySteamIds($tagIds) {
        $ids = explode($tagIds, ',');
        $tags = \App\Tag::whereIn('steam_id', $ids);
    }

    private function updateGameTags($gameId, $tagIds, $tagNames) {
        //TODO update tags...
        $game = \App\Game::find($gameId);
        if (!$game)
            return false;

        $ids = explode($tagIds, ',');
        $names = explode($tagNames, ',');
        $i=0;
        foreach ($ids as $id) {
            $tag = \App\Tag::where('steam_id', $id)->first();
            if (!$tag) {
                $tag = new \App\Tag();
                $tag->steam_id = $id;
                $tag->name = $names[$i];
                $tag->save();
            }
            $game->tags()->find($tag->id);
            $i++;
        }
    }

    private function loadTabItems($rankType, $category, $start) {
        $html = $this->requestRankPageHTML($rankType, $category, $start);
        $items = SteamTabItem::makeTabItemsFromHTML($html);
        return $items;
    }

    private function collectRankPageData($rankType, $category, $start) {
        $now = date("Y-m-d H:i:s");
        $items = $this->loadTabItems($rankType, $category, $start);
        if(!$items)
        {
            //TODO Log tab loading error
            return;
        }

        $position = $start+1;
        foreach ($items as $item) {
            $game = \App\Game::getOrCreateBySteamTabItem($item);
            if (!$game) {
                // TODO Log no game error
                continue;
            }

            $rank = new \App\Rank();
            $rank->rank = $position;
            $rank->type = $rankType;
            $rank->date = $now;
            $game->ranks()->save($rank);
            $position++;
        }

    }

    public function collectRankData($rankType, $category, $count) {

        $start = 0;
        while($start < $count) {
            $this->collectRankPageData($rankType, $category, $start);
            $start += self::ITEMS_PER_RANK_PAGE;
        }
    }
}
