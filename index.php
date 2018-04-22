<?php

require_once 'Command.php';
require_once 'HttpServer.php';

$argv = $_SERVER['argv'];

$commandList = [
    Command::START,
    Command::STOP,
    Command::RELOAD,
    Command::HELP,
];


$command = isset($argv[1]) && $argv[1] ? $argv[1] : '';

var_dump($commandList);

$msg = '';
$httpServer = new HttpServer();

switch ($command) {
    case Command::START:
        $msg = 'Starting...';
        $httpServer->start();
        break;
    case Command::STOP:
        $msg = 'Stopping...';
        $httpServer->stop();
        break;
    case Command::RELOAD:
        $msg = 'Reboot...';
        $httpServer->reload();
        break;
    case Command::HELP:
    default:
        $msg = 'help...';

}
var_dump($msg);
