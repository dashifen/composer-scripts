<?php

namespace Dashifen\ComposerScripts;

use Dashifen\Exception\Exception;

class ComposerScriptException extends Exception
{
  public const UNABLE_TO_FIND_ROOT_DIR = 1;
  public const NOT_A_DIRECTORY         = 2;
}