/** A related record the API sends as just its id and name (user, team). */
export type NamedRef = { id: number; name: string };

export type GameCourt = {
    id: number;
    name: string;
    category_label?: string | null;
};

/** App\Enums\GameStatus */
export type GameStatus =
    | 'scheduled'
    | 'awaiting_result'
    | 'awaiting_confirmation'
    | 'awaiting_video'
    | 'contested'
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'flagged';

export type GameModeration = {
    id: number;
    status: string;
    reason: string;
    is_override: boolean;
    created_at: string;
    moderator: NamedRef | null;
};

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
    player: NamedRef | null;
    team: NamedRef | null;
    opponent_player: NamedRef | null;
    opponent_team: NamedRef | null;
};

/** Fields every game page receives; pages add the relations they load. */
export type GameSummary = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    status: GameStatus;
    played_at: string | null;
    court: GameCourt | null;
    player: NamedRef | null;
};

/** Capabilities computed on the server; the client never decides eligibility itself. */
export type GameResultCapabilities = {
    canSubmitResult: boolean;
    submitResultBlockedReason: string | null;
    canRespondToResult: boolean;
};

export const gameStatusColors: Record<GameStatus, string> = {
    scheduled: 'bg-blue-500',
    awaiting_result: 'bg-sky-500',
    awaiting_confirmation: 'bg-purple-500',
    awaiting_video: 'bg-indigo-500',
    contested: 'bg-orange-600',
    pending: 'bg-yellow-500',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    flagged: 'bg-orange-500',
};

export function gameStatusLabel(status: GameStatus): string {
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
