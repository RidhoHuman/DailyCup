"use client";

import { useState, useEffect } from "react";
import { api } from "@/lib/api-client";

interface Ticket {
  id: string;
  ticket_number: string;
  user_id: string;
  order_id: string | null;
  subject: string;
  category: string;
  priority: string;
  status: string;
  customer_name: string;
  customer_email: string;
  assigned_name: string | null;
  assigned_to: string | null;
  message_count: number;
  created_at: string;
  updated_at: string;
  last_message_at: string;
}

interface TicketMessage {
  id: string;
  ticket_id: string;
  user_id: string;
  user_name: string;
  role: string;
  message: string;
  is_staff: boolean;
  created_at: string;
}

interface TicketDetail extends Ticket {
  messages: TicketMessage[];
}

export default function AdminTicketsPage() {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [selectedTicket, setSelectedTicket] = useState<TicketDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [replyMessage, setReplyMessage] = useState('');
  const [sending, setSending] = useState(false);
  
  // Filters
  const [statusFilter, setStatusFilter] = useState<'all' | 'open' | 'in_progress' | 'closed'>('all');
  const [priorityFilter, setPriorityFilter] = useState<'all' | 'urgent' | 'high' | 'normal' | 'low'>('all');
  const [categoryFilter, setCategoryFilter] = useState<'all' | 'general' | 'order' | 'product' | 'payment' | 'delivery' | 'technical'>('all');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchTickets();
    const interval = setInterval(fetchTickets, 10000); // Refresh every 10s
    return () => clearInterval(interval);
  }, [statusFilter, priorityFilter, categoryFilter, searchQuery]);

  useEffect(() => {
    if (selectedTicket) {
      const interval = setInterval(() => fetchTicketDetail(selectedTicket.id), 5000);
      return () => clearInterval(interval);
    }
  }, [selectedTicket]);

  const fetchTickets = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (priorityFilter !== 'all') params.append('priority', priorityFilter);
      if (categoryFilter !== 'all') params.append('category', categoryFilter);
      if (searchQuery) params.append('search', searchQuery);
      
      const response = await api.get<{success: boolean; tickets: Ticket[]}>(`/tickets.php?${params}`, { requiresAuth: true });
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

  const handleReply = async () => {
    if (!selectedTicket || !replyMessage.trim()) return;
    
    try {
      setSending(true);
      const response = await api.post<{success: boolean; message: string}>('/tickets.php', {
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

  const updateTicketStatus = async (ticketId: string, status: string) => {
    try {
      const response = await api.put<{success: boolean}>('/tickets.php', {
        id: ticketId,
        status
      }, { requiresAuth: true });
      
      if (response.success) {
        await fetchTickets();
        if (selectedTicket?.id === ticketId) {
          await fetchTicketDetail(ticketId);
        }
      }
    } catch (error) {
      console.error('Failed to update status:', error);
      alert('Failed to update status');
    }
  };

  const updateTicketPriority = async (ticketId: string, priority: string) => {
    try {
      const response = await api.put<{success: boolean}>('/tickets.php', {
        id: ticketId,
        priority
      }, { requiresAuth: true });
      
      if (response.success) {
        await fetchTickets();
        if (selectedTicket?.id === ticketId) {
          await fetchTicketDetail(ticketId);
        }
      }
    } catch (error) {
      console.error('Failed to update priority:', error);
      alert('Failed to update priority');
    }
  };

  const stats = {
    total: tickets.length,
    open: tickets.filter(t => t.status === 'open').length,
    in_progress: tickets.filter(t => t.status === 'in_progress').length,
    closed: tickets.filter(t => t.status === 'closed').length,
    urgent: tickets.filter(t => t.priority === 'urgent').length
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
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Support Tickets</h1>
        <p className="text-gray-500">Manage customer support requests</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Total</p>
              <h3 className="text-2xl font-bold text-gray-800">{stats.total}</h3>
            </div>
            <i className="bi bi-ticket-perforated text-2xl text-gray-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Open</p>
              <h3 className="text-2xl font-bold text-red-600">{stats.open}</h3>
            </div>
            <i className="bi bi-exclamation-circle text-2xl text-red-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">In Progress</p>
              <h3 className="text-2xl font-bold text-yellow-600">{stats.in_progress}</h3>
            </div>
            <i className="bi bi-clock-history text-2xl text-yellow-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Closed</p>
              <h3 className="text-2xl font-bold text-green-600">{stats.closed}</h3>
            </div>
            <i className="bi bi-check-circle text-2xl text-green-600"></i>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500 mb-1">Urgent</p>
              <h3 className="text-2xl font-bold text-red-600">{stats.urgent}</h3>
            </div>
            <i className="bi bi-fire text-2xl text-red-600"></i>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search tickets..."
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            />
          </div>
          <div className="flex flex-wrap gap-2">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as any)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="closed">Closed</option>
            </select>
            <select
              value={priorityFilter}
              onChange={(e) => setPriorityFilter(e.target.value as any)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="all">All Priority</option>
              <option value="urgent">Urgent</option>
              <option value="high">High</option>
              <option value="normal">Normal</option>
              <option value="low">Low</option>
            </select>
            <select
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value as any)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#a97456] focus:border-transparent"
            >
              <option value="all">All Category</option>
              <option value="general">General</option>
              <option value="order">Order</option>
              <option value="product">Product</option>
              <option value="payment">Payment</option>
              <option value="delivery">Delivery</option>
              <option value="technical">Technical</option>
            </select>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Tickets List */}
        <div className="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-4 border-b border-gray-100">
            <h3 className="font-semibold text-gray-800">Tickets ({tickets.length})</h3>
          </div>
          <div className="overflow-y-auto max-h-[600px]">
            {tickets.length === 0 ? (
              <div className="p-8 text-center text-gray-500">
                <i className="bi bi-inbox text-4xl mb-2"></i>
                <p>No tickets found</p>
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
                  <p className="text-sm text-gray-600 mb-2">{ticket.customer_name}</p>
                  <div className="flex items-center justify-between text-xs text-gray-500">
                    <span className={`px-2 py-0.5 rounded-full ${getStatusColor(ticket.status)}`}>
                      {ticket.status.replace('_', ' ')}
                    </span>
                    <span>{new Date(ticket.updated_at).toLocaleDateString('id-ID')}</span>
                  </div>
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
                      <i className="bi bi-person"></i>
                      <span>{selectedTicket.customer_name}</span>
                      <span className="text-gray-400">•</span>
                      <span>{selectedTicket.customer_email}</span>
                      {selectedTicket.order_id && (
                        <>
                          <span className="text-gray-400">•</span>
                          <span>Order #{selectedTicket.order_id}</span>
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
                  <select
                    value={selectedTicket.status}
                    onChange={(e) => updateTicketStatus(selectedTicket.id, e.target.value)}
                    className={`px-3 py-1 rounded-lg text-sm font-medium ${getStatusColor(selectedTicket.status)}`}
                  >
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="closed">Closed</option>
                  </select>
                  <select
                    value={selectedTicket.priority}
                    onChange={(e) => updateTicketPriority(selectedTicket.id, e.target.value)}
                    className={`px-3 py-1 rounded-lg text-sm font-medium ${getPriorityColor(selectedTicket.priority)}`}
                  >
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="normal">Normal</option>
                    <option value="low">Low</option>
                  </select>
                  <span className="px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">
                    {selectedTicket.category}
                  </span>
                </div>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
                {selectedTicket.messages.map((msg) => (
                  <div
                    key={msg.id}
                    className={`flex ${msg.is_staff ? 'justify-end' : 'justify-start'}`}
                  >
                    <div className={`max-w-[80%] ${msg.is_staff ? 'bg-[#a97456] text-white' : 'bg-white text-gray-800'} rounded-lg p-4 shadow-sm`}>
                      <div className="flex items-center gap-2 mb-2">
                        <span className="font-semibold text-sm">{msg.user_name}</span>
                        {msg.is_staff && (
                          <span className="text-xs bg-white/20 px-2 py-0.5 rounded">Staff</span>
                        )}
                      </div>
                      <p className="whitespace-pre-wrap">{msg.message}</p>
                      <span className={`text-xs mt-2 block ${msg.is_staff ? 'text-white/70' : 'text-gray-500'}`}>
                        {new Date(msg.created_at).toLocaleString('id-ID')}
                      </span>
                    </div>
                  </div>
                ))}
              </div>

              {/* Reply Form */}
              {selectedTicket.status !== 'closed' && (
                <div className="p-4 border-t border-gray-100 bg-white">
                  <div className="flex gap-2">
                    <textarea
                      value={replyMessage}
                      onChange={(e) => setReplyMessage(e.target.value)}
                      placeholder="Type your reply..."
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
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
