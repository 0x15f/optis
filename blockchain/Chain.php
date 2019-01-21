<?php
namespace optis\blockchain;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Lynn Digital
 * @link https://lynndigital.com
*/

use optis\utils\Utils;

class Chain {

    /**@var string*/
    public $block_store;

    /**@var Block[]*/
    public $blocks = [];

    /**
     * Chain constructor.
     * @param string $block_store
     * @param bool $load
     * @param array $blocks
     * @throws \Exception
     */
    public function __construct(string $block_store, bool $load = True, array $blocks = []) {
        $this->block_store = $block_store;

        if($load) {
            if(!file_exists($block_store)) {
                file_put_contents($block_store, "");
            }

           $handle = @fopen($block_store, 'r+');
           if(!$handle) {
               throw new \Exception((string) error_get_last()['message']);
           }

           $size = @filesize($block_store);
           if($size === 0) {
               $this->blocks[] = Utils::getGenesisBlock();
               return;
           }
           $contents = @fread($handle, $size);
           if(!$contents) {
               throw new \Exception((string) error_get_last()['message']);
           }

           $decoded = @gzuncompress(base64_decode($contents));
           if(!is_string($decoded)) {
               throw new \Exception((string) error_get_last()['message']);
           }

           $blocks =  json_decode($decoded, true); //might need some error handling but not necessary right now
           foreach($blocks as $key => $value) {
               $this->blocks[$key] = Utils::blockFromArray($value);
           }
        }
        else {
            $this->blocks = $blocks;
        }

        if(empty($this->blocks)) {
            $this->blocks[] = Utils::getGenesisBlock();
        }
    }

    /**
     * @return int
     */
    public function getProof() {
        $previous_proof = $this->getLatestBlock()->proof;
        $proof = $previous_proof + 1;
        while(($proof + $previous_proof) % 7 !== 0) {
            $proof++;
        }

        return $proof;
    }

    public function init() {
        if(count($this->blocks) === 0) {
            $this->blocks[] = Utils::getGenesisBlock();
        }
    }

    /**
     * @param Block $block
     */
    public function addBlock(Block $block) {
        $this->blocks[] = $block;
    }

    /**
     * @return Block
     */
    public function getLatestBlock() {
        return @array_values(array_slice($this->blocks, -1))[0];
    }

    /**
     * @return array|Block[]
     */
    public function getBlocks() {
        return $this->blocks;
    }

    /**
     * @throws \Exception
     */
    public function save() {
        $handle = @fopen($this->block_store, 'w+');
        if(!$handle) {
            throw new \Exception(error_get_last()['message']);
        }

        $compressed = base64_encode(gzcompress(json_encode($this->blocks), 9));
        @fwrite($handle, $compressed, strlen($compressed));
    }
}