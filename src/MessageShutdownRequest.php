<?php

/**
 * @file
 * Contains \dawehner\IPythonPhp\MessageShutdownRequest.
 */

namespace dawehner\IPythonPhp;

class MessageShutdownRequest implements MessageInterface {

  public function __construct(Kernel $kernel, $shell_socket) {
    $this->kernel = $kernel;
    $this->shellSocket = $shell_socket;
  }

  public function execute(\stdClass $header, $content) {
    $this->kernel->send(
      $this->shellSocket,
      'shutdown_reply',
      array('restart' => $content->restart),
      $header
    );
    // @todo is there a nice way to shutdown?
    exit;
  }

}
