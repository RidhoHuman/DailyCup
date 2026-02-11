'use client';

import { useEffect, useState } from 'react';

export default function Toast() {
  const [messages, setMessages] = useState<Array<{id: number; text: string}>>([]);

  useEffect(() => {
    let idCounter = 1;
    const handler = (e: Event) => {
      const detail = (e as CustomEvent).detail;
      const text = detail?.message || (detail?.endpoint ? `Using mock data for ${detail.endpoint}` : 'Using mock data');
      const id = idCounter++;
      setMessages((prev) => [...prev, { id, text }]);
      // Auto remove after 5s
      setTimeout(() => {
        setMessages((prev) => prev.filter((m) => m.id !== id));
      }, 5000);
    };

    window.addEventListener('api:mock', handler as EventListener);
    return () => window.removeEventListener('api:mock', handler as EventListener);
  }, []);

  if (messages.length === 0) return null;

  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col gap-2">
      {messages.map((m) => (
        <div key={m.id} className="bg-black/85 text-white px-4 py-2 rounded shadow-lg max-w-xs">
          {m.text}
        </div>
      ))}
    </div>
  );
}
