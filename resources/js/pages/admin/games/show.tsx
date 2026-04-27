import { Form, Head } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/Admin/GameController';
import { store as storeResult } from '@/actions/App/Http/Controllers/Admin/GameResultController';
import DisputeController from '@/actions/App/Http/Controllers/DisputeController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type GameResult = {
    uuid: string;
    your_score: number;
    opponent_score: number;
    started_at: string;
    finished_at: string;
};

type Dispute = {
    uuid: string;
    reason: string;
    status: string;
    created_at: string;
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
    court: Court | null;
    team: Team | null;
    game_result: GameResult | null;
};

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-500',
    pending: 'bg-yellow-500',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    flagged: 'bg-orange-500',
};

function canSubmitResult(game: Game): boolean {
    if (game.game_result) {
        return false;
    }
    if (game.played_at) {
        return true;
    }
    if (!game.scheduled_at) {
        return false;
    }
    return new Date(game.scheduled_at) < new Date();
}

const disputeStatusColors: Record<string, string> = {
    pending: 'bg-yellow-500',
    resolved: 'bg-green-500',
    dismissed: 'bg-red-500',
};

export default function GameShow({
    game,
    canDispute,
    dispute,
}: {
    game: Game;
    canDispute: boolean;
    dispute: Dispute | null;
}) {
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

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-semibold">{game.title}</h1>
                    <span
                        className={cn(
                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white capitalize',
                            statusColors[game.status] ?? 'bg-gray-400',
                        )}
                    >
                        {game.status}
                    </span>
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
                    ) : (
                        <Card>
                            <CardHeader>
                                <CardTitle>Result</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {game.scheduled_at &&
                                    new Date(game.scheduled_at) > new Date()
                                        ? 'You can submit results after the scheduled date.'
                                        : 'No result submitted yet.'}
                                </p>
                            </CardContent>
                        </Card>
                    )}
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
                                            <InputError message={errors.reason} />
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
                                        disputeStatusColors[dispute.status] ?? 'bg-gray-400',
                                    )}
                                >
                                    {dispute.status}
                                </span>
                            </div>
                            <p className="text-sm">{dispute.reason}</p>
                            <p className="text-xs text-muted-foreground">
                                Submitted{' '}
                                {new Date(dispute.created_at).toLocaleDateString()}
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
