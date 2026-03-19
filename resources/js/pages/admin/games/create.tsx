import { Form, Head } from '@inertiajs/react';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Court = { id: number; name: string };
type Team = { id: number; name: string };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index().url,
    },
    {
        title: 'Create Game',
        href: GameController.create().url,
    },
];

export default function CreateGame({
    courts,
    teams,
}: {
    courts: Court[];
    teams: Team[];
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Game" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Create Game</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a new game record or schedule a future game.
                    </p>
                </div>

                <Form
                    {...GameController.store.form()}
                    className="max-w-lg space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
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
                                        variant={
                                            mode === 'played'
                                                ? 'default'
                                                : 'outline'
                                        }
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
                                        variant={
                                            mode === 'scheduled'
                                                ? 'default'
                                                : 'outline'
                                        }
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
                                <Label htmlFor="participant">
                                    Participant Type
                                </Label>
                                <Select
                                    name="participant"
                                    onValueChange={setParticipant}
                                >
                                    <SelectTrigger id="participant">
                                        <SelectValue placeholder="Select type (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="player">
                                            Player (Individual)
                                        </SelectItem>
                                        <SelectItem value="team">
                                            Team
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.participant} />
                            </div>

                            {participant === 'team' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="team_id">Team</Label>
                                    <Select name="team_id" required>
                                        <SelectTrigger id="team_id">
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
                                <Label htmlFor="format">Format</Label>
                                <Select
                                    name="format"
                                    defaultValue={formats[0]}
                                    key={participant}
                                    required
                                >
                                    <SelectTrigger id="format">
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
                                <Label htmlFor="court_id">Court</Label>
                                <Select name="court_id">
                                    <SelectTrigger id="court_id">
                                        <SelectValue placeholder="Select a court (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {courts.map((court) => (
                                            <SelectItem
                                                key={court.id}
                                                value={String(court.id)}
                                            >
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
                                                        !date &&
                                                            'text-muted-foreground',
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 size-4" />
                                                    {date
                                                        ? date.toLocaleDateString(
                                                              'default',
                                                              {
                                                                  year: 'numeric',
                                                                  month: 'long',
                                                                  day: 'numeric',
                                                              },
                                                          )
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
                                        <InputError
                                            message={errors.played_at}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="result">Result</Label>
                                        <Select name="result">
                                            <SelectTrigger id="result">
                                                <SelectValue placeholder="Select result (optional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="win">
                                                    Win
                                                </SelectItem>
                                                <SelectItem value="lost">
                                                    Lost
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.result} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="points">Points</Label>
                                        <Input
                                            id="points"
                                            name="points"
                                            type="number"
                                            min={0}
                                            placeholder="Points scored (optional)"
                                        />
                                        <InputError message={errors.points} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="comments">
                                            Comments
                                        </Label>
                                        <Textarea
                                            id="comments"
                                            name="comments"
                                            placeholder="Comments (optional)"
                                        />
                                        <InputError message={errors.comments} />
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
                                                        !date &&
                                                            'text-muted-foreground',
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 size-4" />
                                                    {date
                                                        ? date.toLocaleDateString(
                                                              'default',
                                                              {
                                                                  year: 'numeric',
                                                                  month: 'long',
                                                                  day: 'numeric',
                                                              },
                                                          )
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
                                                    disabled={(d) =>
                                                        d < new Date()
                                                    }
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <Input
                                            type="time"
                                            value={time}
                                            onChange={(e) =>
                                                setTime(e.target.value)
                                            }
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

                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => window.history.back()}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing} asChild>
                                    <button type="submit">
                                        {mode === 'scheduled'
                                            ? 'Schedule Game'
                                            : 'Create Game'}
                                    </button>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
