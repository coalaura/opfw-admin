<?php

namespace App;

use Illuminate\Http\RedirectResponse;

class OPFWResponse
{
    /**
     * Success or not
     * @var bool
     */
    public bool $status = false;

    /**
     * The error or success message
     * @var string
     */
    public string $message = '';

    /**
     * The data
     * @var mixed|null
     */
    public $data = null;

    /**
     * If the request was not executed
     * @var bool
     */
    public $notExecuted = false;

    public static function didNotExecute(): OPFWResponse
    {
        $response = new OPFWResponse(false, 'The request was not executed');
        $response->notExecuted = true;

        return $response;
    }

    /**
     * OPFWResponse constructor.
     * @param bool $status
     * @param string $message
     * @param mixed|null $data
     */
    public function __construct(bool $status, string $message, $data = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        if ($this->status) {
            return back()->with('success', $this->message);
        }

        return back()->with('error', $this->message);
    }
}
