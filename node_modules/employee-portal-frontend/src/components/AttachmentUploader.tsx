import React, { useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { PaperClipIcon, XMarkIcon, DocumentIcon, PhotoIcon } from '@heroicons/react/24/outline';
import { api } from '../services/api';

interface Attachment {
  id: string;
  file_name: string;
  original_name: string;
  file_size: number;
  file_extension: string;
  mime_type: string;
  description?: string;
  uploaded_by: string;
  created_at: string;
  file_url: string;
  file_size_formatted: string;
  is_image: boolean;
  is_pdf: boolean;
  is_document: boolean;
}

interface AttachmentUploaderProps {
  entityName: string;
  entityId: string;
  attachments?: Attachment[];
  onAttachmentsChange?: (attachments: Attachment[]) => void;
  maxFiles?: number;
  allowedTypes?: string[];
  maxFileSize?: number; // in MB
  className?: string;
}

const AttachmentUploader: React.FC<AttachmentUploaderProps> = ({
  entityName,
  entityId,
  attachments = [],
  onAttachmentsChange,
  maxFiles = 10,
  allowedTypes = ['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
  maxFileSize = 10,
  className = '',
}) => {
  const { t } = useTranslation();
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    if (attachments.length + files.length > maxFiles) {
      setError(t('attachments.maxFilesExceeded', { max: maxFiles }));
      return;
    }

    setError(null);
    setIsUploading(true);
    setUploadProgress(0);

    try {
      const uploadedAttachments: Attachment[] = [];

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Validate file size
        if (file.size > maxFileSize * 1024 * 1024) {
          throw new Error(t('attachments.fileTooLarge', { maxSize: maxFileSize }));
        }

        // Validate file type
        const isValidType = allowedTypes.some(type => {
          if (type.endsWith('/*')) {
            return file.type.startsWith(type.replace('/*', ''));
          }
          return file.type === type;
        });

        if (!isValidType) {
          throw new Error(t('attachments.invalidFileType'));
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('entity_name', entityName);
        formData.append('entity_id', entityId);

        const response = await api.post('/attachments/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
              setUploadProgress(progress);
            }
          },
        });

        if (response.data.success) {
          uploadedAttachments.push(response.data.data);
        }
      }

      const newAttachments = [...attachments, ...uploadedAttachments];
      onAttachmentsChange?.(newAttachments);
      
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || t('attachments.uploadError'));
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleDeleteAttachment = async (attachmentId: string) => {
    try {
      await api.delete(`/attachments/${attachmentId}`);
      const newAttachments = attachments.filter(att => att.id !== attachmentId);
      onAttachmentsChange?.(newAttachments);
    } catch (err: any) {
      setError(err.response?.data?.message || t('attachments.deleteError'));
    }
  };

  const handleDownload = async (attachment: Attachment) => {
    try {
      const response = await api.get(`/attachments/${attachment.id}/download`, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', attachment.original_name);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err: any) {
      setError(err.response?.data?.message || t('attachments.downloadError'));
    }
  };

  const getFileIcon = (attachment: Attachment) => {
    if (attachment.is_image) {
      return <PhotoIcon className="w-6 h-6 text-blue-500" />;
    }
    if (attachment.is_pdf) {
      return <DocumentIcon className="w-6 h-6 text-red-500" />;
    }
    if (attachment.is_document) {
      return <DocumentIcon className="w-6 h-6 text-green-500" />;
    }
    return <DocumentIcon className="w-6 h-6 text-gray-500" />;
  };

  return (
    <div className={`space-y-4 ${className}`}>
      {/* Upload Area */}
      <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept={allowedTypes.join(',')}
          onChange={handleFileSelect}
          className="hidden"
          disabled={isUploading}
        />
        
        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={isUploading || attachments.length >= maxFiles}
          className="inline-flex items-center space-x-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <PaperClipIcon className="w-6 h-6" />
          <span>
            {isUploading 
              ? t('attachments.uploading') 
              : t('attachments.selectFiles', { max: maxFiles - attachments.length })
            }
          </span>
        </button>

        {isUploading && (
          <div className="mt-4">
            <div className="w-full bg-gray-200 rounded-full h-2">
              <div 
                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                style={{ width: `${uploadProgress}%` }}
              />
            </div>
            <p className="text-sm text-gray-600 mt-2">{uploadProgress}%</p>
          </div>
        )}

        <p className="text-xs text-gray-500 mt-2">
          {t('attachments.maxFileSize', { size: maxFileSize })} • {t('attachments.allowedTypes')}
        </p>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-md p-3">
          <p className="text-sm text-red-600">{error}</p>
        </div>
      )}

      {/* Attachments List */}
      {attachments.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-sm font-medium text-gray-900">
            {t('attachments.uploadedFiles')} ({attachments.length})
          </h4>
          
          <div className="space-y-2">
            {attachments.map((attachment) => (
              <div
                key={attachment.id}
                className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div className="flex items-center space-x-3 flex-1 min-w-0">
                  {getFileIcon(attachment)}
                  
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                      {attachment.original_name}
                    </p>
                    <p className="text-xs text-gray-500">
                      {attachment.file_size_formatted} • {new Date(attachment.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>

                <div className="flex items-center space-x-2">
                  <button
                    type="button"
                    onClick={() => handleDownload(attachment)}
                    className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                  >
                    {t('attachments.download')}
                  </button>
                  
                  <button
                    type="button"
                    onClick={() => handleDeleteAttachment(attachment.id)}
                    className="text-red-600 hover:text-red-800"
                  >
                    <XMarkIcon className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default AttachmentUploader;