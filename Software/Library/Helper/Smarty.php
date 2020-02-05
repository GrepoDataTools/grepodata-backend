<?php

namespace Grepodata\Library\Helper;


class Smarty extends \Smarty
{
  function __construct()
  {
    parent::__construct();

    $this->setTemplateDir(SMARTY_TEMPLATE_DIR);
    $this->setCompileDir(SMARTY_COMPILE_DIR);
    $this->setCacheDir(SMARTY_CACHE_DIR);
  }
}