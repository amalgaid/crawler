<?php


namespace App\Exceptions;
use Exception;

/**
 * Class SteamParseException. Failed to parse steam DOM or HTML
 * @package App\Exceptions
 */
class SteamParseException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        Log::error($this->getMessage());
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response(...);
    }
}
