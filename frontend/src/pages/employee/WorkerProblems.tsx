import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { 
  AlertTriangle, 
  Search, 
  Filter, 
  Eye,
  Edit,
  CheckCircle,
  XCircle,
  Clock,
  User,
  Calendar,
  FileText,
  AlertCircle
} from 'lucide-react';
import { apiService } from '@/services/apiService';

interface WorkerProblem {
  id: string;
  problem_type: 'escape' | 'refusal' | 'non_compliance' | 'misconduct' | 'early_return';
  description: string;
  date_reported: string;
  status: 'Pending' | 'Approved' | 'Rejected' | 'Closed';
  resolution_action?: 'Dismissal' | 'Re-training' | 'Escalation';
  resolution_notes?: string;
  worker: {
    id: string;
    name_en: string;
    name_ar: string;
    phone: string;
    passport_number: string;
  };
  created_by: {
    id: string;
    name: string;
  };
  approved_by?: {
    id: string;
    name: string;
  };
  approved_at?: string;
  created_at: string;
}

const WorkerProblems: React.FC = () => {
  const [problems, setProblems] = useState<WorkerProblem[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedProblem, setSelectedProblem] = useState<WorkerProblem | null>(null);
  const [showActionModal, setShowActionModal] = useState(false);
  const [actionType, setActionType] = useState<'approve' | 'reject' | 'close'>('approve');
  const [resolutionAction, setResolutionAction] = useState('');
  const [resolutionNotes, setResolutionNotes] = useState('');

  useEffect(() => {
    fetchProblems();
  }, [currentPage, searchTerm, statusFilter, typeFilter]);

  const fetchProblems = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15'
      });

      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter) params.append('status', statusFilter);
      if (typeFilter) params.append('problem_type', typeFilter);

      const response = await apiService.get(`/employee/worker-problems?${params}`);
      
      if (response.success) {
        setProblems(response.data.data);
        setTotalPages(response.data.last_page);
      }
    } catch (error) {
      console.error('Failed to fetch worker problems:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'Pending':
        return <Badge variant="outline" className="border-yellow-200 text-yellow-800">Pending</Badge>;
      case 'Approved':
        return <Badge className="bg-green-100 text-green-800">Approved</Badge>;
      case 'Rejected':
        return <Badge variant="destructive">Rejected</Badge>;
      case 'Closed':
        return <Badge className="bg-gray-100 text-gray-800">Closed</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const getProblemTypeBadge = (type: string) => {
    const typeLabels = {
      'escape': 'Escape',
      'refusal': 'Refusal to Work',
      'non_compliance': 'Non-Compliance',
      'misconduct': 'Misconduct',
      'early_return': 'Early Return'
    };

    const typeColors = {
      'escape': 'bg-red-100 text-red-800',
      'refusal': 'bg-orange-100 text-orange-800',
      'non_compliance': 'bg-yellow-100 text-yellow-800',
      'misconduct': 'bg-purple-100 text-purple-800',
      'early_return': 'bg-blue-100 text-blue-800'
    };

    return (
      <Badge className={typeColors[type as keyof typeof typeColors] || 'bg-gray-100 text-gray-800'}>
        {typeLabels[type as keyof typeof typeLabels] || type}
      </Badge>
    );
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleAction = async () => {
    if (!selectedProblem) return;

    try {
      let endpoint = '';
      let data: any = {};

      switch (actionType) {
        case 'approve':
          endpoint = `/employee/worker-problems/${selectedProblem.id}/approve`;
          data = {
            resolution_action: resolutionAction,
            resolution_notes: resolutionNotes
          };
          break;
        case 'reject':
          endpoint = `/employee/worker-problems/${selectedProblem.id}/reject`;
          data = { resolution_notes: resolutionNotes };
          break;
        case 'close':
          endpoint = `/employee/worker-problems/${selectedProblem.id}/close`;
          data = { resolution_notes: resolutionNotes };
          break;
      }

      const response = await apiService.post(endpoint, data);

      if (response.success) {
        setShowActionModal(false);
        setSelectedProblem(null);
        setActionType('approve');
        setResolutionAction('');
        setResolutionNotes('');
        fetchProblems();
      }
    } catch (error) {
      console.error('Failed to perform action:', error);
    }
  };

  const openActionModal = (problem: WorkerProblem, action: 'approve' | 'reject' | 'close') => {
    setSelectedProblem(problem);
    setActionType(action);
    setShowActionModal(true);
  };

  const canPerformAction = (problem: WorkerProblem, action: string) => {
    switch (action) {
      case 'approve':
      case 'reject':
        return problem.status === 'Pending';
      case 'close':
        return ['Approved', 'Rejected'].includes(problem.status);
      default:
        return false;
    }
  };

  if (loading && problems.length === 0) {
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
          <h1 className="text-3xl font-bold">Worker Problems Management</h1>
          <p className="text-muted-foreground">
            Manage worker problems and operational issues
          </p>
        </div>
        <Button onClick={fetchProblems} variant="outline">
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
          <div className="grid gap-4 md:grid-cols-4">
            <div className="relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search problems..."
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
                <SelectItem value="Pending">Pending</SelectItem>
                <SelectItem value="Approved">Approved</SelectItem>
                <SelectItem value="Rejected">Rejected</SelectItem>
                <SelectItem value="Closed">Closed</SelectItem>
              </SelectContent>
            </Select>

            <Select value={typeFilter} onValueChange={setTypeFilter}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Types</SelectItem>
                <SelectItem value="escape">Escape</SelectItem>
                <SelectItem value="refusal">Refusal to Work</SelectItem>
                <SelectItem value="non_compliance">Non-Compliance</SelectItem>
                <SelectItem value="misconduct">Misconduct</SelectItem>
                <SelectItem value="early_return">Early Return</SelectItem>
              </SelectContent>
            </Select>

            <Button 
              onClick={() => {
                setSearchTerm('');
                setStatusFilter('');
                setTypeFilter('');
                setCurrentPage(1);
              }}
              variant="outline"
            >
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Problems List */}
      <div className="space-y-4">
        {problems.map((problem) => (
          <Card key={problem.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <AlertTriangle className="h-6 w-6 text-orange-600" />
                  <div>
                    <CardTitle className="text-lg">Problem #{problem.id.slice(0, 8)}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                      Reported on {formatDate(problem.date_reported)}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  {getProblemTypeBadge(problem.problem_type)}
                  {getStatusBadge(problem.status)}
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {/* Worker Information */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <User className="h-4 w-4" />
                    Worker
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="font-medium">{problem.worker.name_en}</p>
                    <p className="text-muted-foreground">{problem.worker.name_ar}</p>
                    <p className="text-muted-foreground">{problem.worker.phone}</p>
                    <p className="text-muted-foreground">Passport: {problem.worker.passport_number}</p>
                  </div>
                </div>

                {/* Problem Details */}
                <div className="space-y-2">
                  <h4 className="font-medium flex items-center gap-2">
                    <AlertCircle className="h-4 w-4" />
                    Problem Details
                  </h4>
                  <div className="text-sm space-y-1">
                    <p className="text-muted-foreground">{problem.description}</p>
                    <div className="flex items-center gap-1 mt-2">
                      <Calendar className="h-4 w-4" />
                      <span>Reported: {formatDate(problem.date_reported)}</span>
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
                      <span className="capitalize">{problem.status}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Created:</span>
                      <span>{formatDate(problem.created_at)}</span>
                    </div>
                    {problem.approved_at && (
                      <div className="flex justify-between">
                        <span>Resolved:</span>
                        <span>{formatDate(problem.approved_at)}</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Resolution Information */}
              {(problem.resolution_action || problem.resolution_notes) && (
                <div className="mt-4 pt-4 border-t">
                  <h4 className="font-medium mb-2">Resolution:</h4>
                  {problem.resolution_action && (
                    <p className="text-sm text-muted-foreground mb-2">
                      Action: {problem.resolution_action}
                    </p>
                  )}
                  {problem.resolution_notes && (
                    <p className="text-sm text-muted-foreground">{problem.resolution_notes}</p>
                  )}
                </div>
              )}

              {/* Actions */}
              <div className="mt-4 pt-4 border-t flex gap-2">
                <Button size="sm" className="flex-1">
                  <Eye className="h-4 w-4 mr-2" />
                  View Details
                </Button>
                
                {canPerformAction(problem, 'approve') && (
                  <Button 
                    size="sm" 
                    variant="outline"
                    onClick={() => openActionModal(problem, 'approve')}
                  >
                    <CheckCircle className="h-4 w-4 mr-2" />
                    Approve
                  </Button>
                )}
                
                {canPerformAction(problem, 'reject') && (
                  <Button 
                    size="sm" 
                    variant="outline"
                    onClick={() => openActionModal(problem, 'reject')}
                  >
                    <XCircle className="h-4 w-4 mr-2" />
                    Reject
                  </Button>
                )}
                
                {canPerformAction(problem, 'close') && (
                  <Button 
                    size="sm" 
                    variant="outline"
                    onClick={() => openActionModal(problem, 'close')}
                  >
                    <FileText className="h-4 w-4 mr-2" />
                    Close
                  </Button>
                )}
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
      {!loading && problems.length === 0 && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <AlertTriangle className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">No worker problems found</h3>
            <p className="text-muted-foreground text-center">
              {searchTerm || statusFilter || typeFilter
                ? 'Try adjusting your filters to see more results.'
                : 'No worker problems have been reported yet.'}
            </p>
          </CardContent>
        </Card>
      )}

      {/* Action Modal */}
      {showActionModal && selectedProblem && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle>
                {actionType === 'approve' && 'Approve Problem'}
                {actionType === 'reject' && 'Reject Problem'}
                {actionType === 'close' && 'Close Problem'}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="text-sm font-medium">Problem</label>
                <p className="text-sm text-muted-foreground">
                  {selectedProblem.problem_type} - {selectedProblem.worker.name_en}
                </p>
              </div>
              
              {actionType === 'approve' && (
                <div>
                  <label className="text-sm font-medium">Resolution Action</label>
                  <Select value={resolutionAction} onValueChange={setResolutionAction}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select action" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Dismissal">Dismissal</SelectItem>
                      <SelectItem value="Re-training">Re-training</SelectItem>
                      <SelectItem value="Escalation">Escalation</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              )}

              <div>
                <label className="text-sm font-medium">Notes</label>
                <Textarea
                  value={resolutionNotes}
                  onChange={(e) => setResolutionNotes(e.target.value)}
                  placeholder="Add resolution notes..."
                  rows={3}
                />
              </div>

              <div className="flex gap-2">
                <Button 
                  onClick={handleAction}
                  className="flex-1"
                >
                  {actionType === 'approve' && 'Approve'}
                  {actionType === 'reject' && 'Reject'}
                  {actionType === 'close' && 'Close'}
                </Button>
                <Button 
                  variant="outline"
                  onClick={() => {
                    setShowActionModal(false);
                    setSelectedProblem(null);
                    setActionType('approve');
                    setResolutionAction('');
                    setResolutionNotes('');
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

export default WorkerProblems;