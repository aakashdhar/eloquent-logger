<?php
namespace WcgPackage\EloquentEventLogger;

use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
class EloquentEventServiceProvider extends ServiceProvider
{
    private $lastLoggedEvent = '';

    /**
     * Register a new user.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the application.
     *
     * This method is called when the application boots up. It iterates through all the model files in the "Models" directory and
     * logs any updates, creations, and deletions made to the models
     *
     *
     * @return void
     */
    public function boot()
    {
        foreach (glob(app_path('Models') . '/*.php') as $model) {
            $class = "App\\Models\\" . basename(str_replace('.php', '', $model));
            $this->logModelUpdates($class);
            $this->logModelCreations($class);
            $this->logModelDeletions($class);
        }
    }

    /**
     * Log model updates.
     *
     * @param string $class The name of the model class.
     * @return void
     */
    private function logModelUpdates($class)
    {
        $class::updating(function($modelInstance) {
            try {
                $originalValues = $modelInstance->getOriginal();
                $dirtyFields = $modelInstance->getDirty();
                $originalValues = array_filter($originalValues, function($key) use($dirtyFields) {
                    return array_key_exists($key, $dirtyFields);
                }, ARRAY_FILTER_USE_KEY);
                // Log old values
                $this->logEvent($modelInstance, ' updating. Old values: '.json_encode($originalValues));
                // Log new values
                $this->logEvent($modelInstance, 'New values: '.json_encode($dirtyFields));
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });

        $class::updated(function ($modelInstance) {
            try {
                $this->logEvent($modelInstance, ' has been updated.');
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });
    }

    /**
     * Log model creations.
     *
     * @param string $class The class name of the model.
     * @return void
     */
    private function logModelCreations($class)
    {
        $class::creating(function($modelInstance) {
            try {
                $this->logEvent($modelInstance, ' is being created', $modelInstance->getAttributes());
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });

        $class::created(function($modelInstance) {
            try {
                $this->logEvent($modelInstance, ' has been created.');
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });
    }

    /**
     * Log model deletions.
     *
     * @param string $class The name of the model class.
     * @return void
     */
    private function logModelDeletions($class)
    {
        $class::deleting(function($modelInstance) {
            try {
                $this->logEvent($modelInstance, ' is being deleted.');
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });

        $class::deleted(function($modelInstance) {
            try {
                $this->logEvent($modelInstance, ' has been deleted.');
            } catch (\Exception $e) {
                $this->logError($modelInstance, $e);
            }
        });
    }

    /**
     * Get a logger instance.
     *
     * @param string $modelName The name of the model.
     * @param int $level The log level (default: Logger::INFO).
     * @return Logger The initialized logger instance.
     */
    private function getLogger($modelName, $level = Logger::INFO)
    {
        $now = new \DateTime();
        $logger = new Logger('ModelLogger');
        $formatter = new LineFormatter(null, null, false, true);
        // Update the log level to the provided level
        $stream = new StreamHandler(storage_path('logs/' . $now->format('Y-m-d') . '/'.$modelName.'/'.$modelName.'-'.$now->format('Y-m-d').'.log'), $level);
        $stream->setFormatter($formatter);
        $logger->pushHandler(new FingersCrossedHandler($stream, new ErrorLevelActivationStrategy($level)));
        return $logger;
    }

    /**
     * Logs an event message with optional data.
     *
     * @param mixed $modelInstance The instance of the model for which the event is being logged.
     * @param string $message The message to be logged.
     * @param mixed|null $data (optional) Additional data to be logged. Defaults to null.
     *
     * @return void
     */
    private function logEvent($modelInstance, $message, $data = null)
    {
        $modelName = class_basename(get_class($modelInstance));
        $logger = $this->getLogger($modelName);
        if (strpos($this->lastLoggedEvent, 'has been') !== false) {
            $logger->info("--------------------------------------------------");
        }
        if ($data !== null) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
            $this->lastLoggedEvent = "{$modelName} model {$message} with data: {$data}.";
        } else {
            $this->lastLoggedEvent = "{$modelName} model {$message}";
        }
        $logger->info($this->lastLoggedEvent);
        if (strpos($this->lastLoggedEvent, 'has been') !== false) {
            $logger->info("--------------------------------------------------");
        }
    }

    /**
     * Logs an error with an exception message and additional exception data.
     *
     * @param mixed $modelInstance The instance of the model for which the error occurred.
     * @param \Exception $e The exception that occurred.
     *
     * @return void
     */
    private function logError($modelInstance, \Exception $e)
    {
        $modelName = class_basename(get_class($modelInstance));
        $logger = $this->getLogger($modelName, Logger::ERROR);
        $logger->error('Error: ' . $e->getMessage(), ['exception' => $e]);
    }

}
