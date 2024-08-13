<?php

namespace Spatie\HttpLogger;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface LogWriter
{
    public function logRequest(Request $request, Response $response, $startTime);
}
