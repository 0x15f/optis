<?php
namespace optis\utils;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Lynn Digital
 * @link https://lynndigital.com
*/

use optis\blockchain\Block;
use optis\blockchain\Chain;

class Utils {

    /**
     * @return string
     */
    public static function UUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @param int $index
     * @param string $previous_hash
     * @param int $timestamp
     * @param int $proof
     * @param string $data
     * @return string
     */
    public static function calculateHash(int $index, string $previous_hash, int $timestamp, int $proof, string $data) {
        return hash('sha256',
            $index . $timestamp . $proof . $data . $previous_hash
        );
    }

    /**
     * @return Block
     */
    public static function getGenesisBlock() {
        return new Block(
            0,
            1526756947,
            0,
            'genesis',
            '0'
        );
    }

    /**
     * @param Block $block
     * @param Block $previous_block
     * @return bool
     *
     * NOT COMPLETED!
     */
    public static function isValidBlock(Block $block, Block $previous_block) {
        $valid_hash = Utils::calculateHash($block->index, $previous_block->hash, $block->timestamp, $block->proof, $block->data);
        if($block->index !== $previous_block->index + 1) {
            return False;
        }
        elseif($block->previous_hash !== $previous_block->hash) {
            return False;
        }
        elseif($block->hash !== $valid_hash) {
            return False;
        }

        return True;
    }

    /**
     * @param Chain $chain
     * @return bool
     */
    public static function isValidChain(Chain $chain) {
        if($chain->getBlocks()[0]->__toString() !== Utils::getGenesisBlock()->__toString()) {
            return False;
        }
        else {
            foreach($chain->getBlocks() as $index => $block) {
                if($index !== $block->index) {
                    return False;
                }

                if($index === 0 && $block->index === 0) { //skip first block
                    continue;
                }
                else {
                    $valid = @Utils::isValidBlock($block, $chain->blocks[$block->index - 1]);
                    if(!$valid) {
                        return False;
                    }
                }
            }
        }

        return True;
    }

    /**
     * @param Chain $current
     * @param Chain[] ...$chains
     * @return Chain
     *
     * NOT COMPLETED
     */
    public static function getBetterChain(Chain $current, Chain ...$chains) {
        $largest_size = 0;
        $largest_index = 0;
        foreach($chains as $index => $chain) {
            if(count($chain->getBlocks()) > $largest_size && Utils::isValidChain($chain)) {
                $largest_size = count($chain->getBlocks());
                $largest_index = $index;
            }
        }

        $chain = $chains[$largest_index];
        $unsafe = (count($chain->blocks) > count($current->blocks)) && (count($chain->blocks) - count($current->blocks) > 15);
        if($unsafe) {
            return count($current->blocks) === 1 ? $chain : $current;
        }

        return $chain;
    }

    /**
     * @param array $contents
     * @return Block
     */
    public static function blockFromArray(array $contents) {
        return new Block($contents['index'], $contents['timestamp'], $contents['proof'], $contents['data'], $contents['previous_hash']);
    }

    /**
     * @param array $contents
     * @param string $path
     * @return Chain
     */
    public static function chainFromArray(array $contents, string $path) {
        $chain = new Chain($path, False);
        foreach($contents as $key => $value) {
            $chain->{$key} = $value;
        }

        $new_blocks = [];
        foreach($chain->blocks as $index => $block) {
            $new_blocks[$index] = Utils::blockFromArray((array)$block);
        }

        $chain->blocks = $new_blocks;
        return $chain;
    }
}