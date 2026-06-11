# Layout Typography Rules

This document defines the shared typography scale for the Papetarie Storefront theme.

## Design Tokens

Use these tokens instead of hardcoded component-specific values whenever possible:

```css
--pap-page-title-size: 32px;
--pap-section-title-size: 26px;
--pap-subsection-title-size: 22px;
--pap-product-title-size: 20px;
--pap-important-text-size: 18px;
--pap-body-text-size: 16px;
--pap-secondary-text-size: 14px;
--pap-label-text-size: 14px;
--pap-placeholder-text-size: 16px;
```

## Scale

- H1 / page title: `32px`, `700`
- H2 / section title: `26px`, `700`
- H3 / subsection title: `22px`, `700`
- Product card title: `20px`, `700`
- Important text: `18px`, `600`
- Body text: `16px`, `400`
- Secondary text: `14px`, `400`
- Form labels: `14px`, `600`
- Placeholders: `16px`

## Usage Guidelines

- Use the page title token for the main page heading only.
- Use the section title token for card and section headings.
- Use the subsection title token for nested groups and internal blocks.
- Use the product title token for all product cards, including homepage, archive, cart recommendations and cross-sells.
- Keep labels and placeholder text consistent across forms.
- Prefer shared tokens and shared classes instead of one-off font sizes.

## Notes

- Homepage hero display headings and other special marketing blocks may use dedicated display styles, but page content titles must follow this scale.
- Any new page or component should map into this scale before adding a new value.
