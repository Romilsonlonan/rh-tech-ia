<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

class LogStreamController extends Controller
{
    public function stream(Request $request): Response
    {
        $channel = $request->get('channel', 'single');

        return response()->stream(function () use ($channel) {
            $logFile = storage_path('logs/laravel.log');

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $lastModified = file_exists($logFile) ? filemtime($logFile) : 0;
                $currentModified = filemtime($logFile);

                if ($lastModified !== $currentModified) {
                    $logs = $this->getRecentLogs($logFile, 50);
                    $data = json_encode([
                        'type' => 'logs',
                        'logs' => $logs,
                        'timestamp' => now()->toISOString(),
                    ]);

                    echo "data: {$data}\n\n";
                }

                $pipelineStatus = $this->getPipelineStatus();
                if ($pipelineStatus) {
                    $data = json_encode($pipelineStatus);
                    echo "data: {$data}\n\n";
                }

                ob_flush();
                flush();

                usleep(500000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function log(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'level' => 'required|in:debug,info,notice,warning,error,critical',
            'message' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $level = $request->get('level', 'info');
        $message = $request->get('message');
        $context = $request->get('context', []);

        Log::channel('single')->{$level}($message, $context);

        return response()->json([
            'success' => true,
            'message' => 'Log recorded',
            'level' => $level,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function pipelineStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'status' => 'required|in:started,running,success,warning,failed,completed',
            'phase' => 'required|in:INICIO,MEIO,FIM',
            'step' => 'required|string',
            'message' => 'nullable|string',
            'details' => 'nullable|array',
        ]);

        $statusData = [
            'type' => 'pipeline',
            'status' => $request->get('status'),
            'phase' => $request->get('phase'),
            'step' => $request->get('step'),
            'message' => $request->get('message'),
            'details' => $request->get('details'),
            'timestamp' => now()->toISOString(),
        ];

        $statusFile = storage_path('logs/pipeline_status.json');
        file_put_contents($statusFile, json_encode($statusData));

        Log::channel('single')->info('Pipeline status update', $statusData);

        return response()->json([
            'success' => true,
            'status' => $statusData,
        ]);
    }

    public function clearLogs(Request $request): \Illuminate\Http\JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        $statusFile = storage_path('logs/pipeline_status.json');
        if (file_exists($statusFile)) {
            unlink($statusFile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logs cleared',
        ]);
    }

    private function getRecentLogs(string $logFile, int $lines = 50): array
    {
        if (!file_exists($logFile)) {
            return [];
        }

        $file = new \SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;

        $startLine = max(0, $totalLines - $lines);
        $logs = [];

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if ($line) {
                $logs[] = $this->parseLogLine($line);
            }
            $file->next();
        }

        return $logs;
    }

    private function parseLogLine(string $line): array
    {
        $pattern = '/\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(\w+)\.(\w+):\s+(.*)/';

        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => strtolower($matches[2]),
                'channel' => $matches[3],
                'message' => $matches[4],
                'raw' => $line,
            ];
        }

        return [
            'timestamp' => now()->toISOString(),
            'level' => 'info',
            'channel' => 'unknown',
            'message' => $line,
            'raw' => $line,
        ];
    }

    private function getPipelineStatus(): ?array
    {
        $statusFile = storage_path('logs/pipeline_status.json');

        if (!file_exists($statusFile)) {
            return null;
        }

        $content = file_get_contents($statusFile);
        $status = json_decode($content, true);

        if ($status && isset($status['auto_clear']) && $status['auto_clear']) {
            unlink($statusFile);
        }

        return $status;
    }
}
