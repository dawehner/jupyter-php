<?php

namespace dawehner\IPythonPhp;

interface MessageInterface {

  public function execute(\stdClass $header, $content);

}
