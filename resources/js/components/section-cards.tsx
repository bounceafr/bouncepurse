"use client"

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

export function SectionCards({ cards }: { cards: SectionCardData[] }) {
  return (
    <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
      {cards.map((card) => (
        <Card key={card.label} className="@container/card">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardDescription>{card.label}</CardDescription>
              <span className="text-muted-foreground [&>svg]:size-4">
                {card.icon}
              </span>
            </div>
            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
              {card.value.toLocaleString()}
            </CardTitle>
            {card.trend !== undefined && (
              <CardAction>
                <Badge variant="outline">
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
                <TrendingUp className="size-4" />
              ) : card.trend !== undefined ? (
                <TrendingDown className="size-4" />
              ) : null}
            </div>
            <div className="text-muted-foreground">
              {card.description}
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
