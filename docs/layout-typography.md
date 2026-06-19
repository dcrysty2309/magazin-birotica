# Layout Typography Rules

This document defines the semantic typography hierarchy for the Papetarie Storefront theme.

## Global Color Tokens

Use these shared text colors everywhere:

```css
--text-primary: #17324D;
--text-secondary: #66758A;
--text-label: #7C8899;
```

## Semantic Hierarchy

- `H1` is the page title. Use one per page.
- `H2` is for main page sections.
- `H3` is for subsections inside an `H2`.
- `H4` is for small widget or card titles.
- Tables, labels, SKU, categories and meta text are not headings.
- Product names and monetary values are text styles, not headings.

## Text Roles

### H1

- `32px / 40px`
- `font-weight: 700`
- `color: var(--text-primary)`
- `letter-spacing: -0.02em`

### H2

- `24px / 32px`
- `font-weight: 700`
- `color: var(--text-primary)`
- `letter-spacing: -0.02em`

### H3

- `20px / 28px`
- `font-weight: 700`
- `color: var(--text-primary)`
- `letter-spacing: -0.02em`

### H4

- `18px / 24px`
- `font-weight: 600`
- `color: var(--text-primary)`
- `letter-spacing: -0.01em`

### Paragraph

- `14px / 22px`
- `font-weight: 400`
- `color: var(--text-primary)`

### Secondary text

- `14px / 22px`
- `font-weight: 400`
- `color: var(--text-secondary)`

### Small

- `13px / 20px`
- `font-weight: 400`
- `color: var(--text-secondary)`

### Label

- `12px / 16px`
- `font-weight: 600`
- `color: var(--text-label)`
- `text-transform: uppercase`
- `letter-spacing: 0.08em`

## Usage Guidelines

- Use headings based on semantic hierarchy, not size alone.
- Keep page titles as `H1`.
- Keep section titles as `H2`.
- Keep subsection titles as `H3`.
- Keep widget and card titles as `H4`.
- Keep table labels and headings as label text, not headings.
- Keep product names and price values as text styles.
- Prefer shared tokens and shared classes instead of one-off font sizes.

## Notes

- Any new page or component should map into this hierarchy before adding a new value.
- If a component needs a special text treatment, it should still map to one of the existing semantic roles above.
