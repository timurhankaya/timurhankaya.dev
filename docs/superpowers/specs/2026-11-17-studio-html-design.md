# Independent studio HTML design

Approved in chat on 2026-11-17. Build a Turkish, single-page studio concept for SMEs and growing companies, separate from the personal Laravel site. T-MAX DevLab is a provisional name. The actual delivery model is founder-led with project-specific specialist partners.

## Direction and alternatives

The user selected a separate studio site over transforming the personal portfolio or adding a service page. They then explicitly chose independent HTML instead of Laravel, and navy with orange instead of a light or mixed theme. No domain, hosting, backend, permanent brand identity, translation or form submission is part of this iteration.

## Design

- Navy #0B1526, surface #142238, accent #FF8A3D, text #F4F7FC; normal text contrast >= 4.5:1.
- Local Geist with three responsive type roles: display 40–64px, heading 26–32px, body 16–18px. Use weight and spacing for finer hierarchy.
- Sequence: navigation, benefit-led hero, three services, three project examples, four-step process, studio/founder, contact and footer.
- Services: corporate websites, operations software, AI and integrations.
- Examples: Sera Ambalaj (freelance), Tamamlayıcı Sağlık (past company work), Monafy (technical cofounder). Explicitly distinguish founder experience from studio clients; no invented results, customer quotes or team size.
- Use code-native typographic artwork and a restrained architectural hero graphic. Contact uses the existing email address via mailto. In-page calls to action lead to contact.
- Mobile navigation must support keyboard, Escape, link activation and resizing; navigation remains usable without JavaScript. Respect reduced motion.

## Delivery

Self-contained folder at prototypes/studio, with HTML, CSS, small menu JavaScript and locally copied font assets/license. Opening index.html must work without a server or installation. Existing application files and public behavior remain unchanged.

## Acceptance

At finalization only: inspect 320/390/768/1440px layouts, no horizontal overflow, keyboard focus, anchors, mailto, mobile menu, reduced motion, JavaScript-disabled behavior, local file loading and font fallback. Do not run tests or npm run build during intermediate development. This buildless prototype does not need the Laravel/Vite build.
