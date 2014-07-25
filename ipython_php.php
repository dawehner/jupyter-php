<?php

use dawehner\IPythonPhp\Kernel;
use dawehner\IPythonPhp\MessageExecuteRequest;
use dawehner\IPythonPhp\MessageKernelInfoRequest;
use dawehner\IPythonPhp\MessageShutdownRequest;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;

require 'vendor/autoload.php';

// Setup the connection strings we want to use, coming from the kernel argument.
$kernel = new Kernel();
if (isset($argv[1])) {
  $config = json_decode(file_get_contents($argv[1]));
  $ip = $config->ip;
  $transport = $config->transport;
  $stdin_port = $config->stdin_port;
  $control_port = $config->control_port;
  $hb_port = $config->hb_port;
  $shell_port = $config->shell_port;
  $iopub_port = $config->iopub_port;

  $kernel->setSecureKey($config->key);
  $kernel->setSignatureScheme($config->signature_scheme);
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
$iopub_connection = $connection . $iopub_port;


$session_id = Uuid::uuid4();
$kernel->setEngineId(Uuid::uuid4());

if (!isset(Kernel::getSignatureSchemes()[$kernel->getSignatureScheme()])) {
  trigger_error("invalid signature scheme:{$kernel->getSignatureScheme()}\n");
  exit;
}

// Open all needed sockets.
$context = new ZMQContext();
$loop = React\EventLoop\Factory::create();
$context = new React\ZMQ\Context($loop);

/** @var $hb_socket \ZMQSocket */
$hb_socket = $context->getSocket(ZMQ::SOCKET_REP);
$hb_socket->bind($hb_connection);

/** @var $iopub_socket \ZMQSocket */
$iopub_socket = $context->getSocket(ZMQ::SOCKET_PUB);
$iopub_socket->bind($iopub_connection);

/** @var $control_socket \ZMQSocket */
$control_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$control_socket->bind($control_connection);

/** @var $stdin_socket \ZMQSocket */
$stdin_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$stdin_socket->bind($stdin_connection);

/** @var $shell_socket \ZMQSocket */
$shell_socket = $context->getSocket(ZMQ::SOCKET_ROUTER);
$shell_socket->bind($shell_connection);

// Register handlers.
$hb_socket->on(
  'error',
  function ($e) {
  }
);

// The heartbeat socket just sends its recieved data to tell ipython, that it
// still lives.
$hb_socket->on(
  'messages',
  function ($msg) {
  }
);

$event_dispatcher = new EventDispatcher();

$message_execute_request = new MessageExecuteRequest($kernel, $iopub_socket, $shell_socket);
$message_kernel_info_request = new MessageKernelInfoRequest($kernel, $shell_socket);
$message_shutdown_request = new MessageShutdownRequest($kernel, $shell_socket);

$shell_socket->on(
  'messages',
  function ($messages) use ($shell_socket, $iopub_socket, $kernel, $message_execute_request, $message_kernel_info_request, $message_shutdown_request) {
    list($zmq_id, $delim, $hmac, $header, $parent_header, $metadata, $content) = $messages;

    $header = json_decode($header);
    $content = json_decode($content);

    if ($header->msg_type == 'kernel_info_request') {
      $message_kernel_info_request->execute($header, $content);
    }
    elseif ($header->msg_type == 'execute_request') {
      $message_execute_request->execute($header, $content);
    }
    elseif ($header->msg_type == 'history_request') {
      trigger_error('unhandled history request');
    }
    elseif ($header->msg_type == 'shutdown_request') {
      $message_shutdown_request->execute($header, $content);
    }
    else {
      trigger_error('unknown msg_type: ' . $header->msg_type);
    }
  }
);

$iopub_socket->on(
  'messages',
  function ($messages) {
  }
);

$loop->run();
