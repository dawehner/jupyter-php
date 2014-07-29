<?php

/**
 * @file
 * Contains \dawehner\IPythonPhp\MessageHistoryRequest.
 */

namespace dawehner\IPythonPhp;

class MessageHistoryRequest implements MessageInterface {

  public function __construct(Kernel $kernel, $shell_socket) {
    $this->kernel = $kernel;
    $this->shellSocket = $shell_socket;
  }

  public function execute(\stdClass $header, $content) {
    $this->kernel->send(
      $this->shellSocket,
      'history_reply',
      array('history' => array()),
      $header
    );
  }

}

