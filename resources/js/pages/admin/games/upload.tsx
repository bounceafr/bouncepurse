import { Form, Head, router } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import React, { useCallback, useRef, useState } from 'react';
import * as tus from 'tus-js-client';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import { store as storeResult } from '@/actions/App/Http/Controllers/Admin/GameResultController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type GameResult = {
    uuid: string;
    your_score: number;
    opponent_score: number;
    started_at: string;
    finished_at: string;
};

type Court = { id: number; name: string };
type Team = { id: number; name: string };

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    participant: string;
    status: string;
    scheduled_at: string | null;
    played_at: string | null;
    vimeo_status: string | null;
    court: Court | null;
    team: Team | null;
    game_result: GameResult | null;
};

function canSubmitResult(game: Game): boolean {
    if (game.game_result) {
        return false;
    }
    if (!game.scheduled_at) {
        return false;
    }
    return new Date(game.scheduled_at) < new Date();
}

export default function UploadGame({ game }: { game: Game }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Games', href: index().url },
        { title: game.title, href: '#' },
        {
            title: 'Upload Video',
            href: GameController.showUpload(game.uuid).url,
        },
    ];

    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [progress, setProgress] = useState(0);
    const [status, setStatus] = useState<
        'idle' | 'uploading' | 'paused' | 'done' | 'error'
    >('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const uploadRef = useRef<tus.Upload | null>(null);
    const inputRef = useRef<HTMLInputElement | null>(null);

    function selectFile(selected: File | null) {
        setFile(selected);
        setProgress(0);
        setStatus('idle');
        setErrorMessage(null);
        uploadRef.current = null;
    }

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const dropped = e.dataTransfer.files[0];
        if (dropped?.type.startsWith('video/')) {
            selectFile(dropped);
        }
    }, []);

    async function startUpload() {
        if (!file) return;

        setStatus('uploading');
        setErrorMessage(null);

        const xsrfToken = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );

        let uploadLink: string;
        try {
            const response = await fetch(
                GameController.initiateUpload(game.uuid).url,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': xsrfToken,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        file_size: file.size,
                        file_name: file.name,
                    }),
                },
            );

            if (!response.ok)
                throw new Error('Failed to create upload session.');

            const data = await response.json();
            uploadLink = data.upload_link;
        } catch (err) {
            setStatus('error');
            setErrorMessage(
                err instanceof Error ? err.message : 'Unknown error occurred.',
            );
            return;
        }

        const upload = new tus.Upload(file, {
            uploadUrl: uploadLink,
            chunkSize: 5 * 1024 * 1024,
            retryDelays: [0, 3000, 5000, 10000, 20000],
            onProgress(bytesUploaded, bytesTotal) {
                setProgress(Math.round((bytesUploaded / bytesTotal) * 100));
            },
            onSuccess() {
                setProgress(100);
                setStatus('done');
                router.patch(GameController.completeUpload(game.uuid).url);
            },
            onError(error) {
                setStatus('error');
                setErrorMessage(error.message ?? 'Upload failed.');
            },
        });

        uploadRef.current = upload;
        upload.start();
    }

    function pauseUpload() {
        uploadRef.current?.abort();
        setStatus('paused');
    }

    function resumeUpload() {
        setStatus('uploading');
        uploadRef.current?.start();
    }

    const isUploading = status === 'uploading';
    const isPaused = status === 'paused';
    const isActive = isUploading || isPaused || status === 'done';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Upload Video — ${game.title}`} />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Upload Video</h1>
                    <p className="text-sm text-muted-foreground">
                        Upload a video for{' '}
                        <span className="font-medium">{game.title}</span>.
                        {game.vimeo_status === 'complete' && (
                            <span className="ml-2 font-medium text-green-600">
                                A video has already been uploaded.
                            </span>
                        )}
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-4">
                        {/* Drop Zone */}
                        <input
                            ref={inputRef}
                            type="file"
                            accept="video/*"
                            className="hidden"
                            onChange={(e) =>
                                selectFile(e.target.files?.[0] ?? null)
                            }
                            disabled={isUploading}
                        />

                        {!file ? (
                            <button
                                type="button"
                                onClick={() => inputRef.current?.click()}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDrop}
                                disabled={isUploading}
                                className={`flex w-full cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-6 py-12 text-center transition-colors ${
                                    isDragging
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50 hover:bg-muted/50'
                                }`}
                            >
                                <Upload className="size-10 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">
                                        Drop a video file here, or{' '}
                                        <span className="text-primary">
                                            browse
                                        </span>
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        MP4, MOV, AVI, MKV and other video
                                        formats
                                    </p>
                                </div>
                            </button>
                        ) : (
                            <div className="flex items-center gap-3 rounded-lg border bg-muted/50 px-4 py-3">
                                <Upload className="size-5 shrink-0 text-muted-foreground" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {file.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {(file.size / 1024 / 1024).toFixed(2)}{' '}
                                        MB
                                    </p>
                                </div>
                                {!isUploading && status !== 'done' && (
                                    <button
                                        type="button"
                                        onClick={() => selectFile(null)}
                                        className="shrink-0 text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="size-4" />
                                    </button>
                                )}
                            </div>
                        )}

                        {/* Progress Bar */}
                        {isActive && (
                            <div className="space-y-1">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {status === 'done'
                                            ? 'Complete'
                                            : isPaused
                                              ? 'Paused'
                                              : 'Uploading...'}
                                    </span>
                                    <span className="font-medium">
                                        {progress}%
                                    </span>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        className={`h-full rounded-full transition-all duration-300 ${status === 'done' ? 'bg-green-500' : 'bg-primary'}`}
                                        style={{ width: `${progress}%` }}
                                    />
                                </div>
                            </div>
                        )}

                        {errorMessage && (
                            <p className="text-sm text-red-600">
                                {errorMessage}
                            </p>
                        )}

                        {status === 'done' && (
                            <p className="text-sm font-medium text-green-600">
                                Upload complete! Redirecting...
                            </p>
                        )}

                        {/* Actions */}
                        <div className="flex gap-2">
                            {!isUploading && !isPaused && status !== 'done' && (
                                <Button
                                    onClick={startUpload}
                                    disabled={!file}
                                    variant="default"
                                >
                                    Start Upload
                                </Button>
                            )}
                            {isUploading && (
                                <Button
                                    onClick={pauseUpload}
                                    variant="secondary"
                                >
                                    Pause
                                </Button>
                            )}
                            {isPaused && (
                                <Button
                                    onClick={resumeUpload}
                                    variant="default"
                                >
                                    Resume
                                </Button>
                            )}
                            <Button
                                variant="secondary"
                                onClick={() => router.visit(index().url)}
                            >
                                Back to Games
                            </Button>
                        </div>
                    </div>

                    {/* Result Section */}
                    {game.game_result ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Result</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-center gap-4 py-4">
                                    <div className="text-center">
                                        <div className="text-3xl font-bold">
                                            {game.game_result.your_score}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Your Score
                                        </div>
                                    </div>
                                    <div className="text-2xl text-muted-foreground">
                                        –
                                    </div>
                                    <div className="text-center">
                                        <div className="text-3xl font-bold">
                                            {game.game_result.opponent_score}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Opponent
                                        </div>
                                    </div>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Started
                                    </span>
                                    <span>
                                        {new Date(
                                            game.game_result.started_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Finished
                                    </span>
                                    <span>
                                        {new Date(
                                            game.game_result.finished_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    ) : canSubmitResult(game) ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Submit Result</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...storeResult.form(game.uuid)}
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="your_score">
                                                        Your Score
                                                    </Label>
                                                    <Input
                                                        id="your_score"
                                                        name="your_score"
                                                        type="number"
                                                        min={0}
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.your_score
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="opponent_score">
                                                        Opponent Score
                                                    </Label>
                                                    <Input
                                                        id="opponent_score"
                                                        name="opponent_score"
                                                        type="number"
                                                        min={0}
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.opponent_score
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="started_at">
                                                        Started At
                                                    </Label>
                                                    <Input
                                                        id="started_at"
                                                        name="started_at"
                                                        type="datetime-local"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.started_at
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="finished_at">
                                                        Finished At
                                                    </Label>
                                                    <Input
                                                        id="finished_at"
                                                        name="finished_at"
                                                        type="datetime-local"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.finished_at
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <Button
                                                disabled={processing}
                                                asChild
                                            >
                                                <button type="submit">
                                                    Submit Result
                                                </button>
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    ) : game.scheduled_at ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Result</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {new Date(game.scheduled_at) > new Date()
                                        ? 'You can submit results after the scheduled date.'
                                        : 'No result submitted yet.'}
                                </p>
                            </CardContent>
                        </Card>
                    ) : null}

                    {/* Game Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Game Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Format
                                </span>
                                <Badge variant="secondary">{game.format}</Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Type
                                </span>
                                <Badge variant="outline" className="capitalize">
                                    {game.participant}
                                </Badge>
                            </div>
                            {game.court && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Court
                                    </span>
                                    <span className="font-medium">
                                        {game.court.name}
                                    </span>
                                </div>
                            )}
                            {game.team && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Team
                                    </span>
                                    <span className="font-medium">
                                        {game.team.name}
                                    </span>
                                </div>
                            )}
                            {game.scheduled_at && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Scheduled
                                    </span>
                                    <span className="font-medium">
                                        {new Date(
                                            game.scheduled_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            )}
                            {game.played_at && (
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Played
                                    </span>
                                    <span className="font-medium">
                                        {new Date(
                                            game.played_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
