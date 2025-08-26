import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { 
  MessageSquare, 
  Plus, 
  Edit, 
  Trash2, 
  User, 
  Calendar,
  Building,
  CheckCircle,
  Clock,
  AlertCircle
} from 'lucide-react';
import DateDisplay from '../Internationalization/DateDisplay';

interface AgencyNote {
  id: string;
  content: string;
  type: 'general' | 'workflow' | 'issue' | 'follow_up' | 'approval';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'active' | 'resolved' | 'pending';
  created_by: {
    id: string;
    name: string;
    role: string;
  };
  created_at: string;
  updated_at?: string;
  assigned_to?: {
    id: string;
    name: string;
  };
  due_date?: string;
  tags?: string[];
}

interface AgencyNotesPanelProps {
  notes: AgencyNote[];
  agencyId: string;
  agencyName: string;
  onAddNote: (note: Omit<AgencyNote, 'id' | 'created_at' | 'updated_at'>) => void;
  onUpdateNote: (id: string, note: Partial<AgencyNote>) => void;
  onDeleteNote: (id: string) => void;
  className?: string;
}

const AgencyNotesPanel: React.FC<AgencyNotesPanelProps> = ({
  notes,
  agencyId,
  agencyName,
  onAddNote,
  onUpdateNote,
  onDeleteNote,
  className = ''
}) => {
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingNote, setEditingNote] = useState<string | null>(null);
  const [newNote, setNewNote] = useState({
    content: '',
    type: 'general' as const,
    priority: 'medium' as const,
    status: 'active' as const,
    tags: [] as string[]
  });

  const getNoteTypeIcon = (type: string) => {
    switch (type) {
      case 'workflow':
        return <CheckCircle className="h-4 w-4" />;
      case 'issue':
        return <AlertCircle className="h-4 w-4" />;
      case 'follow_up':
        return <Clock className="h-4 w-4" />;
      case 'approval':
        return <CheckCircle className="h-4 w-4" />;
      default:
        return <MessageSquare className="h-4 w-4" />;
    }
  };

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'urgent':
        return <Badge variant="destructive">Urgent</Badge>;
      case 'high':
        return <Badge className="bg-red-100 text-red-800">High</Badge>;
      case 'medium':
        return <Badge className="bg-yellow-100 text-yellow-800">Medium</Badge>;
      case 'low':
        return <Badge className="bg-green-100 text-green-800">Low</Badge>;
      default:
        return <Badge variant="secondary">{priority}</Badge>;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-blue-100 text-blue-800">Active</Badge>;
      case 'resolved':
        return <Badge className="bg-green-100 text-green-800">Resolved</Badge>;
      case 'pending':
        return <Badge variant="outline" className="border-orange-200 text-orange-800">Pending</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const getTypeBadge = (type: string) => {
    const typeLabels = {
      'general': 'General',
      'workflow': 'Workflow',
      'issue': 'Issue',
      'follow_up': 'Follow Up',
      'approval': 'Approval'
    };

    const typeColors = {
      'general': 'bg-gray-100 text-gray-800',
      'workflow': 'bg-blue-100 text-blue-800',
      'issue': 'bg-red-100 text-red-800',
      'follow_up': 'bg-orange-100 text-orange-800',
      'approval': 'bg-green-100 text-green-800'
    };

    return (
      <Badge className={typeColors[type as keyof typeof typeColors] || 'bg-gray-100 text-gray-800'}>
        {typeLabels[type as keyof typeof typeLabels] || type}
      </Badge>
    );
  };

  const handleAddNote = () => {
    if (newNote.content.trim()) {
      onAddNote({
        content: newNote.content,
        type: newNote.type,
        priority: newNote.priority,
        status: newNote.status,
        tags: newNote.tags,
        created_by: {
          id: 'current-user-id', // This should come from auth context
          name: 'Current User',
          role: 'Employee'
        }
      });
      setNewNote({
        content: '',
        type: 'general',
        priority: 'medium',
        status: 'active',
        tags: []
      });
      setShowAddForm(false);
    }
  };

  const handleUpdateNote = (id: string) => {
    const note = notes.find(n => n.id === id);
    if (note && newNote.content.trim()) {
      onUpdateNote(id, {
        content: newNote.content,
        type: newNote.type,
        priority: newNote.priority,
        status: newNote.status,
        tags: newNote.tags
      });
      setEditingNote(null);
      setNewNote({
        content: '',
        type: 'general',
        priority: 'medium',
        status: 'active',
        tags: []
      });
    }
  };

  const startEditing = (note: AgencyNote) => {
    setEditingNote(note.id);
    setNewNote({
      content: note.content,
      type: note.type,
      priority: note.priority,
      status: note.status,
      tags: note.tags || []
    });
  };

  const cancelEditing = () => {
    setEditingNote(null);
    setShowAddForm(false);
    setNewNote({
      content: '',
      type: 'general',
      priority: 'medium',
      status: 'active',
      tags: []
    });
  };

  return (
    <Card className={className}>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Building className="h-5 w-5" />
              Agency Notes & Workflow
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Manage notes and workflow for {agencyName}
            </p>
          </div>
          <Button
            onClick={() => setShowAddForm(true)}
            size="sm"
            className="flex items-center gap-2"
          >
            <Plus className="h-4 w-4" />
            Add Note
          </Button>
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Add/Edit Note Form */}
        {(showAddForm || editingNote) && (
          <Card className="border-2 border-blue-200 bg-blue-50">
            <CardContent className="pt-4">
              <div className="space-y-4">
                <Textarea
                  placeholder="Enter note content..."
                  value={newNote.content}
                  onChange={(e) => setNewNote({ ...newNote, content: e.target.value })}
                  rows={3}
                />
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium">Type</label>
                    <select
                      value={newNote.type}
                      onChange={(e) => setNewNote({ ...newNote, type: e.target.value as any })}
                      className="w-full mt-1 p-2 border rounded-md"
                    >
                      <option value="general">General</option>
                      <option value="workflow">Workflow</option>
                      <option value="issue">Issue</option>
                      <option value="follow_up">Follow Up</option>
                      <option value="approval">Approval</option>
                    </select>
                  </div>
                  
                  <div>
                    <label className="text-sm font-medium">Priority</label>
                    <select
                      value={newNote.priority}
                      onChange={(e) => setNewNote({ ...newNote, priority: e.target.value as any })}
                      className="w-full mt-1 p-2 border rounded-md"
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                </div>
                
                <div className="flex gap-2">
                  <Button
                    onClick={editingNote ? () => handleUpdateNote(editingNote) : handleAddNote}
                    size="sm"
                  >
                    {editingNote ? 'Update Note' : 'Add Note'}
                  </Button>
                  <Button
                    onClick={cancelEditing}
                    variant="outline"
                    size="sm"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Notes List */}
        <div className="space-y-4">
          {notes.map((note) => (
            <Card key={note.id} className="hover:shadow-md transition-shadow">
              <CardContent className="pt-4">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      {getNoteTypeIcon(note.type)}
                      {getTypeBadge(note.type)}
                      {getPriorityBadge(note.priority)}
                      {getStatusBadge(note.status)}
                    </div>
                    
                    <p className="text-gray-700 mb-3">{note.content}</p>
                    
                    <div className="flex items-center gap-4 text-sm text-gray-500">
                      <div className="flex items-center gap-1">
                        <User className="h-4 w-4" />
                        <span>{note.created_by.name}</span>
                      </div>
                      
                      <div className="flex items-center gap-1">
                        <Calendar className="h-4 w-4" />
                        <DateDisplay date={note.created_at} format="short" />
                      </div>
                      
                      {note.due_date && (
                        <div className="flex items-center gap-1">
                          <Clock className="h-4 w-4" />
                          <span>Due: <DateDisplay date={note.due_date} format="short" /></span>
                        </div>
                      )}
                    </div>
                    
                    {note.tags && note.tags.length > 0 && (
                      <div className="flex gap-1 mt-2">
                        {note.tags.map((tag, index) => (
                          <Badge key={index} variant="outline" className="text-xs">
                            {tag}
                          </Badge>
                        ))}
                      </div>
                    )}
                  </div>
                  
                  <div className="flex gap-1 ml-4">
                    <Button
                      onClick={() => startEditing(note)}
                      size="sm"
                      variant="outline"
                    >
                      <Edit className="h-3 w-3" />
                    </Button>
                    <Button
                      onClick={() => onDeleteNote(note.id)}
                      size="sm"
                      variant="outline"
                      className="text-red-600 hover:text-red-700"
                    >
                      <Trash2 className="h-3 w-3" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
          
          {notes.length === 0 && (
            <div className="text-center py-8 text-gray-500">
              <MessageSquare className="h-12 w-12 mx-auto mb-4 text-gray-300" />
              <p>No notes found for this agency.</p>
              <p className="text-sm">Click "Add Note" to create the first note.</p>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default AgencyNotesPanel;