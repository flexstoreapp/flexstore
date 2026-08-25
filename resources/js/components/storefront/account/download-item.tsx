import {
    DownloadIcon,
    FileArchiveIcon,
    FileAudioIcon,
    FileCodeIcon,
    FileIcon,
    FileImageIcon,
    FileSpreadsheetIcon,
    FileTextIcon,
    FileVideoIcon,
    type LucideIcon,
} from 'lucide-react';
import { createElement, type ReactNode } from 'react';

import * as DownloadController from '@/actions/App/Http/Controllers/Storefront/DownloadController';
import { buttonVariants } from '@/components/storefront/button';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { OrderItemDownload } from '@/types';

function formatFileSize(bytes: number): string {
    if (bytes <= 0) {
        return '0 KB';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / 1024 ** exponent;

    return `${value % 1 === 0 ? value : value.toFixed(1)} ${units[exponent]}`;
}

function fileFormat(filename: string): string {
    const extension = filename.split('.').pop();

    return extension && extension !== filename ? extension.toUpperCase() : __('File');
}

const ICON_BY_EXTENSION: Record<string, LucideIcon> = {
    png: FileImageIcon,
    jpg: FileImageIcon,
    jpeg: FileImageIcon,
    gif: FileImageIcon,
    webp: FileImageIcon,
    svg: FileImageIcon,
    bmp: FileImageIcon,
    tiff: FileImageIcon,
    heic: FileImageIcon,
    pdf: FileTextIcon,
    doc: FileTextIcon,
    docx: FileTextIcon,
    txt: FileTextIcon,
    rtf: FileTextIcon,
    md: FileTextIcon,
    xls: FileSpreadsheetIcon,
    xlsx: FileSpreadsheetIcon,
    csv: FileSpreadsheetIcon,
    zip: FileArchiveIcon,
    rar: FileArchiveIcon,
    '7z': FileArchiveIcon,
    tar: FileArchiveIcon,
    gz: FileArchiveIcon,
    mp3: FileAudioIcon,
    wav: FileAudioIcon,
    flac: FileAudioIcon,
    aac: FileAudioIcon,
    ogg: FileAudioIcon,
    mp4: FileVideoIcon,
    mov: FileVideoIcon,
    avi: FileVideoIcon,
    mkv: FileVideoIcon,
    webm: FileVideoIcon,
    js: FileCodeIcon,
    ts: FileCodeIcon,
    json: FileCodeIcon,
    html: FileCodeIcon,
    css: FileCodeIcon,
    xml: FileCodeIcon,
};

function fileIcon(filename: string): LucideIcon {
    const extension = filename.split('.').pop()?.toLowerCase();

    return (extension ? ICON_BY_EXTENSION[extension] : undefined) ?? FileIcon;
}

interface DownloadItemProps {
    download: OrderItemDownload;
    buttonVariant?: 'primary' | 'outline';
    meta?: ReactNode;
}

export function DownloadItem({ download, buttonVariant = 'primary', meta }: DownloadItemProps) {
    const downloadUrl = download.is_available ? DownloadController.show(download.token).url : null;

    const notices: string[] = [];

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="flex min-w-0 items-start gap-4 sm:flex-1">
                <span
                    aria-hidden="true"
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-line bg-surface-2 text-muted"
                >
                    {createElement(fileIcon(download.original_filename), { size: 22, strokeWidth: 1.6 })}
                </span>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        {downloadUrl ? (
                            <a
                                href={downloadUrl}
                                className="m-0 truncate text-sm font-semibold text-ink underline-offset-2 transition-colors hover:underline can-hover:hover:text-primary"
                            >
                                {download.name}
                            </a>
                        ) : (
                            <span className="m-0 truncate text-sm font-semibold text-ink">{download.name}</span>
                        )}
                        <span className="rounded-xs border border-line-strong px-1.5 py-0.5 text-2xs font-semibold text-muted uppercase">
                            {fileFormat(download.original_filename)}
                        </span>
                    </div>
                    <p className="mt-1 mb-0 text-sm text-muted">
                        {formatFileSize(download.file_size)}
                        {meta}
                    </p>
                    {notices.length > 0 && (
                        <p className="mt-1 mb-0 text-sm font-medium text-orange">{notices.join(' · ')}</p>
                    )}
                </div>
            </div>

            {downloadUrl ? (
                <a
                    href={downloadUrl}
                    className={cn(
                        buttonVariants({ variant: buttonVariant, size: 'sm' }),
                        'w-full justify-center sm:w-auto',
                    )}
                >
                    <DownloadIcon size={15} strokeWidth={2} aria-hidden="true" />
                    {__('Download')}
                </a>
            ) : (
                <span className="text-sm text-muted sm:shrink-0">{__('Unavailable')}</span>
            )}
        </div>
    );
}
