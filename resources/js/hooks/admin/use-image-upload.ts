import { useState } from 'react';

import * as MediaController from '@/actions/App/Http/Controllers/Admin/MediaController';
import { httpPost, isHttpError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { MediaItem } from '@/types/media';

interface UseImageUploadOptions {
    generateThumbnail?: boolean;
    preserveFormat?: boolean;
    onSuccess?: (imageType: string, data: MediaItem) => void;
}

export function useImageUpload({
    generateThumbnail = true,
    preserveFormat = false,
    onSuccess,
}: UseImageUploadOptions = {}) {
    const [uploading, setUploading] = useState<Record<string, boolean>>({});
    const [uploadingError, setUploadingError] = useState<Record<string, string>>({});
    const [isDragging, setIsDragging] = useState<Record<string, boolean>>({});

    const handleImageUpload = async (file: File, imageType: string) => {
        if (!file) return;

        setUploading((prev) => ({ ...prev, [imageType]: true }));
        setUploadingError((prev) => ({ ...prev, [imageType]: '' }));

        const formData = new FormData();
        formData.append('file', file);
        formData.append('generate_thumbnail', generateThumbnail ? '1' : '0');
        formData.append('preserve_format', preserveFormat ? '1' : '0');

        try {
            const data = await httpPost<MediaItem>(MediaController.store(), formData);

            onSuccess?.(imageType, data);
        } catch (error) {
            let errorMessage = __('Failed to upload media.');
            if (isHttpError(error) && error.data?.message) {
                errorMessage = error.data.message as string;
            }

            setUploadingError((prev) => ({ ...prev, [imageType]: errorMessage }));
        } finally {
            setUploading((prev) => ({ ...prev, [imageType]: false }));
        }
    };

    const handleFileInputChange = (event: React.ChangeEvent<HTMLInputElement>, imageType: string) => {
        const file = event.target.files?.[0];

        if (file) {
            handleImageUpload(file, imageType);
        }
    };

    const handleDragEnter = (e: React.DragEvent<HTMLDivElement>, imageType: string) => {
        e.preventDefault();
        e.stopPropagation();

        setIsDragging((prev) => ({ ...prev, [imageType]: true }));
    };

    const handleDragLeave = (e: React.DragEvent<HTMLDivElement>, imageType: string) => {
        e.preventDefault();
        e.stopPropagation();

        if (e.currentTarget.contains(e.relatedTarget as Node)) {
            return;
        }

        setIsDragging((prev) => ({ ...prev, [imageType]: false }));
    };

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>, imageType: string) => {
        e.preventDefault();
        e.stopPropagation();

        setIsDragging((prev) => ({ ...prev, [imageType]: false }));

        if (uploading[imageType]) {
            return;
        }

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleImageUpload(e.dataTransfer.files[0], imageType);
        }
    };

    const clearError = (imageType: string) => {
        setUploadingError((prev) => ({ ...prev, [imageType]: '' }));
    };

    return {
        uploading,
        uploadingError,
        isDragging,
        handleFileInputChange,
        handleDragEnter,
        handleDragLeave,
        handleDragOver,
        handleDrop,
        clearError,
    };
}
