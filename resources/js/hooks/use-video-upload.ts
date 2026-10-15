import { useCallback, useEffect, useRef, useState } from 'react';
import * as tus from 'tus-js-client';
import GameController from '@/actions/App/Http/Controllers/Admin/GameController';
import type { VideoStatus } from '@/lib/game';

export type UploadState =
    'idle' | 'uploading' | 'paused' | 'verifying' | 'done' | 'error';

type UploadError = {
    message: string;
    /** The whole file was sent; only the server-side verification needs repeating. */
    retryVerification: boolean;
};

/** Where an upload attempt sends the video: this server, or Vimeo as its failover. */
type UploadAttempt = {
    storage: 'server' | 'vimeo';
    upload_link: string;
    upload_id: string;
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
 * Resumable tus upload of one game's video. The server hands out an upload attempt
 * (upload_id), normally on itself, and only accepts completion for that attempt after
 * checking the stored file. When uploading to the server fails, the video goes to Vimeo.
 */
export function useVideoUpload(
    gameUuid: string,
    onComplete?: (videoStatus: VideoStatus) => void,
) {
    const [state, setState] = useState<UploadState>('idle');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<UploadError | null>(null);
    const [videoStatus, setVideoStatus] = useState<VideoStatus | null>(null);
    /** The upload to the server failed, so the video is going to Vimeo instead. */
    const [failedOver, setFailedOver] = useState(false);

    const uploadRef = useRef<tus.Upload | null>(null);
    const uploadIdRef = useRef<string | null>(null);
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
            // Stop sending chunks but keep the partial upload so it could be resumed.
            void uploadRef.current?.abort(false);
            uploadRef.current = null;
        };
    }, []);

    const fail = useCallback((message: string, retryVerification = false) => {
        setState('error');
        setError({ message, retryVerification });
    }, []);

    const verify = useCallback(async () => {
        const uploadId = uploadIdRef.current;
        const signal = abortRef.current?.signal;

        if (!uploadId || !signal) return;

        setState('verifying');
        setError(null);

        try {
            const route = GameController.completeUpload(gameUuid);
            const data = await jsonRequest<{ vimeo_status: VideoStatus }>(
                route.url,
                route.method,
                { upload_id: uploadId },
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
            setFailedOver(false);

            const begin = async (failover: boolean): Promise<void> => {
                let attempt: UploadAttempt;

                try {
                    const route = GameController.initiateUpload(gameUuid);
                    attempt = await jsonRequest<UploadAttempt>(
                        route.url,
                        route.method,
                        {
                            file_size: file.size,
                            file_name: file.name,
                            failover,
                        },
                        signal,
                        'Failed to create upload session.',
                    );
                } catch (err) {
                    if (isAbort(err)) return;

                    fail(
                        err instanceof Error
                            ? err.message
                            : 'Unknown error occurred.',
                    );

                    return;
                }

                uploadIdRef.current = attempt.upload_id;
                setProgress(0);

                const upload = new tus.Upload(file, {
                    uploadUrl: attempt.upload_link,
                    chunkSize: 5 * 1024 * 1024,
                    retryDelays: [0, 3000, 5000, 10000, 20000],
                    onBeforeRequest(req) {
                        // Only our own server checks the CSRF token; it must never be sent to Vimeo.
                        if (attempt.storage === 'server') {
                            req.setHeader('X-XSRF-TOKEN', xsrfToken());
                        }
                    },
                    onProgress(bytesUploaded, bytesTotal) {
                        if (signal.aborted) return;

                        setProgress(
                            Math.round((bytesUploaded / bytesTotal) * 100),
                        );
                    },
                    onSuccess() {
                        if (signal.aborted) return;

                        setProgress(100);
                        void verify();
                    },
                    onError(uploadError) {
                        if (signal.aborted) return;

                        if (attempt.storage === 'vimeo') {
                            fail(
                                failover
                                    ? 'The video could not be uploaded to our server or to Vimeo. Please check your connection and try again.'
                                    : (uploadError.message ?? 'Upload failed.'),
                            );

                            return;
                        }

                        // The server no longer knows this attempt: a newer upload replaced it.
                        if (
                            uploadError instanceof tus.DetailedError &&
                            uploadError.originalResponse?.getStatus() === 404
                        ) {
                            fail(
                                'This upload has been replaced by a newer one.',
                            );

                            return;
                        }

                        // Retries are exhausted; Vimeo is the failover.
                        setFailedOver(true);
                        void begin(true);
                    },
                });

                uploadRef.current = upload;
                upload.start();
            };

            await begin(false);
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
        uploadIdRef.current = null;
        setState('idle');
        setProgress(0);
        setError(null);
        setVideoStatus(null);
        setFailedOver(false);
    }, []);

    return {
        state,
        progress,
        error,
        videoStatus,
        failedOver,
        start,
        pause,
        resume,
        retryVerification: verify,
        reset,
    };
}
