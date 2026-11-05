<style>
    /* An open editorial layout: one invitation, a direct-contact rail, and a clear writing flow. */
    .brand-contact-page { display: block; padding-top: 34px; }
    .brand-contact-intro { padding-block: 32px 48px; border-top: 1px solid var(--line); }
    .brand-contact-eyebrow { display: flex; align-items: center; gap: 12px; color: var(--accent); }
    .brand-contact-eyebrow .brand-arrow { width: 16px; height: 16px; }
    .brand-contact-heading-row { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); align-items: end; gap: 48px; margin-top: 24px; }
    .brand-contact-intro .brand-display { margin: 0; font-size: clamp(56px, 7.4vw, 104px); line-height: 1.06; letter-spacing: -.065em; }
    .brand-contact-lead { max-width: 340px; padding-bottom: 5px; color: var(--muted); font-size: 17px; line-height: 1.8; }
    .brand-contact-body { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.6fr); gap: clamp(48px, 7vw, 104px); padding-top: 44px; border-top: 1px solid var(--line); }
    .brand-contact-page .brand-contact-aside { grid-column: 1; grid-row: 1; gap: 28px; min-width: 0; }
    .brand-contact-direct h2 { color: var(--muted); }
    .brand-contact-email { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 52px; margin-top: 8px; border-bottom: 1px solid var(--line); font-size: clamp(15px, 1.5vw, 19px); letter-spacing: -.025em; overflow-wrap: anywhere; }
    .brand-contact-email .brand-arrow { flex-shrink: 0; color: var(--accent); }
    .brand-contact-direct p { max-width: 270px; margin-top: 16px; color: var(--muted); font-size: 14px; line-height: 1.8; }
    .brand-contact-socials { display: flex; flex-wrap: wrap; gap: 8px 28px; }
    .brand-contact-socials a { display: inline-flex; align-items: center; gap: 10px; min-height: 44px; font-size: 14px; }
    .brand-contact-socials .brand-arrow { color: var(--muted); }
    .brand-contact-aside a:hover { color: var(--accent); }
    .brand-contact-signoff { font-size: 14px; line-height: 1.9; }
    .brand-contact-signoff > span:first-child { color: var(--accent); }
    .brand-contact-signoff > span:last-child { color: var(--muted); font-size: 12px; }
    .brand-contact-page .brand-form { grid-column: 2; grid-row: 1; min-width: 0; gap: 32px; }
    .brand-contact-page .brand-form-head h2 { font-size: 30px; line-height: 1.2; }
    .brand-contact-page .brand-form-head p { margin-top: 8px; }
    .brand-contact-page .brand-form-head .brand-form-required { font-size: 12px; margin-top: 12px; }
    .brand-contact-page .brand-form-section-title { width: 100%; margin-bottom: 20px; color: var(--ink); font-size: 16px; font-weight: 500; line-height: 1.6; }
    .brand-form-section-title > span { display: inline-block; margin-right: 12px; color: var(--accent); font: 12px/1.6 var(--mono); }
    .brand-contact-page .brand-form > fieldset + fieldset { padding-top: 28px; border-top: 1px solid var(--line); }
    .brand-contact-page .brand-form > fieldset + fieldset > legend { float: left; }
    .brand-contact-topics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .brand-contact-topics .brand-chip { min-width: 0; }
    .brand-contact-topics .brand-chip span { display: flex; align-items: center; gap: 12px; width: 100%; min-height: 56px; padding: 14px 16px; color: var(--ink); background: var(--surface); line-height: 1.5; }
    .brand-contact-topics .brand-chip span::before { content: ''; width: 16px; height: 16px; flex-shrink: 0; border: 1px solid var(--muted); border-radius: 50%; }
    .brand-contact-topics .brand-chip input:checked + span::before { border-color: var(--accent); background: var(--accent); box-shadow: inset 0 0 0 4px var(--surface); }
    .brand-contact-page .brand-contact-sender { display: grid; gap: 22px; }
    .brand-contact-page .brand-contact-sender > legend { margin-bottom: 0; }
    .brand-contact-page .brand-field > .brand-label { margin-bottom: 9px; color: var(--ink); font: 14px/1.5 "Geist", sans-serif; text-transform: none; letter-spacing: 0; }
    .brand-contact-page .brand-field small { margin-left: 4px; font-size: 12px; }
    .brand-contact-page .brand-field :is(input, select, textarea) { min-height: 52px; background: var(--surface); border-color: #44464d; font-size: 16px; }
    .brand-contact-page .brand-field :is(input, select, textarea):hover { border-color: var(--muted); }
    .brand-contact-page .brand-field :is(input, select, textarea):focus-visible { border-color: var(--accent); outline: 2px solid var(--accent); outline-offset: 3px; }
    .brand-contact-page .brand-field [aria-invalid="true"] { border-color: #ff8a7a; }
    .brand-contact-page .brand-field textarea { min-height: 170px; }
    .brand-contact-page fieldset.brand-form-project { padding: 24px; border: 1px solid var(--line); background: transparent; }
    .brand-contact-page .brand-form-project > legend { color: var(--ink); }
    .brand-contact-page .brand-form-project .brand-form-row { margin-top: 4px; }
    .brand-contact-page .brand-form-project .brand-chip span { min-height: 44px; display: flex; align-items: center; }
    .brand-contact-page .brand-form-project .brand-chip input:checked + span::after { content: '✓'; margin-left: 8px; color: var(--accent); }
    .brand-contact-page .brand-form-project .brand-label { color: var(--ink); }
    .brand-contact-page .brand-form-project small { color: var(--muted); }
    .brand-field-hint { margin-top: 10px; color: var(--muted); font-size: 12px; line-height: 1.7; }
    .brand-contact-page .brand-form-foot { align-items: center; padding-top: 24px; gap: 28px; }
    .brand-contact-page .brand-form-foot :is(p, .brand-button) { margin-top: 0; }
    .brand-contact-page .brand-form-foot p { max-width: 340px; font-size: 12px; }
    .brand-contact-page .brand-form-foot .brand-button { min-height: 52px; min-width: 152px; justify-content: space-between; }
    .brand-contact-page .brand-form-alert ul { display: grid; gap: 6px; margin-top: 10px; padding-left: 18px; list-style: disc; }
    .brand-contact-page [data-form-feedback] { scroll-margin-top: 120px; }
    .brand-contact-page [data-form-feedback]:focus { outline: 2px solid var(--accent); outline-offset: 4px; }
    .brand-contact-page .brand-form-project[hidden] { display: none; }
    @media (max-width: 900px) {
        .brand-contact-heading-row { grid-template-columns: 1fr; gap: 22px; }
        .brand-contact-lead { max-width: 460px; padding: 0; }
        .brand-contact-body { grid-template-columns: 1fr; gap: 36px; }
        .brand-contact-page .brand-form, .brand-contact-page .brand-contact-aside { grid-column: auto; grid-row: auto; }
        .brand-contact-page .brand-contact-aside { grid-template-columns: minmax(0, 1fr) auto; column-gap: 36px; padding-top: 28px; border-top: 1px solid var(--line); }
        .brand-contact-direct p { max-width: 360px; }
        .brand-contact-socials { align-content: start; flex-direction: column; gap: 0; }
        .brand-contact-signoff { display: none; }
    }
    @media (max-width: 600px) {
        .brand-contact-page { padding-top: 20px; }
        .brand-contact-intro { padding-block: 24px 30px; }
        .brand-contact-heading-row { gap: 18px; margin-top: 20px; }
        .brand-contact-intro .brand-display { font-size: clamp(42px, 12vw, 70px); }
        .brand-contact-lead { font-size: 16px; line-height: 1.75; }
        .brand-contact-body { padding-top: 26px; gap: 28px; }
        .brand-contact-page .brand-contact-aside { grid-template-columns: 1fr; gap: 12px; padding-top: 24px; }
        .brand-contact-email { justify-content: flex-start; gap: 16px; font-size: 17px; }
        .brand-contact-direct p { margin-top: 12px; }
        .brand-contact-socials { flex-direction: row; gap: 28px; }
        .brand-contact-page .brand-form { gap: 26px; }
        .brand-contact-page .brand-form-head h2 { font-size: 28px; }
        .brand-contact-topics { grid-template-columns: 1fr; gap: 8px; }
        .brand-contact-topics .brand-chip span { min-height: 52px; padding: 12px 14px; }
        .brand-contact-page .brand-form-row { grid-template-columns: 1fr; gap: 22px; }
        .brand-contact-page fieldset.brand-form-project { padding: 18px 16px; }
        .brand-contact-page .brand-form-project .brand-form-choices { flex-direction: column; }
        .brand-contact-page .brand-form-project .brand-chip span { width: 100%; }
        .brand-contact-page .brand-form-foot { display: flex; flex-direction: column-reverse; align-items: stretch; gap: 16px; }
        .brand-contact-page .brand-form-foot p { max-width: none; }
        .brand-contact-page .brand-form-foot .brand-button { width: 100%; }
    }
</style>
