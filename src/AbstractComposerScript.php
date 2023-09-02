<?php

namespace Dashifen\ComposerScripts;

use DirectoryIterator;

abstract class AbstractComposerScript
{
  protected string $root;
  
  /**
   * run
   *
   * Use this abstract function to define how your script is run.
   *
   * @return int
   */
  abstract public function run(): int;
  
  /**
   * findRootDirectory
   *
   * Finds the root directory for a project by looking for its composer.json
   * file.
   *
   * @return string
   * @throws ComposerScriptException
   */
  protected function findRootDirectory(): string
  {
    if (isset($this->root)) {
      return $this->root;
    }
    
    $count = 0;
    $dir = getcwd();
    while (++$count <= 100) {
      $files = new DirectoryIterator($dir);
      foreach ($files as $file) {
        if ($file->getFilename() === 'composer.json') {
          
          // if we've found a folder with a composer.json file in it, then we
          // assume that's our root.  since this library is intended for use in
          // the root composer project, that should be the composer.json for
          // the project itself and not for a dependency.
          
          $this->root = join('/', explode('\\', $dir));
          return $this->root;
        }
      }
      
      $parent = realpath($dir . '/..');
      if (!$parent || $parent === $dir) {
        
        // this happens when we're at the top-level of the file system.  in
        // some cases, realpath returns false, but on Windows it seems to just
        // return the drive letter over and over again.  when this happens, all
        // we can do is quit.
        
        break;
      }
      
      $dir = $parent;
    }
    
    throw new ComposerScriptException(
      'Unable to find root directory for project.',
      ComposerScriptException::UNABLE_TO_FIND_ROOT_DIR
    );
  }
  
  /**
   * emptyDirectory
   *
   * Given a directory's name, deletes all the files and folders within it.
   *
   * @param string $directory
   *
   * @return bool
   * @throws ComposerScriptException
   */
  protected function emptyDirectory(string $directory): bool
  {
    if (!is_dir($directory)) {
      throw new ComposerScriptException(
        'Cannot empty; "' . $directory . '" is not a directory.',
        ComposerScriptException::NOT_A_DIRECTORY
      );
    }
    
    return !str_starts_with(strtolower(PHP_OS), 'win')
      ? (bool) shell_exec(sprintf('rm -rf "%s"', $directory))
      : $this->emptyDirectoryWindows($directory);
  }
  
  /**
   * emptyDirectoryWindows
   *
   * Focuses on removing a directory in a Windows environment where rm -rf
   * isn't available.
   *
   * @param string $directory
   *
   * @return bool
   */
  protected function emptyDirectoryWindows(string $directory): bool
  {
    chdir(dirname($directory));
    $clone = basename($directory);
    
    // robocopy has a /purge option that removes anything in the destination
    // folder that's not in the source.  so, we create an empty directory, copy
    // it over the clone with the purge option, then remove the now superfluous
    // empty directory.  this is much, much faster than recursively iterating
    // through the file system to delete files and then deleting directories
    // once they're empty.
    
    mkdir('empty');
    $success = (bool) shell_exec('robocopy empty ' . $clone . ' /purge');
    rmdir('empty');
    rmdir($clone);
    
    return $success;
  }
}