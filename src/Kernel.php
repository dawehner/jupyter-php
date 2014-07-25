<?php

/**
 * @file
 * Contains \dawehner\IpythonPhp\Kernel.
 */

namespace dawehner\IPythonPhp;

class Kernel
{

    public static function getMessageKernelInfo()
    {
        return [
          'protocol_version' => '4.0.0',
          'implementation' => 'ipython',
          'implementation_version' => '1.0.0',
          'language' => 'php',
          'language_version' => '5.4.0',
          'banner' => 'Hey ho, here is php.',
        ];
    }

}

