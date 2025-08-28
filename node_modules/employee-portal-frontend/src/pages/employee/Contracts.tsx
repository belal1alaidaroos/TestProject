import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { 
  FileText, 
  Search, 
  Filter, 
  Eye,
  Edit,
  Calendar,
  DollarSign,
  User,
  Building,
  Clock,
  CheckCircle,
  AlertCircle,
  XCircle
} from 'lucide-react';
import { apiService } from '@/services/apiService';

interface Contract {
  id: string;
  contract_number: string;
  status: 'pending' | 'active' | 'completed' | 'cancelled' | 'on_hold';
  start_date: string;
  end_date: string;
  total_amount: number;
  currency: string;
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
  };
  customer: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
  };
  agency: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  created_at: string;
  employee_notes?: string;
}

const Contracts: React.FC = () => {
  const [contracts, setContracts] = useState<Contract[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedContract, setSelectedContract] = useState<Contract | null>(null);
  const [showUpdateModal, setShowUpdateModal] = useState(false);
  const [updateStatus, setUpdateStatus] = useState('');
  const [updateNotes, setUpdateNotes] = useState('');

  useEffect(() => {
    fetchContracts();
  }, [currentPage, searchTerm, statusFilter]);

  const fetchContracts = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15'
      });

      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter) params.append('status', statusFilter);

      const response = await apiService.get(`/employee/contracts?${params}`);
      
      if (response.success) {
        setContracts(response.data.data);
        setTotalPages(response.data.last_page);
      }
    } catch (error) {
      console.error('Failed to fetch contracts:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-green-100 text-green-800">Active</Badge>;
      case 'pending':
        return <Badge variant="outline" className="border-orange-200 text-orange-800">Pending</Badge>;
      case 'completed':
        return <Badge className="bg-purple-100 text-purple-800">Completed</Badge>;
      case 'cancelled':
        return <Badge variant="destructive">Cancelled</Badge>;
      case 'on_hold':
        return <Badge className="bg-yellow-100 text-yellow-800">On Hold</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD'
    }).format(amount);
  };

  const handleUpdateStatus = async () => {
    if (!selectedContract || !updateStatus) return;

    try {
      const response = await apiService.patch(`/employee/contracts/${selectedContract.id}/status`, {
        status: updateStatus,
        notes: updateNotes
      });

      if (response.success) {
        setShowUpdateModal(false);
        setSelectedContract(null);
        setUpdateStatus('');
        setUpdateNotes('');
        fetchContracts();
      }
    } catch (error) {
      console.error('Failed to update contract status:', error);
    }
  };

  const openUpdateModal = (contract: Contract) => {
    setSelectedContract(contract);
    setUpdateStatus(contract.status);
    setUpdateNotes(contract.employee_notes || '');
    setShowUpdateModal(true);
  };

  if (loading && contracts.length === 0) {
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
          <h1 className="text-3xl font-bold">Contracts Management</h1>
          <p className="text-muted-foreground">
            Manage your assigned contracts and update their status
          </p>
        </div>
        <Button onClick={fetchContracts} variant="outline">
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
                placeholder="Search contracts..."
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
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
                <SelectItem value="on_hold">On Hold</SelectItem>
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

      {/* Contracts List */}
      <div className="space-y-4">
        {contracts.map((contract) => (
          <Card key={contract.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-lg">{contract.contract_number}</CardTitle>
                  <p className="text-sm text-muted-foreground">
                    Created on {formatDate(contract.created_at)}
                  </p>
                </div>
                {getStatusBadge(contract.status)}
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
                    <p className="font-medium">{contract.worker.name_en}</p>
                    <p className="text-muted-foreground">{contract.worker.name_ar}</p>
                    <p className="text-muted-foreground">{contract.worker.phone}</p>
                  </div>
                </div>

                {/* Customer Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <User className="h-4 w-4" />
                    Customer
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="font-medium">{contract.customer.name_en}</p>
                    <p className="text-muted-foreground">{contract.customer.name_ar}</p>
                    <p className="text-muted-foreground">{contract.customer.phone}</p>
                  </div>
                </div>

                {/* Agency Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <Building className="h-4 w-4" />
                    Agency
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="font-medium">{contract.agency.name_en}</p>
                    <p className="text-muted-foreground">{contract.agency.name_ar}</p>
                  </div>
                </div>

                {/* Contract Details */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <FileText className="h-4 w-4" />
                    Contract Details
                  </h4>
                  <div className="text-sm space-y-1">
                    <div className="flex justify-between">
                      <span>Start Date:</span>
                      <span>{formatDate(contract.start_date)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>End Date:</span>
                      <span>{formatDate(contract.end_date)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Amount:</span>
                      <span className="font-medium">
                        {formatCurrency(contract.total_amount, contract.currency)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Employee Notes */}
              {contract.employee_notes && (
                <div className="mt-4 pt-4 border-t">
                  <h4 className="font-medium mb-2">Employee Notes:</h4>
                  <p className="text-sm text-muted-foreground">{contract.employee_notes}</p>
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
                  onClick={() => openUpdateModal(contract)}
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
      {!loading && contracts.length === 0 && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <FileText className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">No contracts found</h3>
            <p className="text-muted-foreground text-center">
              {searchTerm || statusFilter
                ? 'Try adjusting your filters to see more results.'
                : 'You don\'t have any contracts assigned to you yet.'}
            </p>
          </CardContent>
        </Card>
      )}

      {/* Update Status Modal */}
      {showUpdateModal && selectedContract && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle>Update Contract Status</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm font-medium">Contract</label>
                <p className="text-sm text-muted-foreground">{selectedContract.contract_number}</p>
              </div>
              
              <div>
                <label className="text-sm font-medium">Status</label>
                <Select value={updateStatus} onValueChange={setUpdateStatus}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                    <SelectItem value="on_hold">On Hold</SelectItem>
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
                    setSelectedContract(null);
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

export default Contracts;