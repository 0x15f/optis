<?php
namespace optis\network;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Lynn Digital
 * @link https://lynndigital.com
*/

use optis\Optis;

use optis\blockchain\Chain;
use optis\blockchain\Block;

use optis\utils\Utils;

class Client {

    /**@var Optis*/
    public $optis;

    /**@var array*/
    public $peers = [];

    /**@var array*/
    private $default_peers = ['54.39.22.201:3046'];

    /**
     * Client constructor.
     */
    public function __construct(Optis $optis) {
        $this->optis = $optis;

        $this->fetchPeers();
        $this->fetchChain();
    }

    public function sync() {
        $this->fetchPeers();
        $this->fetchChain();
    }

    /**
     * @param Block $block
     */
    public function pushBlock(Block $block) {
        foreach($this->peers as $peer) {
            $this->sendPostRequest($peer . '/block/add', (array)$block);
        }
    }

    public function syncChain(Chain $chain) {
        foreach($this->peers as $peer) {
            $this->sendPostRequest($peer . '/chain/update', ['blocks' => $chain->blocks]);
        }
    }

    private function fetchPeers() {
        $new_peers = $this->default_peers;

        foreach($this->peers as $peer) {
            // we cant use Client::sendPostRequestAllPeers(); because we need to handle each response.
            $response = $this->sendGetRequest($peer . '/peers/fetch', []);
            if(!is_array($response)) {
                continue;
            }
            if(!isset($response['peers'])) {
                continue;
            }

            $new_peers = array_merge($new_peers, $response['peers']);
        }

        $this->peers = array_unique($new_peers);
    }

    private function fetchChain() {
        $new_chain = $this->optis->chain;

        foreach($this->peers as $peer) {
            // we cant use Client::sendPostRequestAllPeers(); because we need to handle each response.
            $response = $this->sendGetRequest($peer . '/chain/fetch', []);
            if(!is_array($response)) {
                continue;
            }
            if(!isset($response['blocks'])) {
                continue;
            }

            //basically using the same thing from Server::process(); for handling chain updates
            $chain = Utils::chainFromArray($response['blocks'], $this->optis->chain->block_store);
            $new_chain = Utils::getBetterChain($new_chain, $chain);
        }

        if($this->optis->chain !== $new_chain) {
            //chain is better let's send it to all peers
            //potential issue: potential chain sync loop among all peers
            $this->sendPostRequestAllPeers('/chain/update', ['blocks' => $new_chain->blocks]);
        }
        $this->optis->chain = $new_chain;
    }

    /**
     * @param string $uri
     * @param array $body
     */
    private function sendPostRequestAllPeers(string $uri, array $body) {
        $multi = curl_multi_init();
        foreach($this->peers as $peer) {
            curl_multi_add_handle($multi, $this->sendPostRequest($peer . $uri, $body, True));
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        }
        while($mrc == CURLM_CALL_MULTI_PERFORM);

        while($active && $mrc == CURLM_OK) {
            if(curl_multi_select($multi) != -1) {
                do {
                    $mrc = curl_multi_exec($multi, $active);
                }
                while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
    }

    /**
     * @param string $uri
     * @param array $body
     * @return mixed
     */
    private function sendPostRequest(string $uri, array $body, bool $return_obj = False) {
        $uri = preg_match("~^(?:f|ht)tps?://~i", $uri) ? $uri : 'http://' . $uri;

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HEADER, [
            'Content-Type: application/json'
        ]);

        if($return_obj) {
            return $ch;
        }

        $result = curl_exec($ch);
        return is_string($result) ? @json_decode($result, True) : $result;
    }

    private function sendGetRequest(string $uri, array $args = []) {
        $uri = preg_match("~^(?:f|ht)tps?://~i", $uri) ? $uri : 'http://' . $uri;
        $args = http_build_query($args);
        $response = @file_get_contents($uri . '?' . $args);
        return $response;
    }
}