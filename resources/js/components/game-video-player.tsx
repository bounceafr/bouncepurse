import Player from '@vimeo/player';
import { useEffect, useRef, useState } from 'react';
import GameVideoController from '@/actions/App/Http/Controllers/Admin/GameVideoController';
import type { GameVideo } from '@/lib/game';

function extractVimeoId(uri: string): string | null {
    return uri.match(/\/(\d+)$/)?.[1] ?? null;
}

function NoVideo({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-muted-foreground/30 p-4 text-center">
            {children}
        </div>
    );
}

/** A video stored on this server, streamed by the browser's own player. */
function ServerVideo({ src }: { src: string }) {
    // We do not transcode, so the browser may not support the uploaded format.
    const [unplayable, setUnplayable] = useState(false);

    if (unplayable) {
        return (
            <NoVideo>
                <p className="text-sm text-muted-foreground">
                    This video can't be played in the browser.
                </p>
                <a
                    href={src}
                    download
                    className="text-sm text-blue-600 underline hover:text-blue-800"
                >
                    Download the video
                </a>
            </NoVideo>
        );
    }

    return (
        <video
            src={src}
            controls
            playsInline
            preload="metadata"
            className="aspect-video w-full rounded-lg bg-black"
            onError={() => setUnplayable(true)}
        />
    );
}

function VimeoVideo({ vimeoId }: { vimeoId: string }) {
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!containerRef.current) return;

        const player = new Player(containerRef.current, {
            id: Number(vimeoId),
            byline: false,
            portrait: false,
            title: false,
            dnt: true,
            responsive: true,
        });

        return () => {
            player.destroy().catch(() => {});
        };
    }, [vimeoId]);

    return (
        <div ref={containerRef} className="w-full overflow-hidden rounded-lg" />
    );
}

/** Plays a game's video from this server, or from Vimeo when the upload failed over there. */
export function GameVideoPlayer({
    game,
    subtitle,
}: {
    game: GameVideo;
    subtitle: string;
}) {
    const vimeoId = game.vimeo_uri ? extractVimeoId(game.vimeo_uri) : null;
    let player: React.ReactNode;

    if (game.video_path && game.vimeo_status === 'ready') {
        const src = GameVideoController.show(game.uuid).url;
        player = <ServerVideo key={src} src={src} />;
    } else if (vimeoId) {
        player = <VimeoVideo vimeoId={vimeoId} />;
    } else {
        return (
            <NoVideo>
                <p className="text-sm text-muted-foreground">
                    No video available
                </p>
            </NoVideo>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            {player}
            <p className="text-base text-muted-foreground">{subtitle}</p>
        </div>
    );
}
