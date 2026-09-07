import { useState, useEffect, useRef } from 'react';
import { useLogStream, LogEntry, PipelineStatus } from '../../hooks/useLogStream';

interface LogViewerProps {
  autoOpen?: boolean;
  maxHeight?: string;
  showPipelineOnly?: boolean;
}

const LEVEL_COLORS: Record<string, string> = {
  debug: 'text-gray-400',
  info: 'text-blue-400',
  notice: 'text-cyan-400',
  warning: 'text-yellow-400',
  error: 'text-red-400',
  critical: 'bg-red-900 text-white',
};

const LEVEL_BG: Record<string, string> = {
  debug: 'bg-gray-900',
  info: 'bg-blue-900/30',
  notice: 'bg-cyan-900/30',
  warning: 'bg-yellow-900/30',
  error: 'bg-red-900/50',
  critical: 'bg-red-900',
};

const PHASE_CONFIG = {
  INICIO: {
    color: 'text-red-500',
    bg: 'bg-red-500/20',
    border: 'border-red-500',
    icon: '🔴',
    label: 'SEGURANÇA',
  },
  MEIO: {
    color: 'text-yellow-500',
    bg: 'bg-yellow-500/20',
    border: 'border-yellow-500',
    icon: '🟡',
    label: 'VALIDAÇÃO',
  },
  FIM: {
    color: 'text-green-500',
    bg: 'bg-green-500/20',
    border: 'border-green-500',
    icon: '🟢',
    label: 'DEPLOY',
  },
};

const STATUS_CONFIG = {
  started: { color: 'text-blue-400', icon: '🚀' },
  running: { color: 'text-yellow-400', icon: '⏳' },
  success: { color: 'text-green-400', icon: '✅' },
  warning: { color: 'text-yellow-400', icon: '⚠️' },
  failed: { color: 'text-red-400', icon: '❌' },
  completed: { color: 'text-green-400', icon: '🎉' },
};

export function LogViewer({
  autoOpen = false,
  maxHeight = '400px',
  showPipelineOnly = false,
}: LogViewerProps) {
  const [isOpen, setIsOpen] = useState(autoOpen);
  const [filter, setFilter] = useState<string>('');
  const [levelFilter, setLevelFilter] = useState<string>('all');

  const {
    logs,
    pipelineStatus,
    connected,
    clearLogs,
    updatePipelineStatus,
  } = useLogStream({
    enabled: isOpen,
    maxLogs: 200,
  });

  const logsEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (logsEndRef.current) {
      logsEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [logs]);

  const filteredLogs = logs.filter((log) => {
    const matchesText = filter
      ? log.message.toLowerCase().includes(filter.toLowerCase()) ||
        log.channel.toLowerCase().includes(filter.toLowerCase())
      : true;
    const matchesLevel = levelFilter === 'all' || log.level === levelFilter;
    return matchesText && matchesLevel;
  });

  const togglePanel = () => setIsOpen(!isOpen);

  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2">
      {/* Pipeline Status Badge */}
      {isOpen && pipelineStatus && (
        <div
          className={`flex items-center gap-3 px-4 py-3 rounded-lg border-2 ${
            PHASE_CONFIG[pipelineStatus.phase]?.border || 'border-gray-500'
          } ${PHASE_CONFIG[pipelineStatus.phase]?.bg || 'bg-gray-900'}`}
        >
          <span className="text-2xl">
            {PHASE_CONFIG[pipelineStatus.phase]?.icon || '📦'}
          </span>
          <div className="flex flex-col">
            <span
              className={`text-sm font-bold ${
                PHASE_CONFIG[pipelineStatus.phase]?.color || 'text-white'
              }`}
            >
              {PHASE_CONFIG[pipelineStatus.phase]?.label || 'PIPELINE'}:{' '}
              {pipelineStatus.step}
            </span>
            <span
              className={`text-xs ${
                STATUS_CONFIG[pipelineStatus.status]?.color || 'text-gray-400'
              }`}
            >
              {STATUS_CONFIG[pipelineStatus.status]?.icon || '•'}{' '}
              {pipelineStatus.message || pipelineStatus.status}
            </span>
          </div>
        </div>
      )}

      {/* Toggle Button */}
      <button
        onClick={togglePanel}
        className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all ${
          isOpen
            ? 'bg-gray-700 text-white hover:bg-gray-600'
            : 'bg-blue-600 text-white hover:bg-blue-500'
        }`}
      >
        <span className={`text-xs ${connected ? 'text-green-400' : 'text-red-400'}`}>
          {connected ? '●' : '○'}
        </span>
        <span>{isOpen ? '🔼 Ocultar Logs' : '🔽 Ver Logs'}</span>
      </button>

      {/* Log Panel */}
      {isOpen && (
        <div className="w-[600px] max-h-[80vh] bg-gray-900 rounded-lg border border-gray-700 shadow-2xl overflow-hidden flex flex-col">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 bg-gray-800 border-b border-gray-700">
            <div className="flex items-center gap-3">
              <h3 className="font-bold text-white">📊 Pipeline Logs</h3>
              <span
                className={`text-xs px-2 py-0.5 rounded ${
                  connected
                    ? 'bg-green-900/50 text-green-400'
                    : 'bg-red-900/50 text-red-400'
                }`}
              >
                {connected ? 'Conectado' : 'Desconectado'}
              </span>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={clearLogs}
                className="px-3 py-1 text-xs bg-red-600 hover:bg-red-500 text-white rounded"
              >
                Limpar
              </button>
            </div>
          </div>

          {/* Filters */}
          <div className="flex items-center gap-2 px-4 py-2 bg-gray-800/50 border-b border-gray-700">
            <input
              type="text"
              placeholder="Filtrar logs..."
              value={filter}
              onChange={(e) => setFilter(e.target.value)}
              className="flex-1 px-3 py-1 text-sm bg-gray-700 text-white rounded placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <select
              value={levelFilter}
              onChange={(e) => setLevelFilter(e.target.value)}
              className="px-3 py-1 text-sm bg-gray-700 text-white rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="all">Todos</option>
              <option value="debug">Debug</option>
              <option value="info">Info</option>
              <option value="warning">Warning</option>
              <option value="error">Error</option>
            </select>
          </div>

          {/* Logs List */}
          <div
            className="flex-1 overflow-y-auto p-2 space-y-1"
            style={{ maxHeight }}
          >
            {filteredLogs.length === 0 ? (
              <div className="text-center text-gray-500 py-8">
                Nenhum log encontrado
              </div>
            ) : (
              filteredLogs.map((log, index) => (
                <LogEntryRow key={index} log={log} />
              ))
            )}
            <div ref={logsEndRef} />
          </div>

          {/* Footer */}
          <div className="px-4 py-2 bg-gray-800 border-t border-gray-700 text-xs text-gray-400 flex justify-between">
            <span>{filteredLogs.length} logs</span>
            <span>
              {pipelineStatus
                ? `Pipeline: ${pipelineStatus.phase} - ${pipelineStatus.step}`
                : 'Aguardando pipeline...'}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}

function LogEntryRow({ log }: { log: LogEntry }) {
  const [expanded, setExpanded] = useState(false);

  const formatTime = (timestamp: string) => {
    try {
      const date = new Date(timestamp);
      return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    } catch {
      return timestamp;
    }
  };

  return (
    <div
      className={`px-3 py-1.5 rounded text-xs font-mono cursor-pointer hover:bg-gray-800 ${LEVEL_BG[log.level] || 'bg-gray-900'} ${LEVEL_COLORS[log.level] || 'text-gray-300'}`}
      onClick={() => setExpanded(!expanded)}
    >
      <div className="flex items-start gap-2">
        <span className="text-gray-500 shrink-0">{formatTime(log.timestamp)}</span>
        <span className="uppercase font-bold shrink-0 w-16">{log.level}</span>
        <span className="text-gray-400 shrink-0">[{log.channel}]</span>
        <span className="flex-1 break-all">{log.message}</span>
        {expanded && log.raw && (
          <pre className="mt-2 p-2 bg-black/50 rounded text-gray-300 whitespace-pre-wrap">
            {log.raw}
          </pre>
        )}
      </div>
    </div>
  );
}

export default LogViewer;
