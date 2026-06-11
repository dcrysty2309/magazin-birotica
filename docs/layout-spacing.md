# Layout Spacing Rules

This document defines the global vertical rhythm used across the Papetarie Storefront theme.

## Global Rule

Header to page title spacing:

```css
64px
```

This is the default spacing standard for all new pages and page shells where the theme controls the first visible title or heading block.

## Spacing Scale

Use only 8px multiples:

```text
8
16
24
32
48
64
96
128
```

Do not introduce arbitrary spacing values unless there is a documented exception.

## Standard Rhythm

- Header -> page title: `64px`
- Page title -> first content block: `48px`
- End of one section -> start of next section: `96px`
- Section title -> subtitle: `16px`
- Subtitle -> content: `32px`

## Page Title Typography

Page-level titles and theme H2 headings use:

- font-size: `32px`
- font-weight: `700`
- line-height: `1.15`

This applies to page titles and the primary page heading treatment. Section headings and product titles use the shared typography scale documented in `docs/layout-typography.md`.

## Current Theme Applications

The theme currently applies the `64px` top spacing rule to:

- Cart page shell
- Account auth shell
- Checkout container

These areas should continue to use the shared spacing token:

```css
--pap-page-title-spacing: 64px;
```

## Guidance for New Pages

When adding a new page or template:

1. Use the shared spacing token for the first title block.
2. Keep section spacing on the 8px scale.
3. Avoid one-off spacing values that are not part of the system.
4. Prefer shared tokens over hardcoded values where possible.
