"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";
import Link from "next/link";

interface Ticket {
  id: string;
  ticket_number: string;
  order_id: string | null;
  subject: string;
  category: string;
  priority: string;
  status: string;
  message_count: number;
  created_at: string;
  updated_at: string;
  last_message_at: string;
}

interface TicketMessage {
  id: string;
  user_name: string;
  message: string;
  is_staff: boolean;
  created_at: string;
}

interface TicketDetail extends Ticket {
  messages: TicketMessage[];
}

export default function CustomerTicketsPage() {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [selectedTicket, setSelectedTicket] = useState<TicketDetail | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [loading, setLoading] = useState(true);
  const [replyMessage, setReplyMessage] = useState('');
  const [sending, setSending] = useState(false);

  // Create form
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('general');
  const [priority, setPriority] = useState('normal');
  const [message, setMessage] = useState('');
  const [orderId, setOrderId] = useState('');
  const [creating, setCreating] = useState(false);

  useEffect(() => {
    fetchTickets();
  }, []);

  useEffect(() => {
    if (selectedTicket) {
      const interval = setInterval(() => fetchTicketDetail(selectedTicket.id), 5000);
      return () => clearInterval(interval);
    }
  }, [selectedTicket]);

  const fetchTickets = async () => {
    try {
      setLoading(true);
      const response = await api.get<{success: boolean; tickets: Ticket[]}>('/tickets.php', { requiresAuth: true });
      if (response.success) {
        setTickets(response.tickets);
      }
    } catch (error) {
      console.error('Failed to fetch tickets:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchTicketDetail = async (ticketId: string) => {
    try {
      const response = await api.get<{success: boolean; ticket: TicketDetail}>(`/tickets.php?id=${ticketId}`, { requiresAuth: true });
      if (response.success) {
        setSelectedTicket(response.ticket);
      }
    } catch (error) {
      console.error('Failed to fetch ticket detail:', error);
    }
  };

  const handleCreateTicket = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!subject.trim() || !message.trim()) {
      alert('Please fill in all required fields');
      return;
    }

    try {
      setCreating(true);
      const response = await api.post<{success: boolean; ticket_id: string; ticket_number: string}>('/tickets.php', {
        subject,
        category,
        priority,
        message,
        order_id: orderId || null
      }, { requiresAuth: true });

      if (response.success) {
        setShowCreateModal(false);
        setSubject('');
        setCategory('general');
        setPriority('normal');
        setMessage('');
        setOrderId('');
        await fetchTickets();
        await fetchTicketDetail(response.ticket_id);
      }
    } catch (error) {
      console.error('Failed to create ticket:', error);
      alert('Failed to create ticket');
    } finally {
      setCreating(false);
    }
  };

  const handleReply = async () => {
    if (!selectedTicket || !replyMessage.trim()) return;

    try {
      setSending(true);
      const response = await api.post<{success: boolean}>('/tickets.php', {
        ticket_id: selectedTicket.id,
        message: replyMessage
      }, { requiresAuth: true });

      if (response.success) {
        setReplyMessage('');
        await fetchTicketDetail(selectedTicket.id);
        await fetchTickets();
      }
    } catch (error) {
      console.error('Failed to send reply:', error);
      alert('Failed to send reply');
    } finally {
      setSending(false);
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'urgent': return 'text-red-600 bg-red-100';
      case 'high': return 'text-orange-600 bg-orange-100';
      case 'normal': return 'text-blue-600 bg-blue-100';
      case 'low': return 'text-gray-600 bg-gray-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'open': return 'text-red-600 bg-red-100';
      case 'in_progress': return 'text-yellow-600 bg-yellow-100';
      case 'closed': return 'text-green-600 bg-green-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  if (loading && tickets.length === 0) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <i className="bi bi-arrow-repeat animate-spin text-4xl text-[#a97456] mb-4"></i>
          <p className="text-gray-600">Loading tickets...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-7xl">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-800 mb-2">My Support Tickets</h1>
          <p className="text-gray-500">Get help from our support team</p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] transition-colors font-medium"
        >
          <i className="bi bi-plus-lg mr-2"></i>
          New Ticket
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Tickets List */}
        <div className="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-4 border-b border-gray-100">
            <h3 className="font-semibold text-gray-800">Your Tickets ({tickets.length})</h3>
          </div>
          <div className="overflow-y-auto max-h-[600px]">
            {tickets.length === 0 ? (
              <div className="p-8 text-center text-gray-500">
                <i className="bi bi-inbox text-4xl mb-2"></i>
                <p>No tickets yet</p>
                <button
                  onClick={() => setShowCreateModal(true)}
                  className="mt-4 text-[#a97456] hover:underline"
                >
                  Create your first ticket
                </button>
              </div>
            ) : (
              tickets.map((ticket) => (
                <div
                  key={ticket.id}
                  onClick={() => fetchTicketDetail(ticket.id)}
                  className={`p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors ${
                    selectedTicket?.id === ticket.id ? 'bg-blue-50' : ''
                  }`}
                >
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex-1">
                      <span className="text-xs font-mono text-gray-500">{ticket.ticket_number}</span>
                      <h4 className="font-medium text-gray-800 line-clamp-1">{ticket.subject}</h4>
                    </div>
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ml-2 ${getPriorityColor(ticket.priority)}`}>
                      {ticket.priority}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs text-gray-500">
                    <span className={`px-2 py-0.5 rounded-full ${getStatusColor(ticket.status)}`}>
                      {ticket.status.replace('_', ' ')}
                    </span>
                    <span>{new Date(ticket.updated_at).toLocaleDateString('id-ID')}</span>
                  </div>
                  {ticket.message_count > 0 && (
                    <div className="mt-2 text-xs text-gray-500">
                      <i className="bi bi-chat-dots mr-1"></i>
                      {ticket.message_count} messages
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>

        {/* Ticket Detail */}
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          {!selectedTicket ? (
            <div className="flex items-center justify-center h-full min-h-[600px] text-gray-400">
              <div className="text-center">
                <i className="bi bi-ticket-detailed text-6xl mb-4"></i>
                <p className="text-lg">Select a ticket to view details</p>
              </div>
            </div>
          ) : (
            <div className="flex flex-col h-full">
              {/* Header */}
              <div className="p-6 border-b border-gray-100">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex-1">
                    <span className="text-sm font-mono text-gray-500">{selectedTicket.ticket_number}</span>
                    <h2 className="text-xl font-bold text-gray-800 mb-2">{selectedTicket.subject}</h2>
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <span className="capitalize">{selectedTicket.category}</span>
                      {selectedTicket.order_id && (
                        <>
                          <span className="text-gray-400">•</span>
                          <Link href={`/track/${selectedTicket.order_id}`} className="text-[#a97456] hover:underline">
                            Order #{selectedTicket.order_id}
                          </Link>
                        </>
                      )}
                    </div>
                  </div>
                  <button
                    onClick={() => setSelectedTicket(null)}
                    className="text-gray-400 hover:text-gray-600"
                  >
                    <i className="bi bi-x-lg text-xl"></i>
                  </button>
                </div>
                <div className="flex gap-2">
                  <span className={`px-3 py-1 rounded-lg text-sm font-medium ${getStatusColor(selectedTicket.status)}`}>
                    {selectedTicket.status.replace('_', ' ')}
                  </span>
                  <span className={`px-3 py-1 rounded-lg text-sm font-medium ${getPriorityColor(selectedTicket.priority)}`}>
                    {selectedTicket.priority}
                  </span>
                  <span className="px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">
                    Created {new Date(selectedTicket.created_at).toLocaleDateString('id-ID')}
                  </span>
                </div>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
                {selectedTicket.messages.map((msg) => (
                  <div
                    key={msg.id}
                    className={`flex ${msg.is_staff ? 'justify-start' : 'justify-end'}`}
                  >
                    <div className={`max-w-[80%] ${msg.is_staff ? 'bg-white text-gray-800' : 'bg-[#a97456] text-white'} rounded-lg p-4 shadow-sm`}>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="font-semibold text-sm">{msg.user_name}</span>
                        {msg.is_staff && (
                          <span className="text-xs bg-[#a97456] text-white px-2 py-0.5 rounded">Support</span>
                        )}
                      </div>
                      <p className="whitespace-pre-wrap">{msg.message}</p>
                      <span className={`text-xs mt-2 block ${msg.is_staff ? 'text-gray-500' : 'text-white/70'}`}>
                        {new Date(msg.created_at).toLocaleString('id-ID')}
                      </span>
                    </div>
                  </div>
                ))}
              </div>

              {/* Reply Form */}
              {selectedTicket.status !== 'closed' ? (
                <div className="p-4 border-t border-gray-100 bg-white">
                  <div className="flex gap-2">
                    <textarea
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      placeholder="Type your message..."
                      className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                      rows={3}
                    />
                    <button
                      onClick={handleReply}
                      disabled={sending || !replyMessage.trim()}
                      className="px-6 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                      {sending ? (
                        <i className="bi bi-arrow-repeat animate-spin"></i>
                      ) : (
                        <i className="bi bi-send"></i>
                      )}
                    </button>
                  </div>
                </div>
              ) : (
                <div className="p-4 border-t border-gray-100 bg-gray-50 text-center text-gray-500">
                  <i className="bi bi-lock mr-2"></i>
                  This ticket has been closed
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Create Ticket Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-100">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Create New Ticket</h2>
                <button
                  onClick={() => setShowCreateModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <i className="bi bi-x-lg text-xl"></i>
                </button>
              </div>
            </div>
            <form onSubmit={handleCreateTicket} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Subject <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  placeholder="Brief description of your issue"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  required
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  >
                    <option value="general">General</option>
                    <option value="order">Order Issue</option>
                    <option value="product">Product Question</option>
                    <option value="payment">Payment Issue</option>
                    <option value="delivery">Delivery Issue</option>
                    <option value="technical">Technical Support</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                  <select
                    value={priority}
                    onChange={(e) => setPriority(e.target.value)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                  >
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Related Order ID (Optional)
                </label>
                <input
                  type="text"
                  value={orderId}
                  onChange={(e) => setOrderId(e.target.value)}
                  placeholder="Enter order ID if this is order-related"
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Message <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  placeholder="Describe your issue in detail..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent resize-none"
                  rows={6}
                  required
                />
              </div>

              <div className="flex gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={creating}
                  className="flex-1 px-6 py-3 bg-[#a97456] text-white rounded-lg hover:bg-[#8b5e3c] disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                  {creating ? (
                    <>
                      <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                      Creating...
                    </>
                  ) : (
                    <>
                      <i className="bi bi-send mr-2"></i>
                      Create Ticket
                    </>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
