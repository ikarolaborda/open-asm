<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggingService implements LoggerInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel(config('logging.default'));
    }

    /**
     * System is unusable.
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Add additional context for better logging
        $enhancedContext = $this->enhanceContext($context);

        $this->logger->log($level, $message, $enhancedContext);
    }

    /**
     * Log API requests with structured data.
     */
    public function logApiRequest(string $method, string $path, array $context = []): void
    {
        $this->info('API Request', [
            'type' => 'api_request',
            'method' => $method,
            'path' => $path,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            ...$context,
        ]);
    }

    /**
     * Log API responses with structured data.
     */
    public function logApiResponse(int $statusCode, float $responseTime, array $context = []): void
    {
        $level = $statusCode >= 500 ? LogLevel::ERROR : ($statusCode >= 400 ? LogLevel::WARNING : LogLevel::INFO);

        $this->log($level, 'API Response', [
            'type' => 'api_response',
            'status_code' => $statusCode,
            'response_time_ms' => round($responseTime * 1000, 2),
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            ...$context,
        ]);
    }

    /**
     * Log business operations with structured data.
     */
    public function logBusinessOperation(string $operation, string $entity, string $entityId, array $context = []): void
    {
        $this->info('Business Operation', [
            'type' => 'business_operation',
            'operation' => $operation,
            'entity' => $entity,
            'entity_id' => $entityId,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            ...$context,
        ]);
    }

    /**
     * Log security events with structured data.
     */
    public function logSecurityEvent(string $event, string $severity = 'medium', array $context = []): void
    {
        $level = match ($severity) {
            'critical', 'high' => LogLevel::CRITICAL,
            'medium' => LogLevel::WARNING,
            'low' => LogLevel::NOTICE,
            default => LogLevel::INFO,
        };

        $this->log($level, 'Security Event', [
            'type' => 'security_event',
            'event' => $event,
            'severity' => $severity,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            ...$context,
        ]);
    }

    /**
     * Log performance metrics with structured data.
     */
    public function logPerformanceMetric(string $metric, float $value, string $unit = 'ms', array $context = []): void
    {
        $this->info('Performance Metric', [
            'type' => 'performance_metric',
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            ...$context,
        ]);
    }

    /**
     * Enhance context with additional information.
     */
    private function enhanceContext(array $context): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'application' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'request_id' => request()->header('X-Request-ID', uniqid()),
            ...$context,
        ];
    }

    /**
     * Log database queries for debugging.
     */
    public function logDatabaseQuery(string $sql, array $bindings, float $time): void
    {
        if (app()->environment(['local', 'testing']) || config('logging.log_queries', false)) {
            $this->debug('Database Query', [
                'type' => 'database_query',
                'sql' => $sql,
                'bindings' => $bindings,
                'execution_time_ms' => round($time, 2),
            ]);
        }
    }

    /**
     * Log validation errors with structured data.
     */
    public function logValidationError(array $errors, array $context = []): void
    {
        $this->warning('Validation Error', [
            'type' => 'validation_error',
            'errors' => $errors,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()?->organization_id,
            ...$context,
        ]);
    }
}
