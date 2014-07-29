<?php

/**
 * @file
 * Contains \dawehner\IPythonPhp\MessageKernelInfoRequest.
 */

namespace dawehner\IPythonPhp;

class MessageKernelInfoRequest implements MessageInterface {

  /**
   * @var Kernel
   */
  protected $kernel;

  protected $shellSocket;

  public function __construct($kernel, $shellSocket) {
    $this->kernel = $kernel;
    $this->shellSocket = $shellSocket;
  }

  public function execute(\stdClass $header, $content) {
    $this->kernel->send(
      $this->shellSocket,
      'kernel_info_reply',
      Kernel::getMessageKernelInfo(),
      $header
    );
  }

}
