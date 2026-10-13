import { type GameStatus, gameStatusColors, gameStatusLabel } from '@/lib/game';
import { cn } from '@/lib/utils';

export function GameStatusBadge({ status }: { status: GameStatus }) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white capitalize',
                gameStatusColors[status] ?? 'bg-gray-400',
            )}
        >
            {gameStatusLabel(status)}
        </span>
    );
}
