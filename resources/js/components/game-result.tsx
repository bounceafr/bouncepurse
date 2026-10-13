import { Form } from '@inertiajs/react';
import {
    confirm as confirmResult,
    reject as rejectResult,
    store as storeResult,
} from '@/actions/App/Http/Controllers/Admin/GameResultController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { localInputToOffsetIso } from '@/lib/datetime';
import {
    type GameResult,
    type GameResultCapabilities,
    type GameSide,
    opponentSideName,
    submittingSideName,
} from '@/lib/game';

type ResultGame = GameSide & {
    uuid: string;
    status: string;
    game_result: GameResult | null;
};

function ConfirmationStatus({
    game,
    result,
}: {
    game: ResultGame;
    result: GameResult;
}) {
    if (game.status === 'awaiting_video') {
        return (
            <p className="text-sm text-indigo-600">
                {result.rejected_at ? 'Rejected' : 'Confirmed'} by{' '}
                {opponentSideName(game)}. Upload a video of the game to send it
                for review; moderators only review games with a video.
            </p>
        );
    }

    if (result.confirmed_at) {
        return (
            <p className="text-sm text-green-600">
                Confirmed by {opponentSideName(game)} on{' '}
                {new Date(result.confirmed_at).toLocaleString()}.
            </p>
        );
    }

    if (result.rejected_at) {
        return (
            <div className="space-y-1 text-sm">
                <p className="text-orange-600">
                    Rejected by {opponentSideName(game)}. A moderator will
                    review this game.
                </p>
                {result.rejection_reason && (
                    <p className="text-muted-foreground">
                        “{result.rejection_reason}”
                    </p>
                )}
            </div>
        );
    }

    return (
        <p className="text-sm text-muted-foreground">
            Waiting for {opponentSideName(game)} to confirm this result.
        </p>
    );
}

function GameResultSummary({
    game,
    result,
}: {
    game: ResultGame;
    result: GameResult;
}) {
    return (
        <div className="space-y-3">
            <div className="flex items-center justify-center gap-4 py-4">
                <div className="text-center">
                    <div className="text-3xl font-bold">
                        {result.your_score}
                    </div>
                    <div className="text-sm text-muted-foreground">
                        {submittingSideName(game)}
                    </div>
                </div>
                <div className="text-2xl text-muted-foreground">–</div>
                <div className="text-center">
                    <div className="text-3xl font-bold">
                        {result.opponent_score}
                    </div>
                    <div className="text-sm text-muted-foreground">
                        {opponentSideName(game)}
                    </div>
                </div>
            </div>
            <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Started</span>
                <span>{new Date(result.started_at).toLocaleString()}</span>
            </div>
            <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Finished</span>
                <span>{new Date(result.finished_at).toLocaleString()}</span>
            </div>
            <ConfirmationStatus game={game} result={result} />
        </div>
    );
}

function GameResultSubmitForm({ game }: { game: ResultGame }) {
    return (
        <Form
            {...storeResult.form(game.uuid)}
            transform={(data) => ({
                ...data,
                started_at: localInputToOffsetIso(
                    String(data.started_at ?? ''),
                ),
                finished_at: localInputToOffsetIso(
                    String(data.finished_at ?? ''),
                ),
            })}
            className="space-y-4"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="your_score">
                                {submittingSideName(game)} score
                            </Label>
                            <Input
                                id="your_score"
                                name="your_score"
                                type="number"
                                min={0}
                                required
                            />
                            <InputError message={errors.your_score} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="opponent_score">
                                {opponentSideName(game)} score
                            </Label>
                            <Input
                                id="opponent_score"
                                name="opponent_score"
                                type="number"
                                min={0}
                                required
                            />
                            <InputError message={errors.opponent_score} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="started_at">Started At</Label>
                            <Input
                                id="started_at"
                                name="started_at"
                                type="datetime-local"
                                required
                            />
                            <InputError message={errors.started_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="finished_at">Finished At</Label>
                            <Input
                                id="finished_at"
                                name="finished_at"
                                type="datetime-local"
                                required
                            />
                            <InputError message={errors.finished_at} />
                        </div>
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Times are in your local timezone (
                        {Intl.DateTimeFormat().resolvedOptions().timeZone}).
                    </p>

                    <p className="text-sm text-muted-foreground">
                        {opponentSideName(game)} must confirm this score before
                        it becomes final.
                    </p>
                    <InputError message={errors.result} />

                    <Button disabled={processing} asChild>
                        <button type="submit">Submit Result</button>
                    </Button>
                </>
            )}
        </Form>
    );
}

function GameResultResponse({ game }: { game: ResultGame }) {
    return (
        <div className="space-y-4 border-t pt-4">
            <p className="text-sm font-medium">
                Is this the final result? It only counts once you confirm it.
            </p>
            <Form {...confirmResult.form(game.uuid)}>
                {({ processing, errors }) => (
                    <>
                        <Button
                            disabled={processing}
                            className="w-full"
                            asChild
                        >
                            <button type="submit">Confirm Final Result</button>
                        </Button>
                        <InputError message={errors.result} />
                    </>
                )}
            </Form>
            <Form {...rejectResult.form(game.uuid)} className="space-y-2">
                {({ processing, errors }) => (
                    <>
                        <Label htmlFor="reject-reason">
                            Or reject it and explain what is wrong
                        </Label>
                        <Textarea
                            id="reject-reason"
                            name="reason"
                            rows={3}
                            placeholder="e.g. The final score was 18 – 21."
                            required
                        />
                        <InputError message={errors.reason} />
                        <Button
                            variant="destructive"
                            disabled={processing}
                            className="w-full"
                            asChild
                        >
                            <button type="submit">Reject Result</button>
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}

export function GameResultPanel({
    game,
    capabilities,
}: {
    game: ResultGame;
    capabilities: GameResultCapabilities;
}) {
    const result = game.game_result;

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {result || !capabilities.canSubmitResult
                        ? 'Result'
                        : 'Submit Result'}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {result ? (
                    <>
                        <GameResultSummary game={game} result={result} />
                        {capabilities.canRespondToResult && (
                            <GameResultResponse game={game} />
                        )}
                    </>
                ) : capabilities.canSubmitResult ? (
                    <GameResultSubmitForm game={game} />
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {capabilities.submitResultBlockedReason ??
                            'No result submitted yet.'}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
