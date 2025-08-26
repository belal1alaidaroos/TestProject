import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
  Users, 
  Search, 
  Filter, 
  Eye,
  Phone,
  Mail,
  MapPin,
  Calendar,
  UserCheck,
  UserX
} from 'lucide-react';
import { apiService } from '@/services/apiService';

interface Worker {
  id: string;
  name_en: string;
  name_ar: string;
  passport_number: string;
  phone: string;
  email: string;
  date_of_birth: string;
  status: 'active' | 'inactive' | 'pending';
  profession: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  nationality: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  agency: {
    id: string;
    name_en: string;
    name_ar: string;
  };
  created_at: string;
}

const Workers: React.FC = () => {
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [professionFilter, setProfessionFilter] = useState('');
  const [professions, setProfessions] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchWorkers();
    fetchProfessions();
  }, [currentPage, searchTerm, statusFilter, professionFilter]);

  const fetchWorkers = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15'
      });

      if (searchTerm) params.append('search', searchTerm);
      if (statusFilter) params.append('status', statusFilter);
      if (professionFilter) params.append('profession_id', professionFilter);

      const response = await apiService.get(`/employee/workers?${params}`);
      
      if (response.success) {
        setWorkers(response.data.data);
        setTotalPages(response.data.last_page);
      }
    } catch (error) {
      console.error('Failed to fetch workers:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchProfessions = async () => {
    try {
      const response = await apiService.get('/lookups/professions');
      if (response.success) {
        setProfessions(response.data);
      }
    } catch (error) {
      console.error('Failed to fetch professions:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-green-100 text-green-800">Active</Badge>;
      case 'inactive':
        return <Badge variant="destructive">Inactive</Badge>;
      case 'pending':
        return <Badge variant="outline" className="border-orange-200 text-orange-800">Pending</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handleStatusFilter = (value: string) => {
    setStatusFilter(value);
    setCurrentPage(1);
  };

  const handleProfessionFilter = (value: string) => {
    setProfessionFilter(value);
    setCurrentPage(1);
  };

  if (loading && workers.length === 0) {
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
          <h1 className="text-3xl font-bold">Workers Management</h1>
          <p className="text-muted-foreground">
            Manage your assigned workers and their information
          </p>
        </div>
        <Button onClick={fetchWorkers} variant="outline">
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
                placeholder="Search workers..."
                value={searchTerm}
                onChange={(e) => handleSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            
            <Select value={statusFilter} onValueChange={handleStatusFilter}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
              </SelectContent>
            </Select>

            <Select value={professionFilter} onValueChange={handleProfessionFilter}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by profession" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Professions</SelectItem>
                {professions.map((profession: any) => (
                  <SelectItem key={profession.id} value={profession.id}>
                    {profession.name_en}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Button 
              onClick={() => {
                setSearchTerm('');
                setStatusFilter('');
                setProfessionFilter('');
                setCurrentPage(1);
              }}
              variant="outline"
            >
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Workers Grid */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {workers.map((worker) => (
          <Card key={worker.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg">{worker.name_en}</CardTitle>
                {getStatusBadge(worker.status)}
              </div>
              <p className="text-sm text-muted-foreground">{worker.name_ar}</p>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm">
                  <UserCheck className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">Passport:</span>
                  <span>{worker.passport_number}</span>
                </div>
                
                <div className="flex items-center gap-2 text-sm">
                  <Phone className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">Phone:</span>
                  <span>{worker.phone}</span>
                </div>
                
                {worker.email && (
                  <div className="flex items-center gap-2 text-sm">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                    <span className="font-medium">Email:</span>
                    <span>{worker.email}</span>
                  </div>
                )}
                
                <div className="flex items-center gap-2 text-sm">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">DOB:</span>
                  <span>{formatDate(worker.date_of_birth)}</span>
                </div>
              </div>

              <div className="pt-4 border-t">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Profession:</span>
                    <span className="font-medium">{worker.profession.name_en}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Nationality:</span>
                    <span className="font-medium">{worker.nationality.name_en}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Agency:</span>
                    <span className="font-medium">{worker.agency.name_en}</span>
                  </div>
                </div>
              </div>

              <div className="pt-4 border-t">
                <div className="flex gap-2">
                  <Button size="sm" className="flex-1">
                    <Eye className="h-4 w-4 mr-2" />
                    View Details
                  </Button>
                  <Button size="sm" variant="outline">
                    <Phone className="h-4 w-4" />
                  </Button>
                </div>
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
      {!loading && workers.length === 0 && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Users className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">No workers found</h3>
            <p className="text-muted-foreground text-center">
              {searchTerm || statusFilter || professionFilter
                ? 'Try adjusting your filters to see more results.'
                : 'You don\'t have any workers assigned to you yet.'}
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default Workers;