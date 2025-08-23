import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  UserPlus, 
  Plane, 
  CheckCircle, 
  FileText, 
  Clock, 
  AlertTriangle,
  UserCheck,
  UserX,
  Calendar,
  MapPin
} from 'lucide-react';
import DateDisplay from '../Internationalization/DateDisplay';

interface LifecycleEvent {
  id: string;
  type: 'recruitment' | 'arrival' | 'medical' | 'contract' | 'deployment' | 'completion' | 'problem' | 'termination';
  title: string;
  description: string;
  date: string;
  status: 'completed' | 'pending' | 'in_progress' | 'cancelled';
  location?: string;
  notes?: string;
}

interface WorkerLifecycleTimelineProps {
  events: LifecycleEvent[];
  workerName: string;
  className?: string;
}

const WorkerLifecycleTimeline: React.FC<WorkerLifecycleTimelineProps> = ({
  events,
  workerName,
  className = ''
}) => {
  const getEventIcon = (type: string) => {
    switch (type) {
      case 'recruitment':
        return <UserPlus className="h-5 w-5" />;
      case 'arrival':
        return <Plane className="h-5 w-5" />;
      case 'medical':
        return <CheckCircle className="h-5 w-5" />;
      case 'contract':
        return <FileText className="h-5 w-5" />;
      case 'deployment':
        return <UserCheck className="h-5 w-5" />;
      case 'completion':
        return <CheckCircle className="h-5 w-5" />;
      case 'problem':
        return <AlertTriangle className="h-5 w-5" />;
      case 'termination':
        return <UserX className="h-5 w-5" />;
      default:
        return <Clock className="h-5 w-5" />;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'completed':
        return <Badge className="bg-green-100 text-green-800">Completed</Badge>;
      case 'pending':
        return <Badge variant="outline" className="border-yellow-200 text-yellow-800">Pending</Badge>;
      case 'in_progress':
        return <Badge className="bg-blue-100 text-blue-800">In Progress</Badge>;
      case 'cancelled':
        return <Badge variant="destructive">Cancelled</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const getEventTypeColor = (type: string) => {
    switch (type) {
      case 'recruitment':
        return 'bg-blue-500';
      case 'arrival':
        return 'bg-green-500';
      case 'medical':
        return 'bg-purple-500';
      case 'contract':
        return 'bg-orange-500';
      case 'deployment':
        return 'bg-indigo-500';
      case 'completion':
        return 'bg-green-600';
      case 'problem':
        return 'bg-red-500';
      case 'termination':
        return 'bg-gray-500';
      default:
        return 'bg-gray-400';
    }
  };

  const sortedEvents = [...events].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Calendar className="h-5 w-5" />
          Worker Lifecycle Timeline
        </CardTitle>
        <p className="text-sm text-muted-foreground">
          Complete journey of {workerName} from recruitment to current status
        </p>
      </CardHeader>
      <CardContent>
        <div className="relative">
          {/* Timeline line */}
          <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
          
          <div className="space-y-6">
            {sortedEvents.map((event, index) => (
              <div key={event.id} className="relative flex items-start gap-4">
                {/* Timeline dot */}
                <div className={`relative z-10 flex h-12 w-12 items-center justify-center rounded-full ${getEventTypeColor(event.type)} text-white shadow-lg`}>
                  {getEventIcon(event.type)}
                </div>
                
                {/* Event content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h4 className="text-lg font-medium text-gray-900">{event.title}</h4>
                      <p className="text-sm text-gray-600 mt-1">{event.description}</p>
                      
                      <div className="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <div className="flex items-center gap-1">
                          <Calendar className="h-4 w-4" />
                          <DateDisplay date={event.date} format="short" />
                        </div>
                        
                        {event.location && (
                          <div className="flex items-center gap-1">
                            <MapPin className="h-4 w-4" />
                            <span>{event.location}</span>
                          </div>
                        )}
                      </div>
                      
                      {event.notes && (
                        <div className="mt-2 p-2 bg-gray-50 rounded text-sm text-gray-600">
                          <strong>Notes:</strong> {event.notes}
                        </div>
                      )}
                    </div>
                    
                    <div className="ml-4">
                      {getStatusBadge(event.status)}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {sortedEvents.length === 0 && (
            <div className="text-center py-8 text-gray-500">
              <Clock className="h-12 w-12 mx-auto mb-4 text-gray-300" />
              <p>No lifecycle events recorded yet.</p>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default WorkerLifecycleTimeline;