<?php
require 'vendor/autoload.php';

if (isset($argv[1])) {
  $config = json_decode(file_get_contents($argv[1]));
  file_put_contents("/tmp/debug", print_r($config, TRUE), FILE_APPEND);

  $ip = $config->ip;
  $shell_port = $config->shell_port;
  $iopub_port = $config->iopub_port;
  $hb_port = $config->hb_port;
}
else {
  $ip = '127.0.0.1';
  $shell_port = 51000;
  $iopub_port = 51001;
  $hb_port = 51002;
}

$connection = "tcp://$ip:";
$shell_connection = $connection . $shell_port;
$pub_connection = $connection . $iopub_port;
$hb_connection = $connection . $hb_port;

$loop = React\EventLoop\Factory::create();

$context = new React\ZMQ\Context($loop);

$hb_socket = $context->getSocket(ZMQ::SOCKET_REP);
$hb_socket->bind($hb_connection);

$hb_socket->on('error', function ($e) {
  var_dump($e->getMessage());
  file_put_contents("/tmp/debug.txt", $e, FILE_APPEND);
});

$hb_socket->on('message', function ($msg) {
  file_put_contents("/tmp/debug.txt", $msg, FILE_APPEND);
  $reply_socket->send($msg);
  $hb_socket->send($msg);
});

$pub_socket = $context->getSocket(ZMQ::SOCKET_PUB);
$pub_socket->bind($pub_connection);

$reply_socket = $context->getSocket(ZMQ::SOCKET_XREP);
$reply_socket->bind($shell_connection);

$reply_socket->on('message', function($data) {
});

$loop->run();
