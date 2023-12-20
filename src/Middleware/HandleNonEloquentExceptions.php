<?php
namespace WcgPackage\EloquentEventLogger\Middleware;

use Closure;
use Throwable;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Formatter\LineFormatter;

class HandleNonEloquentExceptions
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            $logger = $this->getLogger('Exception', Logger::ERROR);
            $data = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
            $logger->error($e->getMessage(), $data);

            throw $e;
        }
    }

    private function getLogger($modelName, $level = Logger::INFO)
    {
        $now = new \DateTime();
        $logger = new Logger('ModelLogger');
        $formatter = new LineFormatter(null, null, false, true); // second parameter is the date format

        if ($modelName == 'Exception') {
            $filename = 'noneloquent';
            $modelPath = $filename;
        } else {
            $filename = $modelName.'-'.$now->format('Y-m-d');
            $modelPath = $modelName.'/'.$filename;
        }

        $stream = new StreamHandler(storage_path('logs/' . $now->format('Y-m-d') . '/'.$modelPath.'.log'), $level);
        $stream->setFormatter($formatter);
        $logger->pushHandler(new FingersCrossedHandler($stream, new ErrorLevelActivationStrategy($level)));

        return $logger;
    }
}