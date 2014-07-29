Installation
------------

* Install [IPython](http://ipython.org) 2.x
* Install [Composer](https://getcomposer.org/)
* Install the dependencies:
  composer.phar install

* Start a notebook using:
  ipython notebook --KernelManager.kernel_cmd="['php', '/path-to/ipython-php/ipython_php.php', '{connection_file}']"
