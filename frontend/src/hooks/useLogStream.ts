import { useState, useEffect, useCallback, useRef } from 'react';

export interface LogEntry {
  timestamp: string;
  level: 'debug' | 'info' | 'notice' | 'warning' | 'error' | 'critical';
  channel: string;
  message: string;
  raw?: string;
}

export interface PipelineStatus {
  type: 'pipeline';
  status: 'started' | 'running' | 'success' | 'warning' | 'failed' | 'completed';
  phase: 'INICIO' | 'MEIO' | 'FIM';
  step: string;
  message?: string;
  details?: Record<string, unknown>;
  timestamp: string;
}

interface UseLogStreamOptions {
  enabled?: boolean;
  maxLogs?: number;
  onLog?: (log: LogEntry) => void;
  onPipelineStatus?: (status: PipelineStatus) => void;
}

export function useLogStream(options: UseLogStreamOptions = {}) {
  const { enabled = true, maxLogs = 100, onLog, onPipelineStatus } = options;

  const [logs, setLogs] = useState<LogEntry[]>([]);
  const [pipelineStatus, setPipelineStatus] = useState<PipelineStatus | null>(null);
  const [connected, setConnected] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const eventSourceRef = useRef<EventSource | null>(null);
  const reconnectTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const connect = useCallback(() => {
    if (!enabled) return;

    const token = localStorage.getItem('auth_token');
    if (!token) {
      setError('No auth token found');
      return;
    }

    const url = `/api/v1/logs/stream?channel=development`;
    const eventSource = new EventSource(url, {
      withCredentials: true,
    });

    eventSource.onopen = () => {
      setConnected(true);
      setError(null);
    };

    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);

        if (data.type === 'logs' && Array.isArray(data.logs)) {
          setLogs((prev) => {
            const newLogs = [...prev, ...data.logs].slice(-maxLogs);
            return newLogs;
          });
          data.logs.forEach((log: LogEntry) => onLog?.(log));
        } else if (data.type === 'pipeline') {
          setPipelineStatus(data as PipelineStatus);
          onPipelineStatus?.(data as PipelineStatus);
        }
      } catch (e) {
        console.error('Failed to parse SSE data:', e);
      }
    };

    eventSource.onerror = () => {
      setConnected(false);
      eventSource.close();

      reconnectTimeoutRef.current = setTimeout(() => {
        connect();
      }, 3000);
    };

    eventSourceRef.current = eventSource;
  }, [enabled, maxLogs, onLog, onPipelineStatus]);

  const disconnect = useCallback(() => {
    if (reconnectTimeoutRef.current) {
      clearTimeout(reconnectTimeoutRef.current);
    }
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
    setConnected(false);
  }, []);

  const clearLogs = useCallback(async () => {
    try {
      const token = localStorage.getItem('auth_token');
      await fetch('/api/v1/logs', {
        method: 'DELETE',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      setLogs([]);
      setPipelineStatus(null);
    } catch (e) {
      console.error('Failed to clear logs:', e);
    }
  }, []);

  const logMessage = useCallback(
    async (
      level: LogEntry['level'],
      message: string,
      context?: Record<string, unknown>
    ) => {
      const token = localStorage.getItem('auth_token');
      await fetch('/api/v1/logs', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ level, message, context }),
      });
    },
    []
  );

  const updatePipelineStatus = useCallback(
    async (
      status: PipelineStatus['status'],
      phase: PipelineStatus['phase'],
      step: string,
      message?: string,
      details?: Record<string, unknown>
    ) => {
      const token = localStorage.getItem('auth_token');
      await fetch('/api/v1/logs/pipeline', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status, phase, step, message, details }),
      });
    },
    []
  );

  useEffect(() => {
    if (enabled) {
      connect();
    }

    return () => {
      disconnect();
    };
  }, [enabled, connect, disconnect]);

  return {
    logs,
    pipelineStatus,
    connected,
    error,
    clearLogs,
    logMessage,
    updatePipelineStatus,
    disconnect,
    reconnect: connect,
  };
}
