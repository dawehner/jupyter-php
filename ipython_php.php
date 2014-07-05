<?php

use Rhumsaa\Uuid\Uuid;

require 'vendor/autoload.php';
require "functions.php";

// @TODO Write actual code, not just pseudo code.

// Setup the connection strings we want to use.
// Either coming from the kernel argument or callback to a default.
if (isset($argv[1])) {
  $config = json_decode(file_get_contents($argv[1]));
  syslog(0, var_export($config, TRUE));
  $ip = $config->ip;
  $transport = $config->transport;
  $stdin_port = $config->stdin_port;
  $control_port = $config->control_port;
  $hb_port = $config->hb_port;
  $shell_port = $config->shell_port;
  $iopub_port = $config->iopub_port;

  $secure_key = $config->key;
  $signature_scheme = $config->signature_scheme;
}
else {
  trigger_error("no config file specified.");
  exit;
}

$connection = "$transport://$ip:";
$stdin_connection = $connection . $stdin_port;
$control_connection = $connection . $control_port;
$hb_connection = $connection . $hb_port;
$shell_connection = $connection . $shell_port;
syslog(0, "shell_connection:" . $shell_connection);
$iopub_connection = $connection . $iopub_port;


$session_id = Uuid::uuid4();
$engine_id = Uuid::uuid4();

$signature_schemes = ['hmac-sha256' => 'sha256'];
if (!isset($signature_schemes[$signature_scheme])) {
  trigger_error("invalid signature scheme:$signature_scheme\n");
  exit;
}

// Open all needed sockets.
$context = new ZMQContext();
$loop = React\EventLoop\Factory::create();
$context = new React\ZMQ\Context($loop);

$hb_socket = $context->getSocket(ZMQ::SOCKET_REP);
$hb_socket->bind($hb_connection);

$iopub_socket = $context->getSocket(ZMQ::SOCKET_PUB);
$iopub_socket->bind($iopub_connection);

$control_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$control_socket->bind($control_connection);

$stdin_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$stdin_socket->bind($stdin_connection);

$shell_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$shell_socket->bind($shell_connection);

// Register handlers.
$hb_socket->on('error', function ($e) {
  var_dump($e->getMessage());
});

// The heartbeat socket just sends its recieved data to tell ipython, that it
// still lives.
$hb_socket->on('messages', function ($msg) {
  syslog(0, 'hb');
  echo sprintf("Received: %s\n", print_r($msg, TRUE));
});

$shell_socket->on('messages', function($messages) use($shell_socket, $iopub_socket) {
  syslog(0, "shell\n");
  list($zmq_id, $delim, $hmac, $header, $parent_header, $metadata, $content) = $messages;

  $header = json_decode($header);
  $content = json_decode($content);

  if ($header->msg_type == 'kernel_info_request') {
    syslog(0, "kernel info request\n");
    send($shell_socket, 'kernel_info_reply', message_kernel_info_reply(), $parent_header);
  }
  elseif ($header->msg_type == 'execute_request') {
    syslog(0, print_r($header, TRUE));
    syslog(0, print_r($content, TRUE));
    syslog(0, "execute request\n");

    send($iopub_socket, 'status', array('execution_state' => 'busy'), $parent_header);

    ob_start();
    $result = eval($content->code);
    $std_out = ob_get_contents();
    ob_end_clean();

    send($shell_socket, 'execute_reply', array('status' => 'ok'), $parent_header);
    send($iopub_socket, 'stream', array('name' => 'stdout', 'data'=> $std_out), $parent_header);
    send($iopub_socket, 'execute_result', array('execution_count' => 0, 'data' => $result, 'metadata' => array()), $parent_header);

    send($iopub_socket, 'status', array('execution_state' => 'idle'), $parent_header);
  }
  elseif ($header->msg_type == 'history_request') {
    trigger_error('unhandled history request');
  }
  else {
    trigger_error('unknown msg_type: ' . $header->msg_type);
  }
});

$iopub_socket->on('messages', function($messages) {
  syslog(0, "iopub\n");
});

$loop->run();
