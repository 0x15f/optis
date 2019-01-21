<?php
namespace optis\thread;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Lynn Digital
 * @link https://lynndigital.com
*/

use Composer\Autoload\ClassLoader;

class RunThread extends \Thread {

    /**@var ClassLoader*/
    private $loader;

    /**
     * RunThread constructor.
     * @param ClassLoader $loader
     */
    public function __construct(ClassLoader $loader) {
        $this->loader = $loader;
    }

    public function run() {
        $this->loader->register();
    }
}