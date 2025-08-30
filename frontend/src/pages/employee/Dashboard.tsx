import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
  Users, 
  FileText, 
  Calendar, 
  CheckCircle, 
  Bell, 
  TrendingUp,
  Clock,
  AlertCircle
} from 'lucide-react';
import { useAuthStore } from '../../stores/authStore';
import { employeeAPI } from '../../services/api';

interface DashboardStats {
  total_workers_managed: number;
  active_contracts: number;
  pending_reservations: number;
  completed_tasks: number;
  recent_notifications: number;
}

interface TasksSummary {
  workers: {
    total: number;
    active: number;
    inactive: number;
  };
  contracts: {
    total: number;
    active: number;
    pending: number;
    completed: number;
  };
  reservations: {
    total: number;
    pending: number;
    confirmed: number;
  };
}

const Dashboard: React.FC = () => {
  const { user } = useAuthStore();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [tasksSummary, setTasksSummary] = useState<TasksSummary | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const [statsResponse, tasksResponse] = await Promise.all([
        employeeAPI.getWorkers({ per_page: 1 }), // Using existing endpoint as placeholder
        employeeAPI.getReservations({ per_page: 1 }) // Using existing endpoint as placeholder
      ]);

      if (statsResponse.success) {
        setStats(statsResponse.data);
      }

      if (tasksResponse.success) {
        setTasksSummary(tasksResponse.data);
      }
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ 
    title, 
    value, 
    icon: Icon, 
    color = 'blue',
    subtitle 
  }: {
    title: string;
    value: number;
    icon: React.ComponentType<any>;
    color?: string;
    subtitle?: string;
  }) => (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className={`h-4 w-4 text-${color}-600`} />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        {subtitle && (
          <p className="text-xs text-muted-foreground">{subtitle}</p>
        )}
      </CardContent>
    </Card>
  );

  if (loading) {
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
          <h1 className="text-3xl font-bold">Employee Dashboard</h1>
          <p className="text-muted-foreground">
            Welcome back, {user?.name}! Here's your work overview.
          </p>
        </div>
        <Button onClick={fetchDashboardData} variant="outline">
          Refresh
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Workers Managed"
          value={stats?.total_workers_managed || 0}
          icon={Users}
          color="blue"
          subtitle="Total assigned workers"
        />
        <StatCard
          title="Active Contracts"
          value={stats?.active_contracts || 0}
          icon={FileText}
          color="green"
          subtitle="Currently active"
        />
        <StatCard
          title="Pending Reservations"
          value={stats?.pending_reservations || 0}
          icon={Calendar}
          color="orange"
          subtitle="Awaiting action"
        />
        <StatCard
          title="Completed Tasks"
          value={stats?.completed_tasks || 0}
          icon={CheckCircle}
          color="purple"
          subtitle="Successfully completed"
        />
        <StatCard
          title="Worker Problems"
          value={tasksSummary?.problems?.pending || 0}
          icon={AlertCircle}
          color="red"
          subtitle="Pending issues"
        />
      </div>

      {/* Tasks Summary */}
      {tasksSummary && (
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {/* Workers Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                Workers Summary
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Total Workers</span>
                <Badge variant="secondary">{tasksSummary.workers.total}</Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Active</span>
                <Badge variant="default" className="bg-green-100 text-green-800">
                  {tasksSummary.workers.active}
                </Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Inactive</span>
                <Badge variant="destructive">{tasksSummary.workers.inactive}</Badge>
              </div>
            </CardContent>
          </Card>

          {/* Contracts Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileText className="h-5 w-5" />
                Contracts Summary
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Total Contracts</span>
                <Badge variant="secondary">{tasksSummary.contracts.total}</Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Active</span>
                <Badge variant="default" className="bg-green-100 text-green-800">
                  {tasksSummary.contracts.active}
                </Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Pending</span>
                <Badge variant="outline" className="border-orange-200 text-orange-800">
                  {tasksSummary.contracts.pending}
                </Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Completed</span>
                <Badge variant="default" className="bg-purple-100 text-purple-800">
                  {tasksSummary.contracts.completed}
                </Badge>
              </div>
            </CardContent>
          </Card>

          {/* Reservations Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                Reservations Summary
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Total Reservations</span>
                <Badge variant="secondary">{tasksSummary.reservations.total}</Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Pending</span>
                <Badge variant="outline" className="border-orange-200 text-orange-800">
                  {tasksSummary.reservations.pending}
                </Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Confirmed</span>
                <Badge variant="default" className="bg-green-100 text-green-800">
                  {tasksSummary.reservations.confirmed}
                </Badge>
              </div>
            </CardContent>
          </Card>

          {/* Problems Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5" />
                Problems Summary
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Total Problems</span>
                <Badge variant="secondary">{tasksSummary.problems?.total || 0}</Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Pending</span>
                <Badge variant="outline" className="border-orange-200 text-orange-800">
                  {tasksSummary.problems?.pending || 0}
                </Badge>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Resolved</span>
                <Badge variant="default" className="bg-green-100 text-green-800">
                  {tasksSummary.problems?.resolved || 0}
                </Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Button variant="outline" className="h-20 flex flex-col gap-2">
              <Users className="h-6 w-6" />
              <span>View Workers</span>
            </Button>
            <Button variant="outline" className="h-20 flex flex-col gap-2">
              <FileText className="h-6 w-6" />
              <span>Manage Contracts</span>
            </Button>
            <Button variant="outline" className="h-20 flex flex-col gap-2">
              <Calendar className="h-6 w-6" />
              <span>Check Reservations</span>
            </Button>
            <Button variant="outline" className="h-20 flex flex-col gap-2">
              <Bell className="h-6 w-6" />
              <span>Notifications</span>
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Recent Activity */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Activity</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center gap-4 p-4 border rounded-lg">
              <div className="p-2 bg-blue-100 rounded-full">
                <CheckCircle className="h-4 w-4 text-blue-600" />
              </div>
              <div className="flex-1">
                <p className="font-medium">Contract #12345 completed</p>
                <p className="text-sm text-muted-foreground">Worker assignment successfully completed</p>
              </div>
              <span className="text-sm text-muted-foreground">2 hours ago</span>
            </div>
            
            <div className="flex items-center gap-4 p-4 border rounded-lg">
              <div className="p-2 bg-orange-100 rounded-full">
                <Clock className="h-4 w-4 text-orange-600" />
              </div>
              <div className="flex-1">
                <p className="font-medium">New reservation request</p>
                <p className="text-sm text-muted-foreground">Customer requested worker reservation</p>
              </div>
              <span className="text-sm text-muted-foreground">4 hours ago</span>
            </div>
            
            <div className="flex items-center gap-4 p-4 border rounded-lg">
              <div className="p-2 bg-green-100 rounded-full">
                <TrendingUp className="h-4 w-4 text-green-600" />
              </div>
              <div className="flex-1">
                <p className="font-medium">Worker status updated</p>
                <p className="text-sm text-muted-foreground">Worker #789 marked as active</p>
              </div>
              <span className="text-sm text-muted-foreground">1 day ago</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default Dashboard;