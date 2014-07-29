<?php

/**
 * @file
 * Contains \dawehner\IPythonPhp\MessageExecuteRequest.
 */

namespace dawehner\IPythonPhp;

class MessageExecuteRequest implements MessageInterface  {

  /**
   * @var Kernel
   */
  protected $kernel;

  protected $iopubSocket;

  protected $shellSocket;

  public function __construct($kernel, $iopubSocket, $shellSocket) {
    $this->iopubSocket = $iopubSocket;
    $this->kernel = $kernel;
    $this->shellSocket = $shellSocket;
  }

  public function execute(\stdClass $header, $content) {
    $this->kernel->send(
      $this->iopubSocket,
      'status',
      array('execution_state' => 'busy'),
      $header
    );

    $execution_count = isset($content->execution_count) ? $content->execution_count : 0;

    ob_start();
    $result = eval($content->code);
    $std_out = ob_get_contents();
    ob_end_clean();

    $this->kernel->send(
      $this->shellSocket,
      'execute_reply',
      array('status' => 'ok'),
      $header
    );
    $this->kernel->send(
      $this->iopubSocket,
      'stream',
      array('name' => 'stdout', 'data' => $std_out),
      $header
    );
    $this->kernel->send(
      $this->iopubSocket,
      'execute_result',
      array(
        'execution_count' => $execution_count + 1,
        'data' => $result,
        'metadata' => array()
      ),
      $header
    );

    $this->kernel->send(
      $this->iopubSocket,
      'status',
      array('execution_state' => 'idle'),
      $header
    );
  }

}

