import React from 'react';
import type { OrderStatusLog } from '@/types/delivery';
import { formatDistanceToNow } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';

interface DeliveryTimelineProps {
  history: OrderStatusLog[];
  currentStatus: string;
}

const statusIcons: Record<string, string> = {
  pending: '⏳',
  confirmed: '✅',
  processing: '👨‍🍳',
  ready: '📦',
  delivering: '🚴',
  completed: '🎉',
  cancelled: '❌',
  cod_validation: '🔍'
};

const statusLabels: Record<string, string> = {
  pending: 'Pesanan Dibuat',
  confirmed: 'Dikonfirmasi',
  processing: 'Sedang Diproses',
  ready: 'Siap Diambil',
  delivering: 'Dalam Pengiriman',
  completed: 'Selesai',
  cancelled: 'Dibatalkan',
  cod_validation: 'Validasi COD'
};

export default function DeliveryTimeline({ history, currentStatus }: DeliveryTimelineProps) {
  return (
    <div className="relative">
      {/* Timeline line */}
      <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />
      
      {/* Timeline items */}
      <div className="space-y-6">
        {history.map((log, index) => {
          const isLatest = index === history.length - 1;
          const icon = statusIcons[log.to_status] || '📌';
          const label = statusLabels[log.to_status] || log.to_status;
          
          return (
            <div key={log.id} className="relative flex gap-4">
              {/* Icon */}
              <div className={`
                relative z-10 flex items-center justify-center w-8 h-8 rounded-full 
                ${isLatest ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-gray-300'}
              `}>
                <span className="text-base">{icon}</span>
              </div>
              
              {/* Content */}
              <div className="flex-1 pb-6">
                <div className="flex items-start justify-between">
                  <div>
                    <h4 className={`font-medium ${isLatest ? 'text-blue-600' : 'text-gray-900'}`}>
                      {label}
                    </h4>
                    {log.notes && (
                      <p className="mt-1 text-sm text-gray-600">{log.notes}</p>
                    )}
                    {log.changed_by_name && (
                      <p className="mt-1 text-xs text-gray-500">
                        oleh {log.changed_by_name} ({log.changed_by_type})
                      </p>
                    )}
                  </div>
                  <time className="text-xs text-gray-500">
                    {formatDistanceToNow(new Date(log.created_at), {
                      addSuffix: true,
                      locale: idLocale
                    })}
                  </time>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
