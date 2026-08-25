import { reorder } from '@atlaskit/pragmatic-drag-and-drop/reorder';
import { FileDownIcon, UploadIcon } from 'lucide-react';
import { LayoutGroup, m, type Transition } from 'motion/react';
import React, { useRef, useState } from 'react';

import * as DigitalFileController from '@/actions/App/Http/Controllers/Admin/DigitalFileController';
import { FormDirtySignal } from '@/components/admin/form-dirty-signal';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import type { AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { useFileUpload, type FileWithPreview } from '@/hooks/admin/use-file-upload';
import { isHttpError, uploadWithProgress } from '@/lib/http';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { Product, ProductVariant } from '@/types';
import type { MediaItem } from '@/types/media';

import { ProductDigitalFileItem, type DigitalFileRow } from './product-digital-file-item';

const animationTransition: Transition = {
    type: 'tween',
    ease: 'easeInOut',
    duration: 0.24,
};

const instantTransition: Transition = { duration: 0 };

const acceptedExtensions = [
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    'pdf',
    'epub',
    'mobi',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'csv',
    'txt',
    'rtf',
    'mp3',
    'wav',
    'flac',
    'aac',
    'ogg',
    'mp4',
    'mov',
    'avi',
    'mkv',
    'webm',
    'png',
    'jpg',
    'jpeg',
    'gif',
    'svg',
    'psd',
    'ai',
    'eps',
    'ttf',
    'otf',
    'woff',
    'woff2',
];

const acceptAttribute = acceptedExtensions.map((extension) => `.${extension}`).join(',');

interface ProductDigitalFilesProps {
    product?: Product;
    variants: ProductVariant[];
    maxUploadSize: number;
    errors: Record<string, string>;
}

type UploadResponse = MediaItem & { name: string };

function createRowsFromProduct(product?: Product): DigitalFileRow[] {
    return (product?.downloads ?? [])
        .slice()
        .sort((a, b) => a.sort_order - b.sort_order)
        .map((download) => ({
            key: download.id,
            id: download.id,
            variant_id: download.variant_id,
            name: download.name,
            media_id: download.media_id,
            original_filename: download.original_filename ?? '',
            file_size: download.file_size ?? 0,
            mime_type: download.mime_type,
            uploading: false,
            error: null,
        }));
}

export function ProductDigitalFiles({ product, variants, maxUploadSize, errors }: ProductDigitalFilesProps) {
    const { open: openProUpgrade } = useProUpgrade();
    const [rows, setRows] = useState<DigitalFileRow[]>(() => createRowsFromProduct(product));
    const dragDepthRef = useRef(0);
    const [isFileDragging, setIsFileDragging] = useState(false);
    const [animateReorder, setAnimateReorder] = useState(false);

    const variantOptions: AdaptiveSelectOption[] =
        variants.length > 0
            ? [
                  { value: '', label: __('All variants') },
                  ...variants.map((variant) => ({ value: variant.id, label: getTranslation(variant.title) })),
              ]
            : [];

    const handleFilesAdded = (addedFiles: FileWithPreview[]) => {
        const pendingRows: DigitalFileRow[] = addedFiles
            .filter((item) => item.file instanceof File)
            .map((item) => {
                const file = item.file as File;
                return {
                    key: item.id,
                    id: null,
                    variant_id: '',
                    name: file.name,
                    media_id: null,
                    original_filename: file.name,
                    file_size: file.size,
                    mime_type: file.type || null,
                    uploading: true,
                    error: null,
                };
            });

        if (pendingRows.length === 0) return;

        setAnimateReorder(false);
        setRows((prev) => [...prev, ...pendingRows]);

        addedFiles.forEach(async (item) => {
            if (!(item.file instanceof File)) return;

            const formData = new FormData();
            formData.append('file', item.file);

            try {
                const data = await uploadWithProgress<UploadResponse>(DigitalFileController.store(), formData);

                setRows((prev) =>
                    prev.map((row) =>
                        row.key === item.id
                            ? {
                                  ...row,
                                  media_id: data.id,
                                  original_filename: data.original_filename ?? row.original_filename,
                                  file_size: data.size ?? row.file_size,
                                  mime_type: data.mime_type ?? row.mime_type,
                                  uploading: false,
                                  error: null,
                              }
                            : row,
                    ),
                );
            } catch (error) {
                let message = __('Failed to upload file.');
                if (isHttpError(error) && error.data?.message) {
                    message = error.data.message as string;
                }

                setRows((prev) =>
                    prev.map((row) => (row.key === item.id ? { ...row, uploading: false, error: message } : row)),
                );
            }
        });
    };

    const [
        { errors: uploadErrors },
        { handleDragEnter, handleDragLeave, handleDragOver, handleDrop, openFileDialog, getInputProps, removeFile },
    ] = useFileUpload({
        accept: acceptAttribute,
        maxSize: maxUploadSize * 1024 * 1024,
        multiple: true,
        onFilesAdded: handleFilesAdded,
    });

    const handleNameChange = (key: string, name: string) => {
        setRows((prev) => prev.map((row) => (row.key === key ? { ...row, name } : row)));
    };

    const handleVariantChange = (key: string, variantId: string) => {
        setRows((prev) => prev.map((row) => (row.key === key ? { ...row, variant_id: variantId } : row)));
    };

    const handleRemove = (key: string) => {
        setAnimateReorder(false);
        removeFile(key);
        setRows((prev) => prev.filter((row) => row.key !== key));
    };

    const handleReorder = (startIndex: number, endIndex: number) => {
        setAnimateReorder(true);
        setRows((prev) => reorder({ list: prev, startIndex, finishIndex: endIndex }));
    };

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

    const uploadedRows = rows.filter((row) => row.media_id !== null);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Digital files')}</CardTitle>
                <CardDescription>{__('Files the customer can download after purchase')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <FormDirtySignal signal={rows.map((row) => `${row.key}:${row.variant_id}:${row.name}`).join('|')} />

                {uploadedRows.map((row, index) => (
                    <React.Fragment key={`hidden-${row.key}`}>
                        {row.id !== null && <input type="hidden" name={`downloads[${index}][id]`} value={row.id} />}
                        <input type="hidden" name={`downloads[${index}][variant_id]`} value={row.variant_id ?? ''} />
                        <input type="hidden" name={`downloads[${index}][name]`} value={row.name} />
                        <input
                            type="hidden"
                            name={`downloads[${index}][media_id]`}
                            value={String(row.media_id ?? '')}
                        />
                    </React.Fragment>
                ))}

                <div
                    onDragEnter={handleDragEnterWithFileCheck}
                    onDragLeave={handleDragLeaveWithFileCheck}
                    onDragOver={handleDragOver}
                    onDrop={handleDropWithFileCheck}
                    className={cn(
                        'relative flex flex-col items-center gap-3 overflow-hidden rounded-xl',
                        'border-2 border-dashed border-input/70 p-4 transition-all',
                        'has-[input[type=file]:focus]:border-ring has-[input[type=file]:focus]:ring-[3px] has-[input[type=file]:focus]:ring-ring/50',
                        isFileDragging && 'bg-muted/50',
                    )}
                >
                    <input {...getInputProps()} className="sr-only" aria-label={__('Upload digital file')} />

                    {rows.length > 0 ? (
                        <div className="flex w-full flex-col gap-3">
                            <div className="flex items-center justify-between">
                                <h3 className="truncate text-sm font-medium">
                                    {__('Uploaded')} ({rows.length})
                                </h3>
                                <Button type="button" variant="outline" onClick={openFileDialog}>
                                    <UploadIcon className="-ms-0.5 size-3.5" aria-hidden />
                                    {__('Add more')}
                                </Button>
                            </div>
                            <LayoutGroup>
                                <m.div layout className="flex flex-col gap-3" transition={instantTransition}>
                                    {rows.map((row, orderedIndex) => (
                                        <m.div
                                            key={row.key}
                                            layout
                                            initial={false}
                                            transition={animateReorder ? animationTransition : instantTransition}
                                        >
                                            <ProductDigitalFileItem
                                                file={row}
                                                index={orderedIndex}
                                                isLast={orderedIndex === rows.length - 1}
                                                variantOptions={variantOptions}
                                                onNameChange={handleNameChange}
                                                onVariantChange={handleVariantChange}
                                                onRemove={handleRemove}
                                                onReorder={handleReorder}
                                            />
                                        </m.div>
                                    ))}
                                </m.div>
                            </LayoutGroup>
                        </div>
                    ) : (
                        <div className="flex w-full flex-col items-center justify-center px-4 py-8 text-center">
                            <div
                                className="mb-2 flex size-11 shrink-0 items-center justify-center rounded-full border"
                                aria-hidden
                            >
                                <FileDownIcon className="size-4 text-muted-foreground" />
                            </div>
                            <p className="mb-2 text-sm font-medium">{__('Drop your files here')}</p>
                            <HelpBlock className="max-w-md text-balance">
                                {__('Archives, documents, ebooks, audio, video, images and fonts (max. :max_size MB)', {
                                    max_size: maxUploadSize,
                                })}
                            </HelpBlock>
                            <Button type="button" variant="outline" className="mt-4" onClick={openFileDialog}>
                                <UploadIcon className="-ms-0.5 size-3.5" aria-hidden />
                                {__('Add files')}
                            </Button>
                        </div>
                    )}
                </div>

                {uploadErrors.length > 0 && <FieldError className="text-xs">{uploadErrors[0]}</FieldError>}
                {errors.downloads && <FieldError className="text-xs">{errors.downloads}</FieldError>}

                <div
                    className="grid cursor-pointer gap-6 border-t pt-6 sm:grid-cols-2"
                    onClick={() => openProUpgrade(__('Download limit'))}
                    role="presentation"
                >
                    <Field>
                        <div className="flex items-center gap-2">
                            <FieldLabel htmlFor="download-limit">{__('Download limit')}</FieldLabel>
                            <ProBadge />
                        </div>
                        <Input
                            id="download-limit"
                            type="number"
                            inputMode="numeric"
                            min="1"
                            placeholder={__('Unlimited')}
                            disabled
                            className="opacity-60"
                        />
                    </Field>

                    <Field>
                        <div className="flex items-center gap-2">
                            <FieldLabel htmlFor="download-expiry-days">{__('Download expiry (days)')}</FieldLabel>
                            <ProBadge />
                        </div>
                        <Input
                            id="download-expiry-days"
                            type="number"
                            inputMode="numeric"
                            min="1"
                            placeholder={__('Never')}
                            disabled
                            className="opacity-60"
                        />
                    </Field>
                </div>
            </CardContent>
        </Card>
    );
}
