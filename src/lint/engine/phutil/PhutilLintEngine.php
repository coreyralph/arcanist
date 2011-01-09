<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class PhutilLintEngine extends ArcanistLintEngine {

  public function buildLinters() {
    $linters = array();

    $paths = $this->getPaths();

    // This needs to go first so that changes to generated files cause module
    // linting. This linter also operates on removed files, because removing
    // a file changes the static properties of a module.
    $module_linter = new ArcanistPhutilModuleLinter();
    $linters[] = $module_linter;
    foreach ($paths as $path) {
      $module_linter->addPath($path);
    }

    // Remaining lint engines operate on file contents and ignore removed
    // files.
    foreach ($paths as $key => $path) {
      if (!$this->pathExists($path)) {
        unset($paths[$key]);
      }
    }

    $generated_linter = new ArcanistGeneratedLinter();
    $linters[] = $generated_linter;

    $text_linter = new ArcanistTextLinter();
    $linters[] = $text_linter;
    foreach ($paths as $path) {
      $is_text = false;
      if (preg_match('/\.php$/', $path)) {
        $is_text = true;
      }
      if ($is_text) {
        $generated_linter->addPath($path);
        $generated_linter->addData($path, $this->loadData($path));

        $text_linter->addPath($path);
        $text_linter->addData($path, $this->loadData($path));
      }
    }

    $name_linter = new ArcanistFilenameLinter();
    $linters[] = $name_linter;
    foreach ($paths as $path) {
      $name_linter->addPath($path);
    }

    $xhpast_linter = new ArcanistXHPASTLinter();
    $license_linter = new ArcanistApacheLicenseLinter();
    $linters[] = $xhpast_linter;
    $linters[] = $license_linter;
    foreach ($paths as $path) {
      if (preg_match('/\.php$/', $path)) {
        $xhpast_linter->addPath($path);
        $xhpast_linter->addData($path, $this->loadData($path));
      }
    }

    foreach ($paths as $path) {
      if (preg_match('/\.(php|cpp|hpp|l|y)$/', $path)) {
        if (!preg_match('@^externals/@', $path)) {
          $license_linter->addPath($path);
          $license_linter->addData($path, $this->loadData($path));
        }
      }
    }

    return $linters;
  }

  public function pathExists($path) {
    $disk_path = $this->getFilePathOnDisk($path);
    return Filesystem::pathExists($disk_path);
  }

}
