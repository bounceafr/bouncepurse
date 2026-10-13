export type GameResult = {
    uuid: string;
    your_score: number;
    opponent_score: number;
    started_at: string;
    finished_at: string;
    confirmed_at: string | null;
    rejected_at: string | null;
    rejection_reason: string | null;
};

/** Lifecycle of a game's Vimeo video (App\Enums\VideoStatus). */
export type VideoStatus = 'uploading' | 'processing' | 'ready' | 'failed';

export type GameSide = {
    player: { id: number; name: string } | null;
    team: { id: number; name: string } | null;
    opponent_player: { id: number; name: string } | null;
    opponent_team: { id: number; name: string } | null;
};

/** Capabilities computed on the server; the client never decides eligibility itself. */
export type GameResultCapabilities = {
    canSubmitResult: boolean;
    submitResultBlockedReason: string | null;
    canRespondToResult: boolean;
};

export const gameStatusColors: Record<string, string> = {
    scheduled: 'bg-blue-500',
    awaiting_confirmation: 'bg-purple-500',
    contested: 'bg-orange-600',
    pending: 'bg-yellow-500',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    flagged: 'bg-orange-500',
};

export function gameStatusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

/** Name of the side that created the game and submits its result. */
export function submittingSideName(game: GameSide): string {
    return game.team?.name ?? game.player?.name ?? 'Home';
}

/** Name of the side that must confirm the submitted result. */
export function opponentSideName(game: GameSide): string {
    return game.opponent_team?.name ?? game.opponent_player?.name ?? 'Opponent';
}
