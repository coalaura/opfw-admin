<?php

namespace App\Helpers;

class Mutex
{
    private $pointer;

    private bool $locked = false;

    private string $path;

    public function __construct(string $name)
    {
        $this->path = storage_path('locks/' . $name . '.lock');

        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function lock(): bool
    {
        if ($this->locked) {
            return false;
        }

        // Mutex stale?
        if (file_exists($this->path) && filemtime($this->path) > time() + 10) {
            unlink($this->path);
        }

        $this->pointer = fopen($this->path, 'c+');

        if (flock($this->pointer, LOCK_EX)) {
            $this->locked = true;

            touch($this->path);

            return true;
        }

        return false;
    }

    public function unlock()
    {
        if (!$this->locked) {
            return;
        }

        flock($this->pointer, LOCK_UN);
        fclose($this->pointer);

        $this->locked = false;
    }
}