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

use Composer\Autoload\ClassLoader;

use optis\Optis;
use optis\utils\Utils;

use Psr\Http\Message\ServerRequestInterface;

use React\EventLoop\Factory;
use React\Http\Response;

class Server extends \Thread {

    /**@var Optis*/
    public $optis;

    /**@var ClassLoader*/
    public $loader;

    /**@var string*/
    public $address;

    /**@var int*/
    public $port;

    /**
     * @param Optis $optis
     * @param string $address
     * @param int $port
     */
    public function __construct(Optis $optis, $loader, string $address, int $port) {
        $this->optis = $optis;
        $this->loader = $loader;
        $this->address = $address;
        $this->port = $port;
    }

    public function run() {
        $this->loader->register();

        $loop = Factory::create();
        //todo: move to another thread
        //$loop->addPeriodicTimer(10, [$this->optis->client, 'sync']);

        $server = new \React\Http\Server([$this, 'process']);
        $socket = new \React\Socket\Server($this->address . ':' . $this->port, $loop);
        $server->listen($socket);
        $loop->run();
    }

    public function process(ServerRequestInterface $request) {
        $optis = $this->optis;

        $code = 200;
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $response = '';

        $path = explode('/', rtrim(ltrim($request->getUri()->getPath(), '/'), '/'));
        //todo: fix this hack, reactphp doesnt parse JSON apparently and refuses to allow access to the raw body
        $body = @json_decode((array_keys($request->getParsedBody()))[0], true);
        switch($path[0]) {
            case 'chain':
                switch($path[1]) {
                    case 'fetch':
                        $response = json_encode(['blocks' => $optis->chain->blocks]);
                    break;
                    case 'update':
                        $chain = Utils::chainFromArray($body, $optis->chain->block_store);
                        $better = Utils::getBetterChain($optis->chain, [$optis->chain, $chain]);
                        $optis->chain = $better;

                        $optis->client->syncChain($better);
                    break;
                }
                break;
            case 'peer':
                switch($path[1]) {
                    case 'add':
                        $this->optis->client->peers[] = @$body['address'];
                    break;
                    case 'fetch':
                        $response = json_encode(['peers' => $this->optis->client->peers]);
                    break;
                }
            break;
            case 'block':
                switch($path[1]) {
                    case 'add':
                        $block = Utils::blockFromArray($body);
                        $valid = Utils::isValidBlock($block, $optis->chain->blocks[$block->index - 1]);
                        if($valid) {
                            $optis->chain->addBlock($block);
                            var_dump($optis->chain->blocks);
                        }
                    break;
                }
            break;
        }

        return new Response(
            $code,
            $headers,
            $response
        );
    }
}