<?php 

namespace Spatie\HttpLogger\Supports;

use Haruncpi\QueryLog\Contracts\FileWritable;

class JsonLogFileWriter implements FileWritable
{
    const QUERY_LOG_FORMAT_FLAG = 0;

    public function write($file_path, $data)
    {
        if (file_exists($file_path) && filesize($file_path) > 0) {
            $existingData = json_decode(file_get_contents($file_path), true);

            $existingData[] = $data;
        } else {
            $existingData = [$data];
        }
        $flag = env('QUERY_LOG_FORMAT_FLAG', self::QUERY_LOG_FORMAT_FLAG);
        
        file_put_contents($file_path, json_encode($existingData, $flag));
    }
}