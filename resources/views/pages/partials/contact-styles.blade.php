<style>
    .brand-contact-header { display: grid; grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr); column-gap: 80px; align-items: end; padding-bottom: 52px; margin-bottom: 0; }
    .brand-contact-header > .brand-label { grid-column: 1 / -1; margin-bottom: 24px; color: var(--accent); }
    .brand-contact-header .brand-display { max-width: 760px; line-height: 1.05; }
    .brand-contact-header > p:not(.brand-label) { max-width: 280px; margin: 0 0 8px; font-size: 17px; }
    .brand-contact-page { grid-template-columns: minmax(0, .8fr) minmax(0, 1.4fr); gap: 100px; padding-top: 42px; border-top: 1px solid var(--line); }
    .brand-contact-intro { position: sticky; top: 125px; align-self: start; }
    .brand-contact-intro .brand-form-head h2 { font-size: clamp(28px, 3vw, 40px); }
    .brand-contact-intro .brand-form-head p { max-width: 240px; margin-top: 16px; }
    .brand-contact-mark { display: block; margin-top: 42px; color: var(--accent); }
    .brand-contact-mark .brand-arrow { width: 56px; height: 56px; stroke-width: 1; }
    .brand-contact-page .brand-form { gap: 28px; min-width: 0; }
    .brand-contact-page fieldset.brand-contact-topic { padding-bottom: 28px; border-bottom: 1px solid var(--line); }
    .brand-contact-topics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .brand-contact-topics .brand-chip span { width: 100%; min-height: 50px; display: flex; align-items: center; padding: 13px 16px; }
    .brand-contact-page .brand-field :is(input, select, textarea) { background: var(--surface); }
    .brand-contact-page .brand-field textarea { min-height: 190px; }
    .brand-contact-page .brand-form-foot { align-items: center; padding-top: 22px; }
    .brand-contact-page .brand-form-foot :is(p, .brand-button) { margin-top: 0; }
    .brand-contact-page .brand-form-foot p { max-width: 300px; font-size: 12px; }
    @media (max-width: 1000px) {
        .brand-contact-header { column-gap: 40px; }
        .brand-contact-page { gap: 50px; }
    }
    @media (max-width: 760px) {
        .brand-contact-header { grid-template-columns: 1fr; gap: 20px; padding-bottom: 32px; }
        .brand-contact-header > .brand-label { margin-bottom: 0; }
        .brand-contact-header .brand-display { font-size: clamp(44px, 10vw, 72px); }
        .brand-contact-header > p:not(.brand-label) { max-width: 320px; margin: 0; font-size: 15px; }
        .brand-contact-page { grid-template-columns: 1fr; gap: 28px; padding-top: 30px; }
        .brand-contact-intro { position: static; }
        .brand-contact-intro .brand-form-head h2 { font-size: 27px; }
        .brand-contact-intro .brand-form-head p { max-width: none; margin-top: 10px; }
        .brand-contact-mark { display: none; }
        .brand-contact-page .brand-form { gap: 24px; }
        .brand-contact-topics .brand-chip span { font-size: 13px; padding: 12px; }
        .brand-contact-page .brand-form-foot { display: grid; gap: 20px; }
        .brand-contact-page .brand-form-foot p { max-width: 100%; }
        .brand-contact-page .brand-form-foot .brand-button { width: 100%; }
    }
</style>
