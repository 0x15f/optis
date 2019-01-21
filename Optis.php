<?php
namespace optis;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Lynn Digital
 * @link https://lynndigital.com
*/

use optis\blockchain\Chain;
use optis\blockchain\Block;

use optis\network\Server;
use optis\network\Client;

class Optis {

    /**@var Chain*/
    public $chain;

    /**@var Server*/
    public $server;

    /**@var Client*/
    public $client;

    /**
     * Optis constructor.
     * @param Chain $chain
     */
    public function __construct(Chain $chain) {
        $this->chain = $chain;
        $this->chain->init();

        $this->client = new Client($this);
    }

    public function addBlock(Block $block) {
        $this->chain->addBlock($block);
        $this->client->pushBlock($block);
    }

    public function stop() {
        $this->chain->save();
    }

}