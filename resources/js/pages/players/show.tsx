import { Head } from '@inertiajs/react';
import {
    CalendarDays,
    Gamepad2,
    MapPin,
    ShieldCheck,
    Star,
    Trophy,
} from 'lucide-react';
import React from 'react';
import { show as playerShow } from '@/actions/App/Http/Controllers/PlayerProfileController';
import Heading from '@/components/heading';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Player = {
    uuid: string;
    name: string;
    profile_image: string | null;
    country: string | null;
    city: string | null;
    position: string | null;
    bio: string | null;
    is_pathway_candidate: boolean;
    member_since: string | null;
};

type RankingEntry = {
    format: string;
    rank: number;
    score: number;
    wins: number;
    losses: number;
};

type GameStats = {
    total: number;
    approved: number;
};

type Props = {
    player: Player;
    rankings: Record<string, RankingEntry>;
    game_stats: GameStats;
};

export default function Show({ player, rankings, game_stats }: Props) {
    const getInitials = useInitials();
    const rankingEntries = Object.values(rankings);

    const totalWins = rankingEntries.reduce((sum, r) => sum + r.wins, 0);
    const totalLosses = rankingEntries.reduce((sum, r) => sum + r.losses, 0);
    const winRate =
        totalWins + totalLosses > 0
            ? Math.round((totalWins / (totalWins + totalLosses)) * 100)
            : null;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Player Profile',
            href: playerShow(player).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${player.name} - Profile`} />

            <div className="flex flex-col gap-6 p-6">
                <Heading
                    title="Player Profile"
                    description="View player information and statistics."
                />

                {/* Profile Header */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                            <Avatar className="size-24 text-2xl">
                                {player.profile_image && (
                                    <AvatarImage
                                        src={`/storage/${player.profile_image}`}
                                        alt={player.name}
                                    />
                                )}
                                <AvatarFallback className="text-2xl">
                                    {getInitials(player.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex flex-1 flex-col items-center gap-2 sm:items-start">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-2xl font-bold">
                                        {player.name}
                                    </h2>
                                    {player.is_pathway_candidate && (
                                        <Badge className="border-transparent bg-green-500 text-white">
                                            <Star className="mr-1 size-3" />
                                            Pathway Candidate
                                        </Badge>
                                    )}
                                </div>

                                {player.position && (
                                    <p className="text-sm font-medium text-muted-foreground">
                                        {player.position}
                                    </p>
                                )}

                                <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                    {(player.city || player.country) && (
                                        <span className="flex items-center gap-1">
                                            <MapPin className="size-3.5" />
                                            {[player.city, player.country]
                                                .filter(Boolean)
                                                .join(', ')}
                                        </span>
                                    )}

                                    {player.member_since && (
                                        <span className="flex items-center gap-1">
                                            <CalendarDays className="size-3.5" />
                                            Joined{' '}
                                            {new Date(
                                                player.member_since,
                                            ).toLocaleDateString('en-US', {
                                                month: 'long',
                                                year: 'numeric',
                                            })}
                                        </span>
                                    )}
                                </div>

                                {player.bio && (
                                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                                        {player.bio}
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Rankings */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Trophy className="size-4 text-muted-foreground" />
                                <CardTitle>Rankings</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {rankingEntries.length > 0 ? (
                                <div className="flex gap-6 overflow-x-auto">
                                    {rankingEntries.map((entry, index) => (
                                        <React.Fragment key={entry.format}>
                                            {index > 0 && (
                                                <Separator
                                                    orientation="vertical"
                                                    className="h-auto"
                                                />
                                            )}
                                            <div className="flex min-w-20 flex-col gap-1">
                                                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                    {entry.format}
                                                </span>
                                                <span className="text-3xl font-bold">
                                                    #{entry.rank}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    <span className="text-green-600">
                                                        {entry.wins}W
                                                    </span>
                                                    {' / '}
                                                    <span className="text-red-500">
                                                        {entry.losses}L
                                                    </span>
                                                </span>
                                            </div>
                                        </React.Fragment>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No rankings available yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Game Stats */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Gamepad2 className="size-4 text-muted-foreground" />
                                <CardTitle>Game Statistics</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Total Games
                                    </span>
                                    <span className="text-3xl font-bold">
                                        {game_stats.total}
                                    </span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Approved
                                    </span>
                                    <span className="text-3xl font-bold">
                                        {game_stats.approved}
                                    </span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Win Rate
                                    </span>
                                    <span className="text-3xl font-bold">
                                        {winRate !== null ? (
                                            <>
                                                {winRate}
                                                <span className="text-lg text-muted-foreground">
                                                    %
                                                </span>
                                            </>
                                        ) : (
                                            <span className="text-lg text-muted-foreground">
                                                N/A
                                            </span>
                                        )}
                                    </span>
                                </div>
                            </div>
                            {rankingEntries.length > 0 && (
                                <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <ShieldCheck className="size-3.5" />
                                    <span>
                                        {totalWins}W / {totalLosses}L across all
                                        formats
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
