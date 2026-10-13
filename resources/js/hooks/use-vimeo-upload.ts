import { useCallback, useEffect, useRef, useState } from 'react';
import * as tus from 'tus-js-client';
import GameController from '@/actions/App/Http/Controllers/Admin/GameController';
import type { VideoStatus } from '@/lib/game';

export type UploadState =
    'idle' | 'uploading' | 'paused' | 'verifying' | 'done' | 'error';

type UploadError = {
    message: string;
    /** The file reached Vimeo; only the server-side verification needs repeating. */
    retryVerification: boolean;
};

class RequestError extends Error {}

function xsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

async function jsonRequest<T>(
    url: string,
    method: string,
    body: Record<string, unknown>,
    signal: AbortSignal,
    fallbackMessage: string,
): Promise<T> {
    const response = await fetch(url, {
        method: method.toUpperCase(),
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
        signal,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const errors = data?.errors as Record<string, string[]> | undefined;
        const message =
            (errors && Object.values(errors)[0]?.[0]) ??
            (typeof data?.message === 'string' ? data.message : null) ??
            fallbackMessage;

        throw new RequestError(message);
    }

    return data as T;
}

function isAbort(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

/**
 * Resumable tus upload to Vimeo for one game. The server hands out an upload attempt
 * (vimeo_uri) and only accepts completion for that attempt, after checking with Vimeo.
 */
export function useVimeoUpload(
    gameUuid: string,
    onComplete?: (videoStatus: VideoStatus) => void,
) {
    const [state, setState] = useState<UploadState>('idle');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<UploadError | null>(null);
    const [videoStatus, setVideoStatus] = useState<VideoStatus | null>(null);

    const uploadRef = useRef<tus.Upload | null>(null);
    const vimeoUriRef = useRef<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);
    const onCompleteRef = useRef(onComplete);

    useEffect(() => {
        onCompleteRef.current = onComplete;
    }, [onComplete]);

    useEffect(() => {
        const controller = new AbortController();
        abortRef.current = controller;

        return () => {
            controller.abort();
            // Stop sending chunks but keep the upload on Vimeo so it could be resumed.
            void uploadRef.current?.abort(false);
            uploadRef.current = null;
        };
    }, []);

    const fail = useCallback((message: string, retryVerification = false) => {
        setState('error');
        setError({ message, retryVerification });
    }, []);

    const verify = useCallback(async () => {
        const vimeoUri = vimeoUriRef.current;
        const signal = abortRef.current?.signal;

        if (!vimeoUri || !signal) return;

        setState('verifying');
        setError(null);

        try {
            const route = GameController.completeUpload(gameUuid);
            const data = await jsonRequest<{ vimeo_status: VideoStatus }>(
                route.url,
                route.method,
                { vimeo_uri: vimeoUri },
                signal,
                'We could not confirm the upload with the server.',
            );

            setVideoStatus(data.vimeo_status);
            setState('done');
            onCompleteRef.current?.(data.vimeo_status);
        } catch (err) {
            if (isAbort(err)) return;

            fail(
                err instanceof RequestError
                    ? err.message
                    : 'We could not reach the server to confirm the upload.',
                true,
            );
        }
    }, [fail, gameUuid]);

    const start = useCallback(
        async (file: File) => {
            const signal = abortRef.current?.signal;

            if (!signal) return;

            setState('uploading');
            setProgress(0);
            setError(null);
            setVideoStatus(null);

            let uploadLink: string;

            try {
                const route = GameController.initiateUpload(gameUuid);
                const data = await jsonRequest<{
                    upload_link: string;
                    vimeo_uri: string;
                }>(
                    route.url,
                    route.method,
                    { file_size: file.size, file_name: file.name },
                    signal,
                    'Failed to create upload session.',
                );

                uploadLink = data.upload_link;
                vimeoUriRef.current = data.vimeo_uri;
            } catch (err) {
                if (isAbort(err)) return;

                fail(
                    err instanceof Error
                        ? err.message
                        : 'Unknown error occurred.',
                );

                return;
            }

            const upload = new tus.Upload(file, {
                uploadUrl: uploadLink,
                chunkSize: 5 * 1024 * 1024,
                retryDelays: [0, 3000, 5000, 10000, 20000],
                onProgress(bytesUploaded, bytesTotal) {
                    if (signal.aborted) return;

                    setProgress(Math.round((bytesUploaded / bytesTotal) * 100));
                },
                onSuccess() {
                    if (signal.aborted) return;

                    setProgress(100);
                    void verify();
                },
                onError(uploadError) {
                    if (signal.aborted) return;

                    fail(uploadError.message ?? 'Upload failed.');
                },
            });

            uploadRef.current = upload;
            upload.start();
        },
        [fail, gameUuid, verify],
    );

    const pause = useCallback(() => {
        void uploadRef.current?.abort();
        setState('paused');
    }, []);

    const resume = useCallback(() => {
        setState('uploading');
        uploadRef.current?.start();
    }, []);

    const reset = useCallback(() => {
        void uploadRef.current?.abort();
        uploadRef.current = null;
        vimeoUriRef.current = null;
        setState('idle');
        setProgress(0);
        setError(null);
        setVideoStatus(null);
    }, []);

    return {
        state,
        progress,
        error,
        videoStatus,
        start,
        pause,
        resume,
        retryVerification: verify,
        reset,
    };
}
