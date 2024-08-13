<?php

namespace Spatie\HttpLogger;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\HttpLogger\Supports\JsonLogFileWriter;

class DefaultLogWriter implements LogWriter
{
    const FORMAT_JSON = 'json';

    protected $sanitizer;

    public function logRequest(Request $request, Response $response, $startTime)
    {
        $message = $this->getMessage($request, $response, $startTime);

        if (self::FORMAT_JSON == 'json' && isset($message)) {
            (new JsonLogFileWriter)->write($this->filePath(), $message);
        }
    }

    public function filePath()
    {
        $baseDir = storage_path('logs/' .config('http-logger.log_level', 'info'). '/' .date('Y-m'). '//http_requests/');
    
        if (!File::exists($baseDir)) {
            File::makeDirectory($baseDir, 0777, true);
        }
        
        $filePath = $baseDir . '/' . date('Y-m-d') . '.log';
    
        return $filePath;
    }

    public function getMessage(Request $request, Response $response, $startTime = 0)
    {
        $files = (new Collection(iterator_to_array($request->files)))
            ->map([$this, 'flatFiles'])
            ->flatten();
    
        $responseTime = microtime(true) - $startTime;
    
        return [
            'timestamp' => gmdate('c'),
            'request' => [
                'method' => strtoupper($request->getMethod()),
                'uri' => $request->getPathInfo(),
                'full_url' => $request->fullUrl(),
                'query_parameters' => $request->query(),
                
                'body' => $request->except(config('http-logger.except')),
                'headers' => $this->getSanitizer()->clean(
                    $request->headers->all(),
                    config('http-logger.sanitize_headers')
                ),
                'files' => $files,
            ],
            'client' => [
                'client_ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'authenticated_user' => optional($request->user())->only(['id', 'email']),
            ],
            'response' => [ 
                'response_status' => $response->status(),
                'response_headers' => $response->headers->all(),
                'response_time' => $responseTime,
            ],
            'version' => [
                'release_version' => config('http-logger.release_version'), // String
                'commit_hash' => config('http-logger.commit_hash'),         // String
                'commit_author' => config('http-logger.commit_author'),     // String
                'commit_date' => config('http-logger.commit_date'),         // String
                'author' => config('http-logger.author'), 
            ]
        ];
    }

    public function flatFiles($file)
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }
        if (is_array($file)) {
            return array_map([$this, 'flatFiles'], $file);
        }

        return (string) $file;
    }

    protected function getSanitizer()
    {
        if (! $this->sanitizer instanceof Sanitizer) {
            $this->sanitizer = new Sanitizer();
        }

        return $this->sanitizer;
    }
}
