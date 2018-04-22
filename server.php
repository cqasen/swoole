<?php

require_once 'Command.php';

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
switch ($command) {
    case Command::START:
        $msg = 'Starting...';
        break;
    case Command::STOP:
        $msg = 'Stopping...';
        break;
    case Command::RELOAD:
        $msg = 'Reboot...';
        break;
    case Command::HELP:
    default:
        $msg = 'help...';

}
var_dump($msg);
