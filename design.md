# Design System

## Typography

- **Font**: Inter Variable (`@fontsource-variable/inter`) — served locally, variable weight axis
- **Sans**: `'Inter Variable', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'`
- **Tokens**: `--font-sans` (base), `--font-heading` (aliased to sans), `--font-body` (aliased to sans)

## Colors

All colors use OKLCH for perceptual uniformity. A few CSS custom properties power the full palette via shared tokens (`var(--primary)`, etc.) — there are **no hardcoded Tailwind color classes** outside the theme block.

### Brand
| Token | Light | Dark |
|---|---|---|
| `--color-brand-red` | `oklch(0.55 0.22 25)` | same |

### Semantic (shadcn/ui palette)

| Token | Light | Dark |
|---|---|---|
| `--background` | `oklch(1 0 0)` | `oklch(0.147 0.004 49.25)` |
| `--foreground` | `oklch(0.147 0.004 49.25)` | `oklch(0.985 0.001 106.423)` |
| `--card` / `--popover` | `oklch(1 0 0)` | `oklch(0.216 0.006 56.043)` |
| `--primary` | `oklch(0.555 0.163 48.998)` | `oklch(0.473 0.137 46.201)` |
| `--primary-foreground` | `oklch(0.987 0.022 95.277)` | `oklch(0.987 0.022 95.277)` |
| `--secondary` | `oklch(0.967 0.001 286.375)` | `oklch(0.274 0.006 286.033)` |
| `--muted` | `oklch(0.97 0.001 106.424)` | `oklch(0.268 0.007 34.298)` |
| `--accent` | `oklch(0.97 0.001 106.424)` | `oklch(0.268 0.007 34.298)` |
| `--destructive` | `oklch(0.577 0.245 27.325)` | `oklch(0.704 0.191 22.216)` |
| `--border` / `--input` | `oklch(0.923 0.003 48.717)` | `oklch(1 0 0 / 10%)` / `15%` |
| `--ring` | `oklch(0.709 0.01 56.259)` | `oklch(0.553 0.013 58.071)` |

Chart & sidebar palettes are also defined (5 chart steps, full sidebar surface/interactive tokens).

## Border Radius

Base `--radius`: `0.625rem` (10px). Scale derived from it:

| Token | Value |
|---|---|
| `--radius-sm` | `calc(var(--radius) * 0.6)` ≈ 6px |
| `--radius-md` | `calc(var(--radius) * 0.8)` ≈ 8px |
| `--radius-lg` | `var(--radius)` = 10px |
| `--radius-xl` | `calc(var(--radius) * 1.4)` ≈ 14px |
| `--radius-2xl` | `calc(var(--radius) * 1.8)` ≈ 18px |
| `--radius-3xl` | `calc(var(--radius) * 2.2)` ≈ 22px |
| `--radius-4xl` | `calc(var(--radius) * 2.6)` ≈ 26px |

## Dark Mode

Toggled via `.dark` class on a parent element. The `@custom-variant dark` directive enables Tailwind's `dark:` variant.

## Base Styles

- `*` gets `border-border` (outline color via `outline-ring/50`)
- `body` gets `bg-background text-foreground`
- `html` gets `font-sans`

## Dependencies

- **tailwindcss** v4
- **tw-animate-css** (animation utilities)
- **shadcn/tailwind.css** (shadcn component base styles)
- **Inter Variable** font
