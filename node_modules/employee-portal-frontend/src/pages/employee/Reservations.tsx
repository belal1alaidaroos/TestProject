import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { 
  Calendar, 
  Search, 
  Filter, 
  Eye,
  Edit,
  User,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle
} from 'lucide-react';
import { apiService } from '@/services/apiService';

interface Reservation {
  id: string;
  reservation_number: string;
  status: 'pending' | 'confirmed' | 'rejected' | 'cancelled';
  requested_date: string;
  duration_days: number;
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
    profession: {
      name_en: string;
      name_ar: string;
    };
  };
  customer: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
  };
  created_at: string;
  employee_notes?: string;
}

const Reservations: React.FC = () => {
  const [reservations, setReservations] = useState<Reservation[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedReservation, setSelectedReservation] = useState<Reservation | null>(null);
  const [showUpdateModal, setShowUpdateModal] = useState(false);
  const [updateStatus, setUpdateStatus] = useState('');
  const [updateNotes, setUpdateNotes] = useState('');

  useEffect(() => {
    fetchReservations();
  }, [currentPage, searchTerm, statusFilter]);

  const fetchReservations = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15'
      });

      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter) params.append('status', statusFilter);

      const response = await apiService.get(`/employee/reservations?${params}`);
      
      if (response.success) {
        setReservations(response.data.data);
        setTotalPages(response.data.last_page);
      }
    } catch (error) {
      console.error('Failed to fetch reservations:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'confirmed':
        return <Badge className="bg-green-100 text-green-800">Confirmed</Badge>;
      case 'pending':
        return <Badge variant="outline" className="border-orange-200 text-orange-800">Pending</Badge>;
      case 'rejected':
        return <Badge variant="destructive">Rejected</Badge>;
      case 'cancelled':
        return <Badge className="bg-gray-100 text-gray-800">Cancelled</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleUpdateStatus = async () => {
    if (!selectedReservation || !updateStatus) return;

    try {
      const response = await apiService.patch(`/employee/reservations/${selectedReservation.id}/status`, {
        status: updateStatus,
        notes: updateNotes
      });

      if (response.success) {
        setShowUpdateModal(false);
        setSelectedReservation(null);
        setUpdateStatus('');
        setUpdateNotes('');
        fetchReservations();
      }
    } catch (error) {
      console.error('Failed to update reservation status:', error);
    }
  };

  const openUpdateModal = (reservation: Reservation) => {
    setSelectedReservation(reservation);
    setUpdateStatus(reservation.status);
    setUpdateNotes(reservation.employee_notes || '');
    setShowUpdateModal(true);
  };

  if (loading && reservations.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Reservations Management</h1>
          <p className="text-muted-foreground">
            Manage worker reservations and update their status
          </p>
        </div>
        <Button onClick={fetchReservations} variant="outline">
          Refresh
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="h-5 w-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div className="relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search reservations..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Status</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="confirmed">Confirmed</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>

            <Button 
              onClick={() => {
                setSearchTerm('');
                setStatusFilter('');
                setCurrentPage(1);
              }}
              variant="outline"
            >
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Reservations List */}
      <div className="space-y-4">
        {reservations.map((reservation) => (
          <Card key={reservation.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-lg">{reservation.reservation_number}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Requested on {formatDate(reservation.created_at)}
                  </p>
                </div>
                {getStatusBadge(reservation.status)}
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                {/* Worker Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <User className="h-4 w-4" />
                    Worker
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="font-medium">{reservation.worker.name_en}</p>
                    <p className="text-muted-foreground">{reservation.worker.name_ar}</p>
                    <p className="text-muted-foreground">{reservation.worker.phone}</p>
                    <p className="text-muted-foreground">{reservation.worker.profession.name_en}</p>
                  </div>
                </div>

                {/* Customer Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <User className="h-4 w-4" />
                    Customer
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="font-medium">{reservation.customer.name_en}</p>
                    <p className="text-muted-foreground">{reservation.customer.name_ar}</p>
                    <p className="text-muted-foreground">{reservation.customer.phone}</p>
                  </div>
                </div>

                {/* Reservation Details */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <Calendar className="h-4 w-4" />
                    Reservation Details
                  </h4>
                  <div className="text-sm space-y-1">
                    <div className="flex justify-between">
                      <span>Requested Date:</span>
                      <span>{formatDate(reservation.requested_date)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Duration:</span>
                      <span>{reservation.duration_days} days</span>
                    </div>
                  </div>
                </div>

                {/* Status Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <Clock className="h-4 w-4" />
                    Status Info
                  </h4>
                  <div className="text-sm space-y-1">
                    <div className="flex justify-between">
                      <span>Status:</span>
                      <span className="capitalize">{reservation.status}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Created:</span>
                      <span>{formatDate(reservation.created_at)}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Employee Notes */}
              {reservation.employee_notes && (
                <div className="mt-4 pt-4 border-t">
                  <h4 className="font-medium mb-2">Employee Notes:</h4>
                  <p className="text-sm text-muted-foreground">{reservation.employee_notes}</p>
                </div>
              )}

              {/* Actions */}
              <div className="mt-4 pt-4 border-t flex gap-2">
                <Button size="sm" className="flex-1">
                  <Eye className="h-4 w-4 mr-2" />
                  View Details
                </Button>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => openUpdateModal(reservation)}
                >
                  <Edit className="h-4 w-4 mr-2" />
                  Update Status
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <Card>
          <CardContent className="flex items-center justify-between pt-6">
            <div className="text-sm text-muted-foreground">
              Page {currentPage} of {totalPages}
            </div>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
              >
                Next
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Empty State */}
      {!loading && reservations.length === 0 && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Calendar className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">No reservations found</h3>
            <p className="text-muted-foreground text-center">
              {searchTerm || statusFilter
                ? 'Try adjusting your filters to see more results.'
                : 'You don\'t have any reservations assigned to you yet.'}
            </p>
          </CardContent>
        </Card>
      )}

      {/* Update Status Modal */}
      {showUpdateModal && selectedReservation && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle>Update Reservation Status</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm font-medium">Reservation</label>
                <p className="text-sm text-muted-foreground">{selectedReservation.reservation_number}</p>
              </div>
              
              <div>
                <label className="text-sm font-medium">Status</label>
                <Select value={updateStatus} onValueChange={setUpdateStatus}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="confirmed">Confirmed</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <label className="text-sm font-medium">Notes</label>
                <Textarea
                  value={updateNotes}
                  onChange={(e) => setUpdateNotes(e.target.value)}
                  placeholder="Add any notes about this status update..."
                  rows={3}
                />
              </div>

              <div className="flex gap-2">
                <Button 
                  onClick={handleUpdateStatus}
                  className="flex-1"
                >
                  Update Status
                </Button>
                <Button 
                  variant="outline"
                  onClick={() => {
                    setShowUpdateModal(false);
                    setSelectedReservation(null);
                    setUpdateStatus('');
                    setUpdateNotes('');
                  }}
                >
                  Cancel
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
};

export default Reservations;