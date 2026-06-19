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

export interface DailyGameData {
    date: string;
    approved: number;
    pending: number;
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

export interface GameStatusSlice {
    status: string;
    label: string;
    count: number;
}

export interface HeatmapCell {
    dow: number;
    hour: number;
    count: number;
}

export interface FunnelStage {
    stage: string;
    count: number;
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
