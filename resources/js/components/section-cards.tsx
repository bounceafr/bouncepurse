import { TrendingUp, TrendingDown } from "lucide-react"
import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardAction,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"

interface SectionCardData {
  label: string
  value: number
  icon: React.ReactNode
  trend?: number
  trendLabel: string
  description: string
}

export const SectionCards = ({ cards }: { cards: SectionCardData[] }) => {
  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
      {cards.map((card) => (
        <Card key={card.label} className="@container/card">
          <CardHeader>
            <div className="flex items-center gap-3">
              <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary [&>svg]:size-5">
                {card.icon}
              </span>
              <div className="flex flex-1 items-center justify-between gap-2">
                <div>
                  <CardDescription className="text-xs font-medium uppercase tracking-wider">
                    {card.label}
                  </CardDescription>
                  <CardTitle className="mt-0.5 text-2xl font-bold tabular-nums @[250px]/card:text-3xl">
                    {card.value.toLocaleString()}
                  </CardTitle>
                </div>
                {card.trend !== undefined && (
                  <CardAction className="row-span-1 row-start-1 self-start">
                    <Badge
                      variant="outline"
                      className={`gap-1 text-xs font-medium ${
                        card.trend >= 0
                          ? 'text-chart-1'
                          : 'text-destructive'
                      }`}
                    >
                      {card.trend >= 0 ? (
                        <TrendingUp className="size-3" />
                      ) : (
                        <TrendingDown className="size-3" />
                      )}
                      {Math.abs(card.trend)}%
                    </Badge>
                  </CardAction>
                )}
              </div>
            </div>
          </CardHeader>
          <CardFooter className="pt-0">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <span className="font-medium text-foreground">
                {card.trendLabel}
              </span>
              <span aria-hidden="true">&middot;</span>
              <span>{card.description}</span>
            </div>
          </CardFooter>
        </Card>
      ))}
    </div>
  )
}

export function SectionCardsSkeleton() {
  return (
    <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
      {Array.from({ length: 4 }).map((_, i) => (
        <Card key={i} className="@container/card">
          <CardHeader>
            <div className="h-4 w-24 animate-pulse rounded bg-muted" />
            <div className="mt-2 h-8 w-20 animate-pulse rounded bg-muted" />
          </CardHeader>
          <CardFooter>
            <div className="h-4 w-32 animate-pulse rounded bg-muted" />
          </CardFooter>
        </Card>
      ))}
    </div>
  )
}
