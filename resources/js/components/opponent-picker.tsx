import { Check, ChevronsUpDown } from 'lucide-react';
import { useId, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { OpponentOption } from '@/lib/game';
import { cn } from '@/lib/utils';

/**
 * Searchable opponent picker. Several players can share a name, so each option
 * shows its detail line and the search matches it too. Submits `name` as a hidden input.
 */
export function OpponentPicker({
    id,
    name,
    options,
    defaultValue,
    placeholder,
}: {
    id: string;
    name: string;
    options: OpponentOption[];
    defaultValue?: number | null;
    placeholder: string;
}) {
    const listId = useId();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [selectedId, setSelectedId] = useState<number | null>(
        defaultValue ?? null,
    );
    const [activeIndex, setActiveIndex] = useState(0);

    const selected = options.find((option) => option.id === selectedId);

    const matches = useMemo(() => {
        const needle = query.trim().toLowerCase();

        return needle === ''
            ? options
            : options.filter((option) =>
                  `${option.name} ${option.detail ?? ''}`
                      .toLowerCase()
                      .includes(needle),
              );
    }, [options, query]);

    function choose(option: OpponentOption) {
        setSelectedId(option.id);
        setOpen(false);
        setQuery('');
    }

    function onKeyDown(event: React.KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) => Math.min(index + 1, matches.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const option = matches[activeIndex];

            if (option) {
                choose(option);
            }
        }
    }

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                setActiveIndex(0);
            }}
        >
            <input type="hidden" name={name} value={selectedId ?? ''} />
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listId}
                    className={cn(
                        'h-auto min-h-9 justify-between font-normal',
                        !selected && 'text-muted-foreground',
                    )}
                >
                    <span className="truncate text-left">
                        {selected ? (
                            <>
                                {selected.name}
                                {selected.detail && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {selected.detail}
                                    </span>
                                )}
                            </>
                        ) : (
                            placeholder
                        )}
                    </span>
                    <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                className="w-(--radix-popover-trigger-width) p-2"
            >
                <Input
                    autoFocus
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setActiveIndex(0);
                    }}
                    onKeyDown={onKeyDown}
                    placeholder="Search by name or city…"
                    aria-label="Search opponents"
                    aria-controls={listId}
                />
                <ul
                    id={listId}
                    role="listbox"
                    className="mt-2 max-h-60 overflow-y-auto"
                >
                    {matches.length === 0 && (
                        <li className="px-2 py-1.5 text-sm text-muted-foreground">
                            No matches.
                        </li>
                    )}
                    {matches.map((option, index) => (
                        <li
                            key={option.id}
                            role="option"
                            aria-selected={option.id === selectedId}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => choose(option)}
                            className={cn(
                                'flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-sm',
                                index === activeIndex && 'bg-accent',
                            )}
                        >
                            <span className="min-w-0">
                                <span className="block truncate">
                                    {option.name}
                                </span>
                                {option.detail && (
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {option.detail}
                                    </span>
                                )}
                            </span>
                            {option.id === selectedId && (
                                <Check className="size-4 shrink-0" />
                            )}
                        </li>
                    ))}
                </ul>
            </PopoverContent>
        </Popover>
    );
}
