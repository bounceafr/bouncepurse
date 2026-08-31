import { TrendingUp, TrendingDown } from "lucide-react"
import { StaggerChildren, StaggerItem } from "@/components/motion"
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
    <StaggerChildren className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-br *:data-[slot=card]:from-primary/8 *:data-[slot=card]:via-card *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:from-primary/12 dark:*:data-[slot=card]:bg-card">
      {cards.map((card) => (
        <StaggerItem key={card.label}>
          <Card className="sport-card-hover @container/card">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardDescription className="font-medium uppercase tracking-wide">
                  {card.label}
                </CardDescription>
                <span className="sport-icon-well [&>svg]:size-4">
                  {card.icon}
                </span>
              </div>
              <CardTitle className="text-3xl font-bold tabular-nums tracking-tight @[250px]/card:text-4xl">
                {card.value.toLocaleString()}
              </CardTitle>
              {card.trend !== undefined && (
                <CardAction>
                  <Badge variant="outline" className="border-primary/20 bg-primary/5 text-primary">
                    {card.trend >= 0 ? (
                      <TrendingUp />
                    ) : (
                      <TrendingDown />
                    )}
                    {Math.abs(card.trend)}%
                  </Badge>
                </CardAction>
              )}
            </CardHeader>
            <CardFooter className="flex-col items-start gap-1.5 text-sm">
              <div className="line-clamp-1 flex gap-2 font-medium">
                {card.trendLabel}
                {card.trend !== undefined && card.trend >= 0 ? (
                  <TrendingUp className="size-4 text-primary" />
                ) : card.trend !== undefined ? (
                  <TrendingDown className="size-4" />
                ) : null}
              </div>
              <div className="text-muted-foreground">
                {card.description}
              </div>
            </CardFooter>
          </Card>
        </StaggerItem>
      ))}
    </StaggerChildren>
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
