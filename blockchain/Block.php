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

class Block {

    /**@var int*/
    public $index;

    /**@var int*/
    public $timestamp;

    /**@var int*/
    public $proof;

    /**@var string*/
    public $data;

    /**@var string*/
    public $previous_hash;

    /**@var string*/
    public $hash;

    /**
     * Block constructor.
     * @param int $index
     * @param int $timestamp
     * @param int $proof
     * @param string $data
     * @param string $previous_hash
     */
    public function __construct(int $index, int $timestamp, int $proof, string $data, string $previous_hash) {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->proof = $proof;
        $this->data = $data;
        $this->previous_hash = $previous_hash;
        $this->hash = $this->generateBlockHash();
    }

    /**
     * @return string
     */
    public function __toString() {
        return json_encode($this);
    }

    /**
     * @return string
     */
    private function generateBlockHash() {
        return hash('sha256',
            $this->index . $this->timestamp . $this->proof . $this->data . $this->previous_hash
        );
    }
}