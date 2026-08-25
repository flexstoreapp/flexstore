import { useCallback, useRef, useState } from 'react';

import { __, transChoice } from '@/lib/i18n';
import { humanFileSize } from '@/lib/utils';
import type { MediaDimensions } from '@/types/media';

export type FileMetadata = MediaDimensions & {
    name: string;
    size: number;
    type: string;
    url: string;
    id: string;
    mediaId?: number;
};

export type FileWithPreview = {
    file: File | FileMetadata;
    id: string;
    preview?: string;
};

export type FileUploadOptions = {
    maxFiles?: number;
    maxSize?: number; // in bytes
    accept?: string;
    multiple?: boolean;
    initialFiles?: FileMetadata[];
    onFilesAdded?: (addedFiles: FileWithPreview[]) => void;
    onFilesChange?: (files: FileWithPreview[]) => void;
};

export type FileUploadState = {
    files: FileWithPreview[];
    isDragging: boolean;
    errors: string[];
};

export type FileUploadActions = {
    addFiles: (files: FileList | File[]) => void;
    removeFile: (id: string) => void;
    setFiles: (files: FileWithPreview[]) => void;
    clearFiles: () => void;
    clearErrors: () => void;
    handleDragEnter: (e: React.DragEvent<HTMLElement>) => void;
    handleDragLeave: (e: React.DragEvent<HTMLElement>) => void;
    handleDragOver: (e: React.DragEvent<HTMLElement>) => void;
    handleDrop: (e: React.DragEvent<HTMLElement>) => void;
    handleFileChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    openFileDialog: () => void;
    updateFileWithServerPath: (id: string, serverPath: string, mediaId?: number, dimensions?: MediaDimensions) => void;
    getInputProps: (
        props?: React.InputHTMLAttributes<HTMLInputElement>,
    ) => React.InputHTMLAttributes<HTMLInputElement> & {
        ref: React.Ref<HTMLInputElement>;
    };
};

export function useFileUpload(options: FileUploadOptions = {}): [FileUploadState, FileUploadActions] {
    const {
        maxFiles = Infinity,
        maxSize = Infinity,
        accept = '*',
        multiple = false,
        initialFiles = [],
        onFilesAdded,
        onFilesChange,
    } = options;

    const [state, setState] = useState<FileUploadState>({
        files: initialFiles.map((file) => ({
            file,
            id: file.id,
            preview: file.url,
        })),
        isDragging: false,
        errors: [],
    });

    const inputRef = useRef<HTMLInputElement>(null);

    const validateFile = useCallback(
        (file: File | FileMetadata): string | null => {
            if (file.size > maxSize) {
                return __('File ":name" exceeds the maximum size of :size.', {
                    name: file.name,
                    size: humanFileSize(maxSize),
                });
            }

            if (accept !== '*') {
                const acceptedTypes = accept.split(',').map((type) => type.trim());
                const fileType = file.type ?? '';
                const fileExtension = `.${file.name.split('.').pop()}`;

                const isAccepted = acceptedTypes.some((type) => {
                    if (type.startsWith('.')) {
                        return fileExtension.toLowerCase() === type.toLowerCase();
                    }
                    if (type.endsWith('/*')) {
                        const baseType = type.split('/')[0];
                        return fileType.startsWith(`${baseType}/`);
                    }
                    return fileType === type;
                });

                if (!isAccepted) {
                    return __('File ":name" is not an accepted file type.', { name: file.name });
                }
            }

            return null;
        },
        [accept, maxSize],
    );

    const createPreview = useCallback((file: File | FileMetadata): string | undefined => {
        if (file instanceof File) return URL.createObjectURL(file);
        return file.url;
    }, []);

    const generateUniqueId = useCallback((file: File | FileMetadata): string => {
        if (file instanceof File) {
            return `${file.name}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
        }

        return file.id;
    }, []);

    const clearFiles = useCallback(() => {
        setState((prev) => {
            prev.files.forEach((file) => {
                if (file.preview && file.file instanceof File && file.file.type.startsWith('image/')) {
                    URL.revokeObjectURL(file.preview);
                }
            });

            if (inputRef.current) {
                inputRef.current.value = '';
            }

            const newState = {
                ...prev,
                files: [],
                errors: [],
            };

            onFilesChange?.(newState.files);
            return newState;
        });
    }, [onFilesChange]);

    const addFiles = useCallback(
        (newFiles: FileList | File[]) => {
            if (!newFiles || newFiles.length === 0) return;

            const newFilesArray = Array.from(newFiles);
            const errors: string[] = [];

            setState((prev) => ({ ...prev, errors: [] }));

            if (!multiple) clearFiles();

            if (multiple && maxFiles !== Infinity && state.files.length + newFilesArray.length > maxFiles) {
                errors.push(
                    transChoice(
                        'You can only upload a maximum of :count file|You can only upload a maximum of :count files',
                        maxFiles,
                    ),
                );
                setState((prev) => ({ ...prev, errors }));
                return;
            }

            const validFiles: FileWithPreview[] = [];

            newFilesArray.forEach((file) => {
                const isDuplicate = state.files.some(
                    (existingFile) => existingFile.file.name === file.name && existingFile.file.size === file.size,
                );

                if (isDuplicate) return;

                if (file.size > maxSize) {
                    errors.push(
                        multiple
                            ? __('Some files exceed the maximum size of :size.', { size: humanFileSize(maxSize) })
                            : __('File ":name" exceeds the maximum size of :size.', {
                                  name: file.name,
                                  size: humanFileSize(maxSize),
                              }),
                    );
                    return;
                }

                const error = validateFile(file);
                if (error) {
                    errors.push(error);
                } else {
                    validFiles.push({
                        file,
                        id: generateUniqueId(file),
                        preview: createPreview(file),
                    });
                }
            });

            if (validFiles.length > 0) {
                onFilesAdded?.(validFiles);

                setState((prev) => {
                    const newFiles = !multiple ? validFiles : [...prev.files, ...validFiles];
                    onFilesChange?.(newFiles);
                    return {
                        ...prev,
                        files: newFiles,
                        errors,
                    };
                });
            } else if (errors.length > 0) {
                setState((prev) => ({
                    ...prev,
                    errors,
                }));
            }

            if (inputRef.current) {
                inputRef.current.value = '';
            }
        },
        [
            state.files,
            maxFiles,
            multiple,
            maxSize,
            validateFile,
            createPreview,
            generateUniqueId,
            clearFiles,
            onFilesAdded,
            onFilesChange,
        ],
    );

    const removeFile = useCallback(
        (id: string) => {
            setState((prev) => {
                const fileToRemove = prev.files.find((file) => file.id === id);
                if (
                    fileToRemove &&
                    fileToRemove.preview &&
                    fileToRemove.file instanceof File &&
                    fileToRemove.file.type.startsWith('image/')
                ) {
                    URL.revokeObjectURL(fileToRemove.preview);
                }

                const newFiles = prev.files.filter((file) => file.id !== id);
                onFilesChange?.(newFiles);

                return {
                    ...prev,
                    files: newFiles,
                    errors: [],
                };
            });
        },
        [onFilesChange],
    );

    const setFiles = useCallback(
        (newFiles: FileWithPreview[]) => {
            setState((prev) => {
                onFilesChange?.(newFiles);
                return {
                    ...prev,
                    files: newFiles,
                };
            });
        },
        [onFilesChange],
    );

    const updateFileWithServerPath = useCallback(
        (id: string, serverPath: string, mediaId?: number, dimensions?: MediaDimensions) => {
            setState((prev) => {
                const fileIndex = prev.files.findIndex((file) => file.id === id);

                if (fileIndex === -1) return prev;

                const fileToUpdate = prev.files[fileIndex];

                const fileMetadata: FileMetadata = {
                    name: fileToUpdate.file instanceof File ? fileToUpdate.file.name : fileToUpdate.file.name,
                    size: fileToUpdate.file instanceof File ? fileToUpdate.file.size : fileToUpdate.file.size,
                    type: fileToUpdate.file instanceof File ? fileToUpdate.file.type : fileToUpdate.file.type,
                    url: serverPath,
                    id: fileToUpdate.id,
                    mediaId,
                    width: dimensions?.width,
                    height: dimensions?.height,
                };

                const updatedFile: FileWithPreview = {
                    ...fileToUpdate,
                    file: fileMetadata,
                };

                const newFiles = [...prev.files];
                newFiles[fileIndex] = updatedFile;

                onFilesChange?.(newFiles);

                return {
                    ...prev,
                    files: newFiles,
                };
            });
        },
        [onFilesChange],
    );

    const clearErrors = useCallback(() => {
        setState((prev) => ({
            ...prev,
            errors: [],
        }));
    }, []);

    const handleDragEnter = useCallback((e: React.DragEvent<HTMLElement>) => {
        const hasFiles = Array.from(e.dataTransfer?.types ?? []).includes('Files');
        if (!hasFiles) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        setState((prev) => ({ ...prev, isDragging: true }));
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent<HTMLElement>) => {
        const hasFiles = Array.from(e.dataTransfer?.types ?? []).includes('Files');
        if (!hasFiles) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (e.currentTarget.contains(e.relatedTarget as Node)) {
            return;
        }

        setState((prev) => ({ ...prev, isDragging: false }));
    }, []);

    const handleDragOver = useCallback((e: React.DragEvent<HTMLElement>) => {
        const hasFiles = Array.from(e.dataTransfer?.types ?? []).includes('Files');
        if (!hasFiles) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
    }, []);

    const handleDrop = useCallback(
        (e: React.DragEvent<HTMLElement>) => {
            const hasFiles = Array.from(e.dataTransfer?.types ?? []).includes('Files');
            if (!hasFiles) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            setState((prev) => ({ ...prev, isDragging: false }));

            if (inputRef.current?.disabled) return;

            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                if (!multiple) {
                    const file = e.dataTransfer.files[0];

                    addFiles([file]);
                } else {
                    addFiles(e.dataTransfer.files);
                }
            }
        },
        [addFiles, multiple],
    );

    const handleFileChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.target.files && e.target.files.length > 0) {
                addFiles(e.target.files);
            }
        },
        [addFiles],
    );

    const openFileDialog = useCallback(() => {
        if (inputRef.current) {
            inputRef.current.click();
        }
    }, []);

    const getInputProps = useCallback(
        (props: React.InputHTMLAttributes<HTMLInputElement> = {}) => {
            return {
                ...props,
                type: 'file' as const,
                onChange: handleFileChange,
                accept: props.accept ?? accept,
                multiple: props.multiple !== undefined ? props.multiple : multiple,
                ref: inputRef,
            };
        },
        [accept, multiple, handleFileChange],
    );

    return [
        state,
        {
            addFiles,
            removeFile,
            setFiles,
            clearFiles,
            clearErrors,
            handleDragEnter,
            handleDragLeave,
            handleDragOver,
            handleDrop,
            handleFileChange,
            openFileDialog,
            updateFileWithServerPath,
            getInputProps,
        },
    ];
}
