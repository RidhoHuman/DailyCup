'use client';

import { useState, useRef, DragEvent, useEffect } from 'react';
import { Upload, X, Image as ImageIcon, CheckCircle, AlertCircle } from 'lucide-react';
import { api } from '@/lib/api-client';
import { getErrorMessage } from '@/lib/utils';

interface ImageUploadProps {
  type: 'product' | 'category' | 'user' | 'general';
  resourceId?: number | string;
  currentImage?: string;
  onUploadSuccess?: (imageUrl: string) => void;
  onUploadError?: (error: string) => void;
  className?: string;
}

export default function ImageUpload({
  type,
  resourceId,
  currentImage,
  onUploadSuccess,
  onUploadError,
  className = '',
}: ImageUploadProps) {
  const [preview, setPreview] = useState<string | null>(currentImage || null);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [isDragging, setIsDragging] = useState(false);
  
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setPreview(currentImage || null);
  }, [currentImage]);

  const validateFile = (file: File): string | null => {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (!allowedTypes.includes(file.type)) {
      return 'Invalid file type. Only JPG, PNG, and WebP are allowed.';
    }

    if (file.size > maxSize) {
      return 'File too large. Maximum size is 5MB.';
    }

    return null;
  };

  const handleFileSelect = async (file: File) => {
    setError('');
    setSuccess('');
    
    const validationError = validateFile(file);
    if (validationError) {
      setError(validationError);
      if (onUploadError) onUploadError(validationError);
      return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
      setPreview(e.target?.result as string);
    };
    reader.readAsDataURL(file);

    // Upload file
    await uploadFile(file);
  };

  const uploadFile = async (file: File) => {
    try {
      setUploading(true);
      setProgress(0);

      const formData = new FormData();
      formData.append('image', file);
      formData.append('type', type);
      if (resourceId) {
        formData.append('resource_id', resourceId.toString());
      }

      // Simulate progress (since XMLHttpRequest doesn't work well with api-client)
      const progressInterval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 90) return prev;
          return prev + 10;
        });
      }, 200);

      const response = await api.post<{success: boolean; data: {url: string; message: string}}>('/upload_image.php', formData, {
        requiresAuth: true,
      });

      clearInterval(progressInterval);
      setProgress(100);

      if (response.success) {
        const imageUrl = response.data.url;
        setSuccess('Image uploaded successfully!');
        if (onUploadSuccess) onUploadSuccess(imageUrl);
        
        // Clear success message after 3 seconds
        setTimeout(() => setSuccess(''), 3000);
      } else {
        const msg = (response as { message?: string }).message ?? 'Upload failed';
        throw new Error(msg);
      }
    } catch (err: unknown) {
      console.error('Upload error:', getErrorMessage(err));
      const errorMsg = getErrorMessage(err) || 'Failed to upload image';
      setError(errorMsg);
      if (onUploadError) onUploadError(errorMsg);
      setPreview(currentImage || null);
    } finally {
      setUploading(false);
      setProgress(0);
    }
  };

  const handleDragEnter = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragLeave = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
  };

  const handleDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleFileSelect(files[0]);
    }
  };

  const handleRemove = () => {
    setPreview(null);
    setError('');
    setSuccess('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  return (
    <div className={`w-full ${className}`}>
      <input
        ref={fileInputRef}
        type="file"
        accept="image/jpeg,image/jpg,image/png,image/webp"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file) handleFileSelect(file);
        }}
        className="hidden"
      />

      {/* Upload Area */}
      <div
        onClick={() => !uploading && fileInputRef.current?.click()}
        onDragEnter={handleDragEnter}
        onDragLeave={handleDragLeave}
        onDragOver={handleDragOver}
        onDrop={handleDrop}
        className={`
          relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer
          transition-all duration-200
          ${isDragging ? 'border-[#a97456] bg-[#a97456]/10' : 'border-gray-300 hover:border-[#a97456]'}
          ${uploading ? 'pointer-events-none opacity-60' : ''}
        `}
      >
        {preview ? (
          <div className="relative">
            <img
              src={preview}
              alt="Preview"
              className="max-h-64 mx-auto rounded-lg object-contain"
            />
            {!uploading && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  handleRemove();
                }}
                className="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition"
              >
                <X size={16} />
              </button>
            )}
          </div>
        ) : (
          <div className="py-8">
            <div className={`
              mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4
              ${isDragging ? 'bg-[#a97456] text-white' : 'bg-gray-100 text-gray-400'}
            `}>
              {uploading ? (
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
              ) : (
                <Upload size={32} />
              )}
            </div>
            <p className="text-gray-600 font-medium mb-1">
              {uploading ? 'Uploading...' : 'Click to upload or drag and drop'}
            </p>
            <p className="text-sm text-gray-500">
              JPG, PNG or WebP (max 5MB)
            </p>
          </div>
        )}

        {/* Progress Bar */}
        {uploading && progress > 0 && (
          <div className="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 rounded-b-lg overflow-hidden">
            <div
              className="h-full bg-[#a97456] transition-all duration-300"
              style={{ width: `${progress}%` }}
            ></div>
          </div>
        )}
      </div>

      {/* Success Message */}
      {success && (
        <div className="mt-3 flex items-center gap-2 text-green-600 bg-green-50 border border-green-200 rounded-lg p-3">
          <CheckCircle size={18} />
          <span className="text-sm font-medium">{success}</span>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="mt-3 flex items-center gap-2 text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">
          <AlertCircle size={18} />
          <span className="text-sm font-medium">{error}</span>
        </div>
      )}

      {/* Upload Info */}
      <div className="mt-3 text-xs text-gray-500 space-y-1">
        <p>• Allowed formats: JPG, PNG, WebP</p>
        <p>• Maximum file size: 5MB</p>
        <p>• Recommended: Square images (1:1 ratio) for best display</p>
      </div>
    </div>
  );
}
