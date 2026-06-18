import { CircleCheck, CircleX, Route } from 'lucide-react';
import { type ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type PathwayEligibility } from './types';

function CriterionRow({
    met,
    children,
}: {
    met: boolean;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center gap-2">
            {met ? (
                <CircleCheck className="size-4 text-green-500" />
            ) : (
                <CircleX className="size-4 text-red-500" />
            )}
            <span className="text-sm">{children}</span>
        </div>
    );
}

export function PathwayEligibilityCard({
    eligibility,
}: {
    eligibility: PathwayEligibility;
}) {
    const { criteria } = eligibility;

    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 [&>svg]:size-4">
                        <Route />
                    </span>
                    <CardTitle>Pathway Eligibility</CardTitle>
                    <Badge
                        variant={
                            eligibility.is_eligible ? 'default' : 'secondary'
                        }
                    >
                        {eligibility.is_eligible
                            ? 'Pathway Candidate'
                            : 'Not Yet Eligible'}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="pt-0">
                <div className="flex flex-col gap-2">
                    <CriterionRow met={criteria.approved_games.met}>
                        Approved Games: {criteria.approved_games.current} /{' '}
                        {criteria.approved_games.required} required
                    </CriterionRow>
                    <CriterionRow met={criteria.rank.met}>
                        Best Rank:{' '}
                        {criteria.rank.current !== null
                            ? `#${criteria.rank.current}`
                            : 'N/A'}{' '}
                        / top {criteria.rank.required} required
                    </CriterionRow>
                    <CriterionRow met={criteria.conduct_flags.met}>
                        Conduct Flags: {criteria.conduct_flags.current} /{' '}
                        {criteria.conduct_flags.limit} max allowed
                    </CriterionRow>
                </div>
            </CardContent>
        </Card>
    );
}
