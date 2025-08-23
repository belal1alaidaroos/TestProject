<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function uploadFile(UploadedFile $file, string $entityName, string $entityId, string $uploadedBy, ?string $description = null): Attachment
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $fileName = $this->generateFileName($file);
        
        // Store file
        $filePath = $this->storeFile($file, $fileName, $entityName);
        
        // Create attachment record
        $attachment = Attachment::create([
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_extension' => $file->getClientOriginalExtension(),
            'description' => $description,
            'uploaded_by' => $uploadedBy,
        ]);

        return $attachment;
    }

    public function deleteAttachment(string $attachmentId, string $userId): bool
    {
        $attachment = Attachment::findOrFail($attachmentId);
        
        // Check permissions (only uploader or admin can delete)
        if ($attachment->uploaded_by !== $userId && !$this->isAdmin($userId)) {
            throw new \Exception('Unauthorized to delete this attachment');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Delete record
        return $attachment->delete();
    }

    public function getAttachments(string $entityName, string $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return Attachment::byEntity($entityName, $entityId)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAttachment(string $attachmentId): Attachment
    {
        return Attachment::with('uploadedBy')->findOrFail($attachmentId);
    }

    public function downloadAttachment(string $attachmentId, string $userId): array
    {
        $attachment = $this->getAttachment($attachmentId);
        
        // Check permissions
        if (!$this->canAccessAttachment($attachment, $userId)) {
            throw new \Exception('Unauthorized to access this attachment');
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        return [
            'path' => $filePath,
            'name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
        ];
    }

    public function updateAttachmentDescription(string $attachmentId, string $description, string $userId): Attachment
    {
        $attachment = Attachment::findOrFail($attachmentId);
        
        // Check permissions
        if ($attachment->uploaded_by !== $userId && !$this->isAdmin($userId)) {
            throw new \Exception('Unauthorized to update this attachment');
        }

        $attachment->update(['description' => $description]);
        
        return $attachment;
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxSize = config('app.max_file_size', 10 * 1024 * 1024); // 10MB
        $allowedTypes = config('app.allowed_file_types', [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('File type not allowed');
        }
    }

    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    private function storeFile(UploadedFile $file, string $fileName, string $entityName): string
    {
        $directory = "attachments/{$entityName}/" . now()->format('Y/m');
        $filePath = $file->storeAs($directory, $fileName, 'public');
        
        return $filePath;
    }

    private function canAccessAttachment(Attachment $attachment, string $userId): bool
    {
        // Admin can access all attachments
        if ($this->isAdmin($userId)) {
            return true;
        }

        // Uploader can access their own attachments
        if ($attachment->uploaded_by === $userId) {
            return true;
        }

        // Check entity-specific permissions
        switch ($attachment->entity_name) {
            case 'Contract':
                return $this->canAccessContract($attachment->entity_id, $userId);
            case 'Worker':
                return $this->canAccessWorker($attachment->entity_id, $userId);
            case 'CustomerAddress':
                return $this->canAccessCustomerAddress($attachment->entity_id, $userId);
            case 'WorkerProblem':
                return $this->canAccessWorkerProblem($attachment->entity_id, $userId);
            default:
                return false;
        }
    }

    private function isAdmin(string $userId): bool
    {
        $user = \App\Models\AppUser::find($userId);
        return $user && $user->user_type === 'Internal';
    }

    private function canAccessContract(string $contractId, string $userId): bool
    {
        $contract = \App\Models\Contract::find($contractId);
        if (!$contract) return false;

        $user = \App\Models\AppUser::find($userId);
        if (!$user) return false;

        // Customer can access their own contracts
        if ($user->user_type === 'Customer' && $contract->customer_id === $user->customer->id) {
            return true;
        }

        // Internal users can access all contracts
        if ($user->user_type === 'Internal') {
            return true;
        }

        return false;
    }

    private function canAccessWorker(string $workerId, string $userId): bool
    {
        $worker = \App\Models\Worker::find($workerId);
        if (!$worker) return false;

        $user = \App\Models\AppUser::find($userId);
        if (!$user) return false;

        // Agency can access their own workers
        if ($user->user_type === 'Agency' && $worker->agency_id === $user->agency_id) {
            return true;
        }

        // Internal users can access all workers
        if ($user->user_type === 'Internal') {
            return true;
        }

        return false;
    }

    private function canAccessCustomerAddress(string $addressId, string $userId): bool
    {
        $address = \App\Models\CustomerAddress::find($addressId);
        if (!$address) return false;

        $user = \App\Models\AppUser::find($userId);
        if (!$user) return false;

        // Customer can access their own addresses
        if ($user->user_type === 'Customer' && $address->customer_id === $user->customer->id) {
            return true;
        }

        // Internal users can access all addresses
        if ($user->user_type === 'Internal') {
            return true;
        }

        return false;
    }

    private function canAccessWorkerProblem(string $problemId, string $userId): bool
    {
        $problem = \App\Models\WorkerProblem::find($problemId);
        if (!$problem) return false;

        $user = \App\Models\AppUser::find($userId);
        if (!$user) return false;

        // Creator can access their own problems
        if ($problem->created_by === $userId) {
            return true;
        }

        // Internal users can access all problems
        if ($user->user_type === 'Internal') {
            return true;
        }

        return false;
    }
}