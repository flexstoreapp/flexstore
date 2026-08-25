import {
    FileArchiveIcon,
    FileAudioIcon,
    FileIcon,
    FileImageIcon,
    FileTextIcon,
    FileVideoIcon,
    type LucideProps,
} from 'lucide-react';

interface FileTypeIconProps extends LucideProps {
    mimeType: string | null;
    filename: string;
}

export function FileTypeIcon({ mimeType, filename, ...props }: FileTypeIconProps) {
    const extension = filename.split('.').pop()?.toLowerCase() ?? '';
    const mime = mimeType ?? '';

    if (mime.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(extension)) {
        return <FileImageIcon {...props} />;
    }
    if (mime.startsWith('audio/') || ['mp3', 'wav', 'flac', 'aac', 'ogg'].includes(extension)) {
        return <FileAudioIcon {...props} />;
    }
    if (mime.startsWith('video/') || ['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(extension)) {
        return <FileVideoIcon {...props} />;
    }
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
        return <FileArchiveIcon {...props} />;
    }
    if (['pdf', 'doc', 'docx', 'txt', 'rtf', 'epub', 'mobi', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'].includes(extension)) {
        return <FileTextIcon {...props} />;
    }

    return <FileIcon {...props} />;
}
