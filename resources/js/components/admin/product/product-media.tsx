import { reorder } from '@atlaskit/pragmatic-drag-and-drop/reorder';
import { ImageIcon, UploadIcon } from 'lucide-react';
import { LayoutGroup, m, type Transition } from 'motion/react';
import { useRef, useState } from 'react';

import * as MediaController from '@/actions/App/Http/Controllers/Admin/MediaController';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { ProductMediaItem } from '@/components/admin/product/product-media-item';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { useFileUpload, type FileWithPreview, type FileMetadata } from '@/hooks/admin/use-file-upload';
import { isHttpError, uploadWithProgress } from '@/lib/http';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio, mediaThumb } from '@/lib/media';
import { cn } from '@/lib/utils';
import type { Product, UploadProgress } from '@/types';
import type { MediaItem } from '@/types/media';

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.24,
};

interface ProductMediaProps {
    product?: Product;
    maxUploadSize: number;
}

function ImageHiddenInputs({ files }: { files: FileWithPreview[] }) {
    const mediaIds = files
        .filter((item) => !(item.file instanceof File))
        .map((item) => (item.file as FileMetadata).mediaId)
        .filter((id): id is number => typeof id === 'number');

    return (
        <>
            <FormDirtySignal signal={mediaIds.join(',')} />
            {mediaIds.map((id, index) => (
                <input key={`media-${index}`} type="hidden" name={`media.${index}`} value={String(id)} />
            ))}
        </>
    );
}

export function ProductMedia({ product, maxUploadSize }: ProductMediaProps) {
    const [uploadProgress, setUploadProgress] = useState<UploadProgress[]>([]);
    const dragDepthRef = useRef(0);
    const [isFileDragging, setIsFileDragging] = useState(false);

    const handleFilesAdded = (addedFiles: FileWithPreview[]) => {
        const newProgressItems = addedFiles.map((file) => ({
            fileId: file.id,
            progress: 0,
            completed: false,
        }));

        setUploadProgress((prev) => [...prev, ...newProgressItems]);

        addedFiles.forEach(async (file) => {
            if (file.file instanceof File) {
                const formData = new FormData();
                formData.append('file', file.file);
                formData.append('generate_thumbnail', '1');

                try {
                    const data = await uploadWithProgress<MediaItem>(MediaController.store(), formData, {
                        onUploadProgress: ({ percentage }) => {
                            setUploadProgress((prev) =>
                                prev.map((item) =>
                                    item.fileId === file.id ? { ...item, progress: percentage } : item,
                                ),
                            );
                        },
                    });

                    updateFileWithServerPath(file.id, mediaThumb(data) ?? '', data.id, data);
                    setUploadProgress((prev) =>
                        prev.map((item) => (item.fileId === file.id ? { ...item, completed: true } : item)),
                    );
                } catch (error) {
                    let errorMessage = __('Failed to upload media.');
                    if (isHttpError(error) && error.data?.message) {
                        errorMessage = error.data.message as string;
                    }

                    setUploadProgress((prev) =>
                        prev.map((item) =>
                            item.fileId === file.id ? { ...item, completed: false, error: errorMessage } : item,
                        ),
                    );
                }
            }
        });
    };

    const [
        { files, errors: uploadErrors },
        {
            setFiles,
            removeFile,
            handleDragEnter,
            handleDragLeave,
            handleDragOver,
            handleDrop,
            openFileDialog,
            getInputProps,
            updateFileWithServerPath,
        },
    ] = useFileUpload({
        accept: 'image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml',
        maxSize: maxUploadSize * 1024 * 1024,
        multiple: true,
        initialFiles: product?.media_gallery?.map((item) => ({
            name: item.original_filename ?? String(item.id),
            size: item.size ?? 0,
            type: item.mime_type ?? 'image/jpeg',
            url: mediaThumb(item) ?? '',
            id: String(item.id),
            mediaId: item.id,
            width: item.width,
            height: item.height,
        })),
        onFilesAdded: handleFilesAdded,
    });

    const ratio = mediaBoxRatio(files.map((item) => (item.file instanceof File ? null : item.file)));

    const handleDragEnterWithFileCheck = (e: React.DragEvent<HTMLDivElement>) => {
        if (e.dataTransfer.types.includes('Files')) {
            dragDepthRef.current += 1;
            if (dragDepthRef.current === 1) {
                setIsFileDragging(true);
            }
        }
        handleDragEnter(e);
    };

    const handleDragLeaveWithFileCheck = (e: React.DragEvent<HTMLDivElement>) => {
        if (e.dataTransfer.types.includes('Files')) {
            dragDepthRef.current -= 1;
            if (dragDepthRef.current === 0) {
                setIsFileDragging(false);
            }
        }
        handleDragLeave(e);
    };

    const handleDropWithFileCheck = (e: React.DragEvent<HTMLDivElement>) => {
        dragDepthRef.current = 0;
        setIsFileDragging(false);
        handleDrop(e);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Media')}</CardTitle>
                <CardDescription>{__('Add media to showcase your product')}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    onDragEnter={handleDragEnterWithFileCheck}
                    onDragLeave={handleDragLeaveWithFileCheck}
                    onDragOver={handleDragOver}
                    onDrop={handleDropWithFileCheck}
                    className={cn(
                        'relative flex min-h-52 flex-col items-center overflow-hidden rounded-xl',
                        'border-2 border-dashed border-input/70 p-4 transition-all',
                        'has-[input:focus]:border-ring has-[input:focus]:ring-[3px] has-[input:focus]:ring-ring/50',
                        isFileDragging && 'bg-muted/50',
                    )}
                >
                    <input {...getInputProps()} className="sr-only" aria-label={__('Upload image file')} />

                    {files && files.length > 0 ? (
                        <div className="flex w-full flex-col gap-3">
                            <div className="flex items-center justify-between">
                                <h3 className="truncate text-sm font-medium">
                                    {__('Uploaded')} ({files.length})
                                </h3>
                                <Button type="button" variant="outline" onClick={openFileDialog}>
                                    <UploadIcon className="-ms-0.5 size-3.5" aria-hidden />
                                    {__('Add more')}
                                </Button>
                            </div>
                            <LayoutGroup>
                                <m.div
                                    layout
                                    className="grid grid-cols-3 gap-4 md:grid-cols-4 lg:grid-cols-5"
                                    transition={animationTransition}
                                >
                                    {files.map((file, index) => (
                                        <m.div
                                            key={file.id}
                                            layout
                                            transition={animationTransition}
                                            initial={false}
                                            animate={{ opacity: 1, y: 0 }}
                                        >
                                            <ProductMediaItem
                                                file={file}
                                                index={index}
                                                isLast={index === files.length - 1}
                                                isFeatured={index === 0}
                                                ratio={ratio}
                                                removeFile={removeFile}
                                                uploadProgress={uploadProgress}
                                                onReorder={(startIndex, endIndex) => {
                                                    setFiles(
                                                        reorder({ list: files, startIndex, finishIndex: endIndex }),
                                                    );
                                                }}
                                            />
                                        </m.div>
                                    ))}
                                </m.div>
                            </LayoutGroup>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center px-4 py-3 text-center">
                            <div
                                className="mb-2 flex size-11 shrink-0 items-center justify-center rounded-full border"
                                aria-hidden
                            >
                                <ImageIcon className="size-4 text-muted-foreground" />
                            </div>
                            <p className="mb-2 text-sm font-medium">{__('Drop your media here')}</p>
                            <HelpBlock>
                                {__('JPG, PNG, WEBP, BMP or GIF (max. :max_size MB)', {
                                    max_size: maxUploadSize,
                                })}
                            </HelpBlock>
                            <Button type="button" variant="outline" className="mt-4" onClick={openFileDialog}>
                                <UploadIcon className="-ms-0.5 size-3.5" aria-hidden />
                                {__('Add media')}
                            </Button>
                        </div>
                    )}
                </div>

                {uploadErrors && uploadErrors.length > 0 && (
                    <FieldError className="mt-2 text-xs">{uploadErrors[0]}</FieldError>
                )}

                <ImageHiddenInputs files={files} />
            </CardContent>
        </Card>
    );
}
