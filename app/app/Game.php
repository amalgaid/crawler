<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    //
    public function ranks() {

        return $this->hasMany('App\Rank');
    }

    public function tags() {

        return $this->belongsToMany('App\Tag');
    }

    public static function getOrCreateBySteamTabItem(SteamTabItem $item) {
        if(!isset($item))
            return null;

        $bundle_id = $item->getSteamBundleId();
        if($bundle_id) {
            // TODO support bundles;
            // Just skip them for now
            return null;
        }

        $package_id = $item->getSteamPackageId();
        if($package_id) {
            // TODO support packages;
            // Just skip them for now
            return null;
        }

        $steam_id = $item->getSteamAppId();
        $game = \App\Game::where('steam_id', $steam_id)->first();

        if (!$game) {
            // create new game record
            $game = new \App\Game();
            $game->steam_id = $steam_id;
            $game->name = $item->getName();
            $game->url = $item->getUrl();
            $game->save();

        }
        return $game;
    }
}
