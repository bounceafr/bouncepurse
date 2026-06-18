export interface GameStats {
    total_games: number;
    total_courts: number;
    pending_games: number;
    approved_games: number;
    contested_games: number;
}

export interface RecentGame {
    id: number;
    uuid: string;
    title: string;
    status: string;
    played_at: string;
    court: { name: string } | null;
    player: { name: string } | null;
}

export interface MonthlyData {
    month: string;
    games: number;
    courts: number;
}

export interface SparklineDay {
    date: string;
    games: number;
    approved: number;
    pending: number;
    courts: number;
}

export interface VisitorStat {
    date: string;
    desktop: number;
    mobile: number;
}

export interface PlayerRankingEntry {
    format: string;
    rank: number;
    score: number;
    wins: number;
    losses: number;
}

export interface CriterionDetail {
    required?: number;
    current: number | null;
    met: boolean;
    limit?: number;
}

export interface PathwayEligibility {
    is_eligible: boolean;
    criteria: {
        approved_games: CriterionDetail;
        rank: CriterionDetail;
        conduct_flags: CriterionDetail;
    };
}

/** Tailwind class tokens for a single accent hue. Literal strings so the JIT detects them. */
export interface StatAccent {
    text: string;
    tint: string;
    fill: string;
}

export const statAccents = {
    amber: {
        text: 'text-amber-500',
        tint: 'bg-amber-500/10',
        fill: 'bg-amber-500',
    },
    green: {
        text: 'text-green-500',
        tint: 'bg-green-500/10',
        fill: 'bg-green-500',
    },
    red: { text: 'text-red-500', tint: 'bg-red-500/10', fill: 'bg-red-500' },
    blue: {
        text: 'text-blue-500',
        tint: 'bg-blue-500/10',
        fill: 'bg-blue-500',
    },
} satisfies Record<string, StatAccent>;
