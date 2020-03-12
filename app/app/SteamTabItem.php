<?php


namespace App;


use PHPHtmlParser\Selector\Parser;
use stringEncode\Exception;

class SteamTabItem
{
    private $dom = null;
    private $parser = null;


    private const TAG_ATTRIBUTE_NAME = '';
    private const TAG_ATTRIBUTE_URL = '';
    private const TAG_ATTRIBUTE_BUNDLE_ID = '';
    private const TAG_ATTRIBUTE_STEAM_ID = '';
    private const TAG_ATTRIBUTE_PACKAGE_ID = '';
    private const TAG_ATTRIBUTE_TAG_IDS = '';
    private const DIV_NAME_SELECTOR = '';
    private const DIV_PRICE_SELECTOR = '';
    private const DIV_TAG_NAMES_SELECTOR = '';


    public function __construct($dom)
    {
        if (!isset($dom))
            throw new Exception('Creating SteamTabItem without DOM');

        // make tab item from HTML
        $this->parser = new Parser();
        $this->dom = $dom;
    }

    public function getName(){
        try {
            $selectName = new \PHPHtmlParser\Selector\Selector('div.tab_item_name', $this->parser);
            $nameDiv = $selectName->find($this->dom);
            $name = $nameDiv->text();
            return $name;
        }
        catch (\Exception $e) {
            Log::error('Parsing name from DOM failed. Exception: '.$e->getMessage());
            return null;
        }
    }

    public function getPrice(){
        $selectCost = new PHPHtmlParser\Selector\Selector('div.discount_block', $this->parser);
        $costDiv = $selectCost->find($this->dom);
        $costTag = $costDiv->getTag();
        $price = $costTag->getAttribute('data-price-final')['value'];
        return $price;
    }

    public function getTagIds() {
        $tag = $this->dom->getTag();
        $tag_ids = $tag->getAttribute('data-ds-tagids')['value'];
        return $tag_ids;
    }

    public function getTagNames() {
        $selectTagNames = new PHPHtmlParser\Selector\Selector('div.tab_item_details span.top_tag', $this->parser);
        $tagNames = $selectTagNames->find($this->dom);
        $tagNamesStr = '';
        foreach ($tagNames as $tagName) {
            $tagNamesStr .= $tagName->text();
        }
        return $tagNamesStr;
    }

    public function getSteamAppId() {
        try {
            $tag = $this->dom->getTag();
            $steam_id = $tag->getAttribute('data-ds-appid')['value'];
            return $steam_id;
        }
        catch (\Exception $e) {
            Log::error('Parsing app id from DOM failed. Exception: '.$e->getMessage());
            return null;
        }
    }

    public function getSteamBundleId() {
        try {
            $tag = $this->dom->getTag();
            $steam_id = $tag->getAttribute('data-ds-bundleid')['value'];
            return $steam_id;
        }
        catch (\Exception $e) {
            Log::error('Parsing bundle id from DOM failed. Exception: '.$e->getMessage());
            return null;
        }
    }

    public function getSteamPackageId() {
        try {
            $tag = $this->dom->getTag();
            $steam_id = $tag->getAttribute('data-ds-packageid')['value'];
            return $steam_id;
        }
        catch (\Exception $e) {
            Log::error('Parsing package id from DOM failed. Exception: '.$e->getMessage());
            return null;
        }
    }

    public function getUrl() {
        try {
            $tag = $this->dom->getTag();
            $href = $tag->getAttribute('href')['value'];
            $cleanHref = strstr($href, '?', true);
            if ($cleanHref) $href = $cleanHref;
            return $href;
        }
        catch (\Exception $e) {
            Log::error('Parsing URL from DOM failed. Exception: '.$e->getMessage());
            return null;
        }
    }

    public static function makeTabItemsFromHTML($html) {

        if(!isset($html))
            return null;

        $items = null;
        $domItems = null;
        try {
            $dom = new Dom();
            $dom->load($html);

            $domItems = $dom->find('a.tab_item');
            foreach ($domItems as $domItem) {
                $items[] = new SteamTabItem($domItem);
            }
        }
        catch (Exception $e) {
            Log::error('Failed creating SteamTabItems from HTML. Exception: '.$e->getMessage());
            return null;
        }

        return $items;
    }

}
