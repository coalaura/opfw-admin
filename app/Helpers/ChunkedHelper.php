<?php

namespace App\Helpers;

class ChunkedHelper
{
    public function __construct()
    {
        // Disable PHP's output buffering so we can send progress updates to the client
        while (ob_get_level() > 0) ob_end_clean();

        ob_implicit_flush(true);

        // Set headers
        header('Content-Type: text/plain');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        //header('Transfer-Encoding: chunked');
        header('Content-Encoding: chunked');
    }

    public function sendChunk($chunk)
    {
        $chunk = json_encode($chunk);

        // Send the chunk
        echo $chunk . "\r\n\r\n";

        // Flush the buffer
        flush();
        flush();
    }

    public function end()
    {
        echo "0\r\n\r\n";

        // Flush the buffer
        flush();
        flush();
    }
}