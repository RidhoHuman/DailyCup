"use client";

import { useState, useEffect, useRef } from "react";
import { api } from "@/lib/api-client";

interface Conversation {
  id: number;
  user_id: number;
  user_name: string;
  subject: string;
  status: 'open' | 'closed' | 'pending';
  unread_count: number;
  last_message: string;
  last_message_at: string;
  created_at: string;
}

interface Message {
  id: number;
  conversation_id: number;
  sender_type: 'customer' | 'admin';
  sender_name: string;
  message: string;
  is_read: boolean;
  created_at: string;
}

export default function AdminChatPage() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [messageInput, setMessageInput] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [filter, setFilter] = useState<'all' | 'open' | 'closed' | 'pending'>('all');
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetchConversations();
    // Auto-refresh every 5 seconds
    const interval = setInterval(fetchConversations, 5000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    if (selectedConversation) {
      fetchMessages(selectedConversation.id);
      // Auto-refresh messages every 3 seconds
      const interval = setInterval(() => fetchMessages(selectedConversation.id), 3000);
      return () => clearInterval(interval);
    }
  }, [selectedConversation]);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const fetchConversations = async () => {
    try {
      const response = await api.get<{success: boolean; conversations: Conversation[]}>('/chat/conversations.php', { requiresAuth: true });
      if (response.success) {
        setConversations(response.conversations);
      }
    } catch (error) {
      console.error('Failed to fetch conversations:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchMessages = async (conversationId: number) => {
    try {
      const response = await api.get<{success: boolean; messages: Message[]}>(`/chat/messages.php?conversation_id=${conversationId}`, { requiresAuth: true });
      if (response.success) {
        setMessages(response.messages);
      }
    } catch (error) {
      console.error('Failed to fetch messages:', error);
    }
  };

  const sendMessage = async () => {
    if (!messageInput.trim() || !selectedConversation) return;

    setSending(true);
    try {
      const response = await api.post<{success: boolean; message: string}>('/chat/messages.php', {
        conversation_id: selectedConversation.id,
        message: messageInput
      }, { requiresAuth: true });

      if (response.success) {
        setMessageInput('');
        fetchMessages(selectedConversation.id);
        fetchConversations();
      }
    } catch (error) {
      console.error('Failed to send message:', error);
      alert('Failed to send message');
    } finally {
      setSending(false);
    }
  };

  const updateStatus = async (status: 'open' | 'closed' | 'pending') => {
    if (!selectedConversation) return;

    try {
      const response = await api.put<{success: boolean}>('/chat/conversations.php', {
        conversation_id: selectedConversation.id,
        status
      }, { requiresAuth: true });

      if (response.success) {
        fetchConversations();
        setSelectedConversation({ ...selectedConversation, status });
      }
    } catch (error) {
      console.error('Failed to update status:', error);
    }
  };

  const filteredConversations = conversations.filter(conv => 
    filter === 'all' || conv.status === filter
  );

  const totalUnread = conversations.reduce((sum, conv) => sum + conv.unread_count, 0);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading chats...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="h-[calc(100vh-200px)]">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Live Chat Support</h1>
        <p className="text-gray-500">Respond to customer inquiries in real-time</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
        {/* Conversations List */}
        <div className="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
          {/* Header with Filters */}
          <div className="p-4 border-b">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold">Conversations</h2>
              {totalUnread > 0 && (
                <span className="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                  {totalUnread} unread
                </span>
              )}
            </div>
            
            <div className="flex gap-2 text-sm">
              <button
                onClick={() => setFilter('all')}
                className={`px-3 py-1 rounded-full ${filter === 'all' ? 'bg-[#a97456] text-white' : 'bg-gray-100 text-gray-600'}`}
              >
                All
              </button>
              <button
                onClick={() => setFilter('open')}
                className={`px-3 py-1 rounded-full ${filter === 'open' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600'}`}
              >
                Open
              </button>
              <button
                onClick={() => setFilter('pending')}
                className={`px-3 py-1 rounded-full ${filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600'}`}
              >
                Pending
              </button>
              <button
                onClick={() => setFilter('closed')}
                className={`px-3 py-1 rounded-full ${filter === 'closed' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-600'}`}
              >
                Closed
              </button>
            </div>
          </div>

          {/* Conversations */}
          <div className="flex-1 overflow-y-auto">
            {filteredConversations.length === 0 ? (
              <div className="p-8 text-center text-gray-500">
                <i className="bi bi-chat-dots text-4xl mb-2"></i>
                <p>No conversations found</p>
              </div>
            ) : (
              <div className="divide-y">
                {filteredConversations.map(conv => (
                  <div
                    key={conv.id}
                    onClick={() => setSelectedConversation(conv)}
                    className={`p-4 cursor-pointer hover:bg-gray-50 transition-colors ${
                      selectedConversation?.id === conv.id ? 'bg-blue-50 border-l-4 border-[#a97456]' : ''
                    }`}
                  >
                    <div className="flex items-start justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <div className="w-10 h-10 bg-[#a97456] rounded-full flex items-center justify-center text-white font-semibold">
                          {conv.user_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                          <p className="font-semibold text-gray-800">{conv.user_name}</p>
                          <p className="text-xs text-gray-500">{conv.subject}</p>
                        </div>
                      </div>
                      {conv.unread_count > 0 && (
                        <span className="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                          {conv.unread_count}
                        </span>
                      )}
                    </div>
                    <p className="text-sm text-gray-600 truncate">{conv.last_message}</p>
                    <div className="flex items-center justify-between mt-2">
                      <span className="text-xs text-gray-400">
                        {new Date(conv.last_message_at).toLocaleString('id-ID', {
                          day: '2-digit',
                          month: 'short',
                          hour: '2-digit',
                          minute: '2-digit'
                        })}
                      </span>
                      <span className={`text-xs px-2 py-1 rounded-full ${
                        conv.status === 'open' ? 'bg-green-100 text-green-700' :
                        conv.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {conv.status}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Chat Window */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
          {selectedConversation ? (
            <>
              {/* Chat Header */}
              <div className="p-4 border-b bg-gray-50">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="font-semibold text-gray-800">{selectedConversation.user_name}</h3>
                    <p className="text-sm text-gray-500">{selectedConversation.subject}</p>
                  </div>
                  <div className="flex gap-2">
                    <button
                      onClick={() => updateStatus('open')}
                      className={`px-3 py-1 rounded-lg text-sm ${
                        selectedConversation.status === 'open' 
                          ? 'bg-green-500 text-white' 
                          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      }`}
                    >
                      Open
                    </button>
                    <button
                      onClick={() => updateStatus('pending')}
                      className={`px-3 py-1 rounded-lg text-sm ${
                        selectedConversation.status === 'pending' 
                          ? 'bg-yellow-500 text-white' 
                          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      }`}
                    >
                      Pending
                    </button>
                    <button
                      onClick={() => updateStatus('closed')}
                      className={`px-3 py-1 rounded-lg text-sm ${
                        selectedConversation.status === 'closed' 
                          ? 'bg-gray-500 text-white' 
                          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      }`}
                    >
                      Close
                    </button>
                  </div>
                </div>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                {messages.map(msg => (
                  <div
                    key={msg.id}
                    className={`flex ${msg.sender_type === 'admin' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div className={`max-w-[70%] ${
                      msg.sender_type === 'admin' 
                        ? 'bg-[#a97456] text-white' 
                        : 'bg-white border border-gray-200'
                    } rounded-2xl p-3 shadow-sm`}>
                      <p className="text-xs font-semibold mb-1 opacity-75">{msg.sender_name}</p>
                      <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                      <p className={`text-xs mt-1 ${
                        msg.sender_type === 'admin' ? 'text-white/70' : 'text-gray-400'
                      }`}>
                        {new Date(msg.created_at).toLocaleTimeString('id-ID', {
                          hour: '2-digit',
                          minute: '2-digit'
                        })}
                      </p>
                    </div>
                  </div>
                ))}
                <div ref={messagesEndRef} />
              </div>

              {/* Input */}
              <div className="p-4 border-t bg-white">
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={messageInput}
                    onChange={(e) => setMessageInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                    placeholder="Type your message..."
                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                  <button
                    onClick={sendMessage}
                    disabled={sending || !messageInput.trim()}
                    className="px-6 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8f6249] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  >
                    {sending ? (
                      <i className="bi bi-arrow-repeat animate-spin"></i>
                    ) : (
                      <i className="bi bi-send"></i>
                    )}
                    Send
                  </button>
                </div>
              </div>
            </>
          ) : (
            <div className="flex-1 flex items-center justify-center text-gray-400">
              <div className="text-center">
                <i className="bi bi-chat-dots text-6xl mb-4"></i>
                <p>Select a conversation to start chatting</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
