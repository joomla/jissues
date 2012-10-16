<?php
/// @cond ALL
class LuminousFileSystemCache extends LuminousCache {

  private $dir = null;
  private $path = null;

  public function __construct($id) {
    $this->dir = luminous::root() . '/cache/';
    $this->path = rtrim($this->dir, '/') . '/' . $id;
    parent::__construct($id);
  }

  protected function _log_error($file, $msg) {
    $this->log_error( str_replace('%s', "\"$file\"", $msg) . "\n"
      . "File exists: " . var_export(file_exists($file), true) . "\n"
      . "Readable?: " . var_export(is_readable($file), true) . "\n"
      . "Writable?: "  . var_export(is_writable($file), true) . "\n"
      . (!file_exists($this->dir)?
        "Your cache dir (\"{$this->dir}\") does not exist! \n" : '' )
      . (file_exists($this->dir) && !is_writable($this->dir)?
        "Your cache dir (\"{$this->dir}\") is not writable! \n" : '' )
    );
  }

  protected function _create() {
    if (!@mkdir($this->dir, 0777, true) && !is_dir($this->dir)) {
     $this->_log_error($this->dir, "%s does not exist, and cannot create.");
      return false;
    }
    return true;
  }

  protected function _update() {
    if (!(@touch($this->path))) {
      $this->_log_error($this->path, "Failed to update (touch) %s");
    }
  }

  protected function _read() {
    $contents = false;
    if (file_exists($this->path)) {
      $contents = @file_get_contents($this->path);
      if ($contents === false)
        $this->_log_error($this->path, 'Failed to read %s"');
    }
    return $contents;
  }

  protected function _write($data) {
    if (@file_put_contents($this->path, $data, LOCK_EX) === false) {
      $this->_log_error($this->path, "Error writing to %s");
    }
  }

  protected function _purge() {
    if ($this->timeout <= 0) return;
    $purge_file = $this->dir . '/.purgedata';
    if (!file_exists($purge_file)) @touch($purge_file);
    $last = 0;
    $fh = @fopen($purge_file, 'r+');
    if (!$fh) {
      $this->_log_error($purge_file,
        "Error encountered opening %s for writing");
      return;
    }
    $time = time();
    if (flock($fh, LOCK_EX)) {
      if (filesize($purge_file))
        $last = (int)fread($fh, filesize($purge_file));
      else $last = 0;
      if ($time - $last > 60*60*24) {
        rewind($fh);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, $time);
        foreach(scandir($this->dir) as $file) {
          if ($file[0] === '.') continue;
          $mtime = filemtime($this->dir . '/' . $file);
          if ($time - $mtime > $this->timeout)
            unlink($this->dir . '/' . $file);
        }
      }
      flock($fh, LOCK_UN);
      fclose($fh);
    }
  }
}
/// @endcond