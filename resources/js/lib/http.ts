import { type UrlMethodPair } from '@inertiajs/core';

export type Url = string | UrlMethodPair;

function resolveUrl(url: Url): string {
    return typeof url === 'string' ? url : url.url;
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function headers(extra?: Record<string, string>): Record<string, string> {
    return {
        'X-XSRF-TOKEN': getXsrfToken(),
        Accept: 'application/json',
        ...extra,
    };
}

export class HttpError extends Error {
    status: number;
    data: Record<string, unknown> | null;

    constructor(status: number, data: Record<string, unknown> | null) {
        super(`HTTP error ${status}`);
        this.name = 'HttpError';
        this.status = status;
        this.data = data;
    }
}

export function isHttpError(error: unknown): error is HttpError {
    return error instanceof HttpError;
}

export function isAbortError(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

async function request<T = unknown>(url: Url, options: RequestInit = {}): Promise<T> {
    const isFormData = options.body instanceof FormData;

    const response = await fetch(resolveUrl(url), {
        ...options,
        headers: headers({
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...((options.headers as Record<string, string>) ?? {}),
        }),
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const data = await response.json().catch(() => null);
        throw new HttpError(response.status, data);
    }

    const text = await response.text();
    return text ? JSON.parse(text) : undefined!;
}

export function httpGet<T = unknown>(url: Url, options?: { signal?: AbortSignal }): Promise<T> {
    return request<T>(url, { method: 'GET', signal: options?.signal });
}

export function httpPost<T = unknown>(url: Url, data?: unknown, options?: { signal?: AbortSignal }): Promise<T> {
    const body = data instanceof FormData ? data : data !== undefined ? JSON.stringify(data) : undefined;
    return request<T>(url, { method: 'POST', body, signal: options?.signal });
}

export function httpPatch<T = unknown>(url: Url, data?: unknown, options?: { signal?: AbortSignal }): Promise<T> {
    const body = data instanceof FormData ? data : data !== undefined ? JSON.stringify(data) : undefined;
    return request<T>(url, { method: 'PATCH', body, signal: options?.signal });
}

export function uploadWithProgress<T = unknown>(
    url: Url,
    formData: FormData,
    options?: {
        onUploadProgress?: (event: { loaded: number; total: number; percentage: number }) => void;
        signal?: AbortSignal;
    },
): Promise<T> {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', resolveUrl(url));

        const h = headers();
        for (const [key, value] of Object.entries(h)) {
            xhr.setRequestHeader(key, value);
        }

        if (options?.onUploadProgress) {
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    options.onUploadProgress!({
                        loaded: event.loaded,
                        total: event.total,
                        percentage: Math.round((event.loaded * 100) / event.total),
                    });
                }
            });
        }

        if (options?.signal) {
            options.signal.addEventListener('abort', () => xhr.abort());
        }

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(xhr.responseText ? JSON.parse(xhr.responseText) : undefined!);
            } else {
                const data = (() => {
                    try {
                        return JSON.parse(xhr.responseText);
                    } catch {
                        return null;
                    }
                })();
                reject(new HttpError(xhr.status, data));
            }
        };

        xhr.onerror = () => reject(new Error('Network error'));
        xhr.onabort = () => reject(new DOMException('Request aborted', 'AbortError'));

        xhr.withCredentials = true;
        xhr.send(formData);
    });
}
