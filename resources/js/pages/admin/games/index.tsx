import { Form, Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    CalendarIcon,
    CirclePlusIcon,
    MoreHorizontal,
    Search,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type User = { id: number; name: string };
type Court = { id: number; name: string };
type Team = { id: number; name: string };

type GameResult = {
    your_score: number;
    opponent_score: number;
};

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    participant: string;
    court_id: number;
    player_id: number;
    scheduled_at: string | null;
    played_at: string | null;
    status: string;
    result: 'win' | 'lost' | null;
    court: Court;
    player: User | null;
    game_result: GameResult | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type PaginatedGames = {
    data: Game[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Games', href: index().url }];

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-500',
    pending: 'bg-yellow-500',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    flagged: 'bg-orange-500',
};

function statusBadge(status: string) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white capitalize ${statusColors[status] ?? 'bg-gray-400'}`}
        >
            {status}
        </span>
    );
}

function EditGameFormFields({
    game,
    courts,
    errors,
}: {
    game: Game;
    courts: Court[];
    errors: Record<string, string>;
}) {
    const [date, setDate] = useState<Date | undefined>(
        game.played_at ? new Date(game.played_at) : undefined,
    );
    const [calendarOpen, setCalendarOpen] = useState(false);

    const playedAtValue = date
        ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
        : '';

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="edit-title">Title</Label>
                <Input
                    id="edit-title"
                    name="title"
                    defaultValue={game.title}
                    placeholder="Game title"
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="edit-format">Format</Label>
                <Select
                    name="format"
                    defaultValue={game.format ?? '5v5'}
                    required
                >
                    <SelectTrigger id="edit-format">
                        <SelectValue placeholder="Select format" />
                    </SelectTrigger>
                    <SelectContent>
                        {['1v1', '2v2', '3v3', '4v4', '5v5'].map((f) => (
                            <SelectItem key={f} value={f}>
                                {f}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.format} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="edit-court_id">Court</Label>
                <Select
                    name="court_id"
                    defaultValue={String(game.court_id)}
                    required
                >
                    <SelectTrigger id="edit-court_id">
                        <SelectValue placeholder="Select a court" />
                    </SelectTrigger>
                    <SelectContent>
                        {courts.map((court) => (
                            <SelectItem key={court.id} value={String(court.id)}>
                                {court.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.court_id} />
            </div>

            <div className="grid gap-2">
                <Label>Played At</Label>
                <Popover open={calendarOpen} onOpenChange={setCalendarOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            variant="outline"
                            type="button"
                            className={cn(
                                'justify-start font-normal',
                                !date && 'text-muted-foreground',
                            )}
                        >
                            <CalendarIcon className="mr-2 size-4" />
                            {date
                                ? date.toLocaleDateString('default', {
                                      year: 'numeric',
                                      month: 'long',
                                      day: 'numeric',
                                  })
                                : 'Pick a date'}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent align="start">
                        <Calendar
                            mode="single"
                            selected={date}
                            onSelect={(d) => {
                                setDate(d);
                                setCalendarOpen(false);
                            }}
                        />
                    </PopoverContent>
                </Popover>
                <input type="hidden" name="played_at" value={playedAtValue} />
                <InputError message={errors.played_at} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="edit-result">Result</Label>
                <Select name="result" defaultValue={game.result ?? ''}>
                    <SelectTrigger id="edit-result">
                        <SelectValue placeholder="Select result (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="win">Win</SelectItem>
                        <SelectItem value="lost">Lost</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.result} />
            </div>

        </>
    );
}

function CreateGameFormFields({
    courts,
    teams,
    errors,
}: {
    courts: Court[];
    teams: Team[];
    errors: Record<string, string>;
}) {
    const [mode, setMode] = useState<'played' | 'scheduled'>('played');
    const [participant, setParticipant] = useState<string>('');
    const [date, setDate] = useState<Date | undefined>(undefined);
    const [time, setTime] = useState<string>('');
    const [calendarOpen, setCalendarOpen] = useState(false);

    const playerFormats = ['1v1'];
    const teamFormats = ['3v3', '5v5'];
    const allFormats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

    const formats =
        participant === 'team'
            ? teamFormats
            : participant === 'player'
              ? playerFormats
              : allFormats;

    const playedAtValue =
        mode === 'played' && date
            ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
            : '';

    const scheduledAtValue =
        mode === 'scheduled' && date && time
            ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${time}`
            : '';

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="create-title">Title</Label>
                <Input
                    id="create-title"
                    name="title"
                    placeholder="Game title"
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label>Game Type</Label>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant={mode === 'played' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => {
                            setMode('played');
                            setDate(undefined);
                            setTime('');
                        }}
                    >
                        Played
                    </Button>
                    <Button
                        type="button"
                        variant={mode === 'scheduled' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => {
                            setMode('scheduled');
                            setDate(undefined);
                            setTime('');
                        }}
                    >
                        Schedule
                    </Button>
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="create-participant">Participant Type</Label>
                <Select name="participant" onValueChange={setParticipant}>
                    <SelectTrigger id="create-participant">
                        <SelectValue placeholder="Select type (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="player">
                            Player (Individual)
                        </SelectItem>
                        <SelectItem value="team">Team</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.participant} />
            </div>

            {participant === 'team' && (
                <div className="grid gap-2">
                    <Label htmlFor="create-team_id">Team</Label>
                    <Select name="team_id" required>
                        <SelectTrigger id="create-team_id">
                            <SelectValue placeholder="Select your team" />
                        </SelectTrigger>
                        <SelectContent>
                            {teams.map((team) => (
                                <SelectItem
                                    key={team.id}
                                    value={String(team.id)}
                                >
                                    {team.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.team_id} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="create-format">Format</Label>
                <Select
                    name="format"
                    defaultValue={formats[0]}
                    key={participant}
                    required
                >
                    <SelectTrigger id="create-format">
                        <SelectValue placeholder="Select format" />
                    </SelectTrigger>
                    <SelectContent>
                        {formats.map((f) => (
                            <SelectItem key={f} value={f}>
                                {f}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.format} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="create-court_id">Court</Label>
                <Select name="court_id" required>
                    <SelectTrigger id="create-court_id">
                        <SelectValue placeholder="Select a court" />
                    </SelectTrigger>
                    <SelectContent>
                        {courts.map((court) => (
                            <SelectItem key={court.id} value={String(court.id)}>
                                {court.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.court_id} />
            </div>

            {mode === 'played' ? (
                <>
                    <div className="grid gap-2">
                        <Label>Played At</Label>
                        <Popover
                            open={calendarOpen}
                            onOpenChange={setCalendarOpen}
                        >
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    type="button"
                                    className={cn(
                                        'justify-start font-normal',
                                        !date && 'text-muted-foreground',
                                    )}
                                >
                                    <CalendarIcon className="mr-2 size-4" />
                                    {date
                                        ? date.toLocaleDateString('default', {
                                              year: 'numeric',
                                              month: 'long',
                                              day: 'numeric',
                                          })
                                        : 'Pick a date'}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="start">
                                <Calendar
                                    mode="single"
                                    selected={date}
                                    onSelect={(d) => {
                                        setDate(d);
                                        setCalendarOpen(false);
                                    }}
                                />
                            </PopoverContent>
                        </Popover>
                        <input
                            type="hidden"
                            name="played_at"
                            value={playedAtValue}
                        />
                        <InputError message={errors.played_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="create-result">Result</Label>
                        <Select name="result">
                            <SelectTrigger id="create-result">
                                <SelectValue placeholder="Select result (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="win">Win</SelectItem>
                                <SelectItem value="lost">Lost</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.result} />
                    </div>

                </>
            ) : (
                <div className="grid gap-2">
                    <Label>Scheduled Date & Time</Label>
                    <div className="flex gap-2">
                        <Popover
                            open={calendarOpen}
                            onOpenChange={setCalendarOpen}
                        >
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    type="button"
                                    className={cn(
                                        'flex-1 justify-start font-normal',
                                        !date && 'text-muted-foreground',
                                    )}
                                >
                                    <CalendarIcon className="mr-2 size-4" />
                                    {date
                                        ? date.toLocaleDateString('default', {
                                              year: 'numeric',
                                              month: 'long',
                                              day: 'numeric',
                                          })
                                        : 'Pick a date'}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="start">
                                <Calendar
                                    mode="single"
                                    selected={date}
                                    onSelect={(d) => {
                                        setDate(d);
                                        setCalendarOpen(false);
                                    }}
                                    disabled={(d) => d < new Date()}
                                />
                            </PopoverContent>
                        </Popover>
                        <Input
                            type="time"
                            value={time}
                            onChange={(e) => setTime(e.target.value)}
                            className="w-32"
                            required
                        />
                    </div>
                    <input
                        type="hidden"
                        name="scheduled_at"
                        value={scheduledAtValue}
                    />
                    <InputError message={errors.scheduled_at} />
                </div>
            )}
        </>
    );
}

const filterOptions = [
    { label: 'All', value: '' },
    { label: 'Upcoming', value: 'upcoming' },
    { label: 'Played', value: 'played' },
];

export default function GamesIndex({
    games,
    filters,
    courts,
    teams,
}: {
    games: PaginatedGames;
    filters: { search: string | null; filter: string | null };
    courts: Court[];
    teams: Team[];
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editGame, setEditGame] = useState<Game | null>(null);
    const [deleteGame, setDeleteGame] = useState<Game | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const isInitialRender = useRef(true);

    useEffect(() => {
        if (isInitialRender.current) {
            isInitialRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                {
                    search: search || undefined,
                    filter: filters.filter || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.filter]);

    const columns: ColumnDef<Game, unknown>[] = [
        selectionColumn<Game>(),
        {
            accessorKey: 'title',
            header: sortableHeader('Title'),
            cell: ({ row }) => (
                <Link
                    href={GameController.show(row.original.uuid).url}
                    className="font-medium hover:underline"
                >
                    {row.getValue('title')}
                </Link>
            ),
        },
        {
            id: 'player',
            accessorFn: (row) => row.player?.name ?? '—',
            header: sortableHeader('Player'),
        },
        {
            accessorKey: 'format',
            header: sortableHeader('Format'),
            cell: ({ row }) => (
                <Badge variant="secondary">{row.getValue('format')}</Badge>
            ),
        },
        {
            accessorKey: 'participant',
            header: 'Type',
            cell: ({ row }) => (
                <Badge variant="outline" className="capitalize">
                    {row.getValue('participant')}
                </Badge>
            ),
        },
        {
            id: 'date',
            header: sortableHeader('Date'),
            cell: ({ row }) => {
                const date =
                    row.original.scheduled_at ?? row.original.played_at;
                return date ? new Date(date).toLocaleDateString() : '—';
            },
        },
        {
            accessorKey: 'status',
            header: sortableHeader('Status'),
            cell: ({ row }) => statusBadge(row.getValue('status')),
        },
        {
            id: 'result',
            header: 'Result',
            enableSorting: false,
            cell: ({ row }) => {
                const gameResult = row.original.game_result;
                if (gameResult) {
                    return (
                        <span className="text-sm font-medium">
                            {gameResult.your_score} –{' '}
                            {gameResult.opponent_score}
                        </span>
                    );
                }
                const result = row.original.result;
                if (result) {
                    return (
                        <span
                            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${result === 'win' ? 'bg-green-500' : 'bg-red-500'}`}
                        >
                            {result === 'win' ? 'Win' : 'Lost'}
                        </span>
                    );
                }
                return <span className="text-xs text-muted-foreground">—</span>;
            },
        },
        {
            id: 'actions',
            enableHiding: false,
            enableSorting: false,
            cell: ({ row }) => {
                const game = row.original;
                return (
                    <div className="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon">
                                    <MoreHorizontal className="size-4" />
                                    <span className="sr-only">Actions</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={
                                            GameController.show(game.uuid).url
                                        }
                                    >
                                        View
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => setEditGame(game)}
                                >
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={
                                            GameController.showUpload(game.uuid)
                                                .url
                                        }
                                    >
                                        Upload Video
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    onClick={() => setDeleteGame(game)}
                                >
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
    ];

    const toolbar = (
        <>
            <div className="flex items-center gap-2">
                <div className="relative w-64">
                    <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Search games..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8"
                    />
                </div>
                <div className="flex gap-1">
                    {filterOptions.map((f) => (
                        <Button
                            key={f.value}
                            variant={
                                (filters.filter ?? '') === f.value
                                    ? 'default'
                                    : 'outline'
                            }
                            size="sm"
                            onClick={() =>
                                router.get(
                                    index().url,
                                    {
                                        search: search || undefined,
                                        filter: f.value || undefined,
                                    },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            {f.label}
                        </Button>
                    ))}
                </div>
            </div>
            <div className="flex gap-2">
                <Button onClick={() => setCreateOpen(true)}>
                    <CirclePlusIcon />
                    Add Game
                </Button>
            </div>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Games" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Games</h1>
                    <p className="text-sm text-muted-foreground">
                        Manage all games ({games.total} total)
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    data={games.data}
                    toolbar={toolbar}
                    pagination={
                        games.last_page > 1 ? (
                            <LaravelPagination links={games.links} />
                        ) : undefined
                    }
                />
            </div>

            {/* Create Game Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Create Game</DialogTitle>
                        <DialogDescription>
                            Add a new game record or schedule a future game.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...GameController.store.form()}
                        key={createOpen ? 'open' : 'closed'}
                        resetOnSuccess
                        onSuccess={() => setCreateOpen(false)}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <CreateGameFormFields
                                    courts={courts}
                                    teams={teams}
                                    errors={errors}
                                />

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button disabled={processing} asChild>
                                        <button type="submit">
                                            Create Game
                                        </button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {/* Edit Game Modal */}
            <Dialog
                open={editGame !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditGame(null);
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Game</DialogTitle>
                        <DialogDescription>
                            Update game details.
                        </DialogDescription>
                    </DialogHeader>

                    {editGame && (
                        <Form
                            {...GameController.update.form(editGame.uuid)}
                            key={editGame.id}
                            onSuccess={() => setEditGame(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <EditGameFormFields
                                        game={editGame}
                                        courts={courts}
                                        errors={errors}
                                    />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button disabled={processing} asChild>
                                            <button type="submit">
                                                Update Game
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Game Modal */}
            <Dialog
                open={deleteGame !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteGame(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Game</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium">
                                {deleteGame?.title}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    {deleteGame && (
                        <Form
                            {...GameController.destroy.form(deleteGame.uuid)}
                            onSuccess={() => setDeleteGame(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.game} />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button type="submit">
                                                Delete
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
