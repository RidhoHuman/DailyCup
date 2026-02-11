"use client";

import { useState, useEffect, useRef } from "react";
import { api } from "@/lib/api-client";
import { useAuthStore } from "@/lib/stores/auth-store";

interface Conversation {
  id: number;
  subject: string;
  status: string;
  last_message: string;
  unread_count: number;
}

interface Message {
  id: number;
  sender_type: 'customer' | 'admin';
  sender_name: string;
  message: string;
  created_at: string;
}

export default function ChatWidget() {
  const { isAuthenticated, user } = useAuthStore();
  const [isOpen, setIsOpen] = useState(false);
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConv, setSelectedConv] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [messageInput, setMessageInput] = useState('');
  const [newChatSubject, setNewChatSubject] = useState('');
  const [showNewChat, setShowNewChat] = useState(false);
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (isAuthenticated && isOpen) {
      fetchConversations();
      const interval = setInterval(fetchConversations, 5000);
      return () => clearInterval(interval);
    }
  }, [isAuthenticated, isOpen]);

  useEffect(() => {
    if (selectedConv) {
      fetchMessages(selectedConv.id);
      const interval = setInterval(() => fetchMessages(selectedConv.id), 3000);
      return () => clearInterval(interval);
    }
  }, [selectedConv]);

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
        if (response.conversations.length > 0 && !selectedConv) {
          setSelectedConv(response.conversations[0]);
        }
      }
    } catch (error) {
      console.error('Failed to fetch conversations:', error);
    }
  };

  const fetchMessages = async (convId: number) => {
    try {
      const response = await api.get<{success: boolean; messages: Message[]}>(`/chat/messages.php?conversation_id=${convId}`, { requiresAuth: true });
      if (response.success) {
        setMessages(response.messages);
      }
    } catch (error) {
      console.error('Failed to fetch messages:', error);
    }
  };

  const createNewChat = async () => {
    if (!newChatSubject.trim() || !messageInput.trim()) {
      alert('Please enter subject and message');
      return;
    }

    setSending(true);
    try {
      const response = await api.post<{success: boolean; conversation_id: number}>('/chat/conversations.php', {
        subject: newChatSubject,
        message: messageInput
      }, { requiresAuth: true });

      if (response.success) {
        setShowNewChat(false);
        setNewChatSubject('');
        setMessageInput('');
        fetchConversations();
      }
    } catch (error) {
      console.error('Failed to create chat:', error);
      alert('Failed to create chat');
    } finally {
      setSending(false);
    }
  };

  const sendMessage = async () => {
    if (!messageInput.trim() || !selectedConv) return;

    setSending(true);
    try {
      const response = await api.post<{success: boolean}>('/chat/messages.php', {
        conversation_id: selectedConv.id,
        message: messageInput
      }, { requiresAuth: true });

      if (response.success) {
        setMessageInput('');
        fetchMessages(selectedConv.id);
      }
    } catch (error) {
      console.error('Failed to send message:', error);
    } finally {
      setSending(false);
    }
  };

  const totalUnread = conversations.reduce((sum, conv) => sum + conv.unread_count, 0);

  if (!isAuthenticated) {
    return null; // Don't show widget if not logged in
  }

  return (
    <>
      {/* Floating Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-6 right-6 w-16 h-16 bg-[#a97456] text-white rounded-full shadow-2xl hover:bg-[#8f6249] transition-all flex items-center justify-center z-50"
      >
        {isOpen ? (
          <i className="bi bi-x-lg text-2xl"></i>
        ) : (
          <>
            <i className="bi bi-chat-dots-fill text-2xl"></i>
            {totalUnread > 0 && (
              <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center">
                {totalUnread}
              </span>
            )}
          </>
        )}
      </button>

      {/* Chat Window */}
      {isOpen && (
        <div className="fixed bottom-24 right-6 w-96 h-[600px] bg-white rounded-2xl shadow-2xl z-50 flex flex-col overflow-hidden border border-gray-200">
          {/* Header */}
          <div className="bg-[#a97456] text-white p-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-bold text-lg">Live Chat Support</h3>
                <p className="text-xs opacity-90">We're here to help!</p>
              </div>
              <button
                onClick={() => setShowNewChat(!showNewChat)}
                className="w-8 h-8 bg-white/20 rounded-full hover:bg-white/30 flex items-center justify-center"
              >
                <i className="bi bi-plus-lg"></i>
              </button>
            </div>
          </div>

          {/* New Chat Form */}
          {showNewChat && (
            <div className="p-4 bg-blue-50 border-b">
              <h4 className="font-semibold text-sm mb-2">New Conversation</h4>
              <input
                type="text"
                value={newChatSubject}
                onChange={(e) => setNewChatSubject(e.target.value)}
                placeholder="Subject"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2 focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
              <textarea
                value={messageInput}
                onChange={(e) => setMessageInput(e.target.value)}
                placeholder="Your message..."
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2 focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
              />
              <div className="flex gap-2">
                <button
                  onClick={createNewChat}
                  disabled={sending}
                  className="flex-1 bg-[#a97456] text-white py-2 rounded-lg text-sm hover:bg-[#8f6249] disabled:opacity-50"
                >
                  Start Chat
                </button>
                <button
                  onClick={() => {
                    setShowNewChat(false);
                    setNewChatSubject('');
                    setMessageInput('');
                  }}
                  className="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50"
                >
                  Cancel
                </button>
              </div>
            </div>
          )}

          {/* Conversations List */}
          {!selectedConv && conversations.length > 0 && (
            <div className="flex-1 overflow-y-auto">
              {conversations.map(conv => (
                <div
                  key={conv.id}
                  onClick={() => setSelectedConv(conv)}
                  className="p-3 border-b hover:bg-gray-50 cursor-pointer"
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <p className="font-semibold text-sm">{conv.subject}</p>
                      <p className="text-xs text-gray-500 truncate">{conv.last_message}</p>
                    </div>
                    {conv.unread_count > 0 && (
                      <span className="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                        {conv.unread_count}
                      </span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Chat Messages */}
          {selectedConv && (
            <>
              {/* Conversation Header */}
              <div className="p-3 border-b bg-gray-50 flex items-center gap-2">
                <button
                  onClick={() => setSelectedConv(null)}
                  className="text-gray-600 hover:text-gray-800"
                >
                  <i className="bi bi-arrow-left"></i>
                </button>
                <div>
                  <p className="font-semibold text-sm">{selectedConv.subject}</p>
                  <p className="text-xs text-gray-500">{selectedConv.status}</p>
                </div>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                {messages.map(msg => (
                  <div
                    key={msg.id}
                    className={`flex ${msg.sender_type === 'customer' ? 'justify-end' : 'justify-start'}`}
                  >
                    <div className={`max-w-[80%] ${
                      msg.sender_type === 'customer' 
                        ? 'bg-[#a97456] text-white' 
                        : 'bg-white border border-gray-200'
                    } rounded-2xl p-3 shadow-sm`}>
                      {msg.sender_type === 'admin' && (
                        <p className="text-xs font-semibold mb-1 text-[#a97456]">{msg.sender_name}</p>
                      )}
                      <p className="text-sm whitespace-pre-wrap">{msg.message}</p>
                      <p className={`text-xs mt-1 ${
                        msg.sender_type === 'customer' ? 'text-white/70' : 'text-gray-400'
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
              <div className="p-3 border-t bg-white">
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={messageInput}
                    onChange={(e) => setMessageInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                    placeholder="Type a message..."
                    className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  />
                  <button
                    onClick={sendMessage}
                    disabled={sending || !messageInput.trim()}
                    className="px-4 py-2 bg-[#a97456] text-white rounded-lg hover:bg-[#8f6249] disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <i className="bi bi-send"></i>
                  </button>
                </div>
              </div>
            </>
          )}

          {/* Empty State */}
          {conversations.length === 0 && !showNewChat && (
            <div className="flex-1 flex items-center justify-center p-8 text-center">
              <div>
                <i className="bi bi-chat-dots text-6xl text-gray-300 mb-4"></i>
                <p className="text-gray-500 mb-4">No conversations yet</p>
                <button
                  onClick={() => setShowNewChat(true)}
                  className="bg-[#a97456] text-white px-6 py-2 rounded-lg hover:bg-[#8f6249]"
                >
                  Start a Conversation
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </>
  );
}
