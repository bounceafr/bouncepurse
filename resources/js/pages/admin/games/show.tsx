import { Form, Head } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/Admin/GameController';
import DisputeController from '@/actions/App/Http/Controllers/DisputeController';
import { GameResultPanel } from '@/components/game-result';
import { GameStatusBadge } from '@/components/game-status-badge';
import InputError from '@/components/input-error';
import { ListPageShell } from '@/components/list-page-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatCourtName } from '@/lib/court';
import {
    type GameResult,
    type GameResultCapabilities,
    type GameSide,
    type GameSummary,
} from '@/lib/game';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Game = GameSummary &
    GameSide & {
        participant: string;
        scheduled_at: string | null;
        game_result: GameResult | null;
    };

type Dispute = {
    uuid: string;
    reason: string;
    status: string;
    created_at: string;
};

const disputeStatusColors: Record<string, string> = {
    pending: 'bg-yellow-500',
    resolved: 'bg-green-500',
    dismissed: 'bg-red-500',
};

export default function GameShow({
    game,
    canDispute,
    dispute,
    ...capabilities
}: {
    game: Game;
    canDispute: boolean;
    dispute: Dispute | null;
} & GameResultCapabilities) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Games', href: GameController.index().url },
        {
            title: game.title,
            href: GameController.show(game.uuid).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={game.title} />

            <ListPageShell>
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-semibold">{game.title}</h1>
                    <GameStatusBadge status={game.status} />
                </div>

                <div className="grid gap-6 md:grid-cols-2">
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
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Opponent
                                </span>
                                <span className="font-medium">
                                    {game.opponent_team?.name ??
                                        game.opponent_player?.name ??
                                        'Not set'}
                                </span>
                            </div>
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

                    <GameResultPanel game={game} capabilities={capabilities} />
                </div>

                {canDispute && (
                    <Card className="border-orange-400">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-orange-600">
                                <AlertCircle className="size-5" />
                                Submit Dispute
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...DisputeController.store.form(game.uuid)}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="reason">
                                                Reason
                                            </Label>
                                            <textarea
                                                id="reason"
                                                name="reason"
                                                placeholder="Explain why you are disputing this game decision..."
                                                rows={4}
                                                required
                                                className="flex w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:aria-invalid:ring-destructive/40"
                                            />
                                            <InputError
                                                message={errors.reason}
                                            />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            className="w-full"
                                            asChild
                                        >
                                            <button type="submit">
                                                Submit Dispute
                                            </button>
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {dispute && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Dispute</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Status
                                </span>
                                <span
                                    className={cn(
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white capitalize',
                                        disputeStatusColors[dispute.status] ??
                                            'bg-gray-400',
                                    )}
                                >
                                    {dispute.status}
                                </span>
                            </div>
                            <p className="text-sm">{dispute.reason}</p>
                            <p className="text-xs text-muted-foreground">
                                Submitted{' '}
                                {new Date(
                                    dispute.created_at,
                                ).toLocaleDateString()}
                            </p>
                        </CardContent>
                    </Card>
                )}
            </ListPageShell>
        </AppLayout>
    );
}
