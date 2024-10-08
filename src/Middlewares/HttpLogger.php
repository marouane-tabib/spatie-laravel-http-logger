<?php

namespace Spatie\HttpLogger\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Spatie\HttpLogger\LogProfile;
use Spatie\HttpLogger\LogWriter;

class HttpLogger
{
    protected $logProfile;

    protected $logWriter;

    public function __construct(LogProfile $logProfile, LogWriter $logWriter)
    {
        $this->logProfile = $logProfile;
        $this->logWriter = $logWriter;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->logProfile->shouldLogRequest($request)) {
            $startTime = microtime(true);
            $response = $next($request);
            $this->logWriter->logRequest($request, $response, $startTime);
            
            return $response;
        }

        return $next($request);
    }
}
