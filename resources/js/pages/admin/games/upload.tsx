import { Head, router } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import React, { useCallback, useRef, useState } from 'react';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import { GameResultPanel } from '@/components/game-result';
import { ListPageShell } from '@/components/list-page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useVideoUpload } from '@/hooks/use-video-upload';
import AppLayout from '@/layouts/app-layout';
import { formatCourtName } from '@/lib/court';
import {
    type GameResult,
    type GameResultCapabilities,
    type GameSide,
    type GameSummary,
    type VideoStatus,
} from '@/lib/game';
import type { BreadcrumbItem } from '@/types';

type Game = GameSummary &
    GameSide & {
        participant: string;
        scheduled_at: string | null;
        vimeo_status: VideoStatus | null;
        game_result: GameResult | null;
    };

export default function UploadGame({
    game,
    ...capabilities
}: { game: Game } & GameResultCapabilities) {
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
    const inputRef = useRef<HTMLInputElement | null>(null);
    const upload = useVideoUpload(game.uuid, () => router.visit(index().url));
    const { state: status, progress, error } = upload;

    function selectFile(selected: File | null) {
        setFile(selected);
        upload.reset();
    }

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const dropped = e.dataTransfer.files[0];

        if (dropped?.type.startsWith('video/')) {
            selectFile(dropped);
        }
    };

    const isUploading = status === 'uploading' || status === 'verifying';
    const isPaused = status === 'paused';
    const isActive = isUploading || isPaused || status === 'done';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Upload Video — ${game.title}`} />

            <ListPageShell>
                <div>
                    <h1 className="text-2xl font-semibold">Upload Video</h1>
                    <p className="text-sm text-muted-foreground">
                        Upload a video for{' '}
                        <span className="font-medium">{game.title}</span>.
                        {game.vimeo_status === 'ready' && (
                            <span className="ml-2 font-medium text-green-600">
                                A video has already been uploaded.
                            </span>
                        )}
                        {game.vimeo_status === 'processing' && (
                            <span className="ml-2 font-medium text-muted-foreground">
                                A video has been uploaded and is still
                                processing on Vimeo.
                            </span>
                        )}
                        {game.vimeo_status === 'failed' && (
                            <span className="ml-2 font-medium text-red-600">
                                The last upload failed. Please upload the video
                                again.
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
                                            : status === 'verifying'
                                              ? 'Verifying upload...'
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

                        {upload.failedOver && !error && status !== 'done' && (
                            <p className="text-sm text-muted-foreground">
                                The upload to our server failed, so the video is
                                being sent to Vimeo instead.
                            </p>
                        )}

                        {error && (
                            <p role="alert" className="text-sm text-red-600">
                                {error.message}
                            </p>
                        )}

                        {status === 'done' && (
                            <p className="text-sm font-medium text-green-600">
                                {upload.videoStatus === 'processing'
                                    ? 'Upload received! Vimeo is still processing the video. Redirecting...'
                                    : 'Upload complete! Redirecting...'}
                            </p>
                        )}

                        {/* Actions */}
                        <div className="flex gap-2">
                            {error?.retryVerification ? (
                                <Button
                                    onClick={() =>
                                        void upload.retryVerification()
                                    }
                                    variant="default"
                                >
                                    Retry Verification
                                </Button>
                            ) : (
                                !isUploading &&
                                !isPaused &&
                                status !== 'done' && (
                                    <Button
                                        onClick={() =>
                                            file && void upload.start(file)
                                        }
                                        disabled={!file}
                                        variant="default"
                                    >
                                        Start Upload
                                    </Button>
                                )
                            )}
                            {status === 'uploading' && (
                                <Button
                                    onClick={upload.pause}
                                    variant="secondary"
                                >
                                    Pause
                                </Button>
                            )}
                            {isPaused && (
                                <Button
                                    onClick={upload.resume}
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
                    <GameResultPanel game={game} capabilities={capabilities} />

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
                                        {formatCourtName(game.court)}
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
            </ListPageShell>
        </AppLayout>
    );
}
