<style>
    /* A quiet editorial introduction leads into a single, generous writing surface. */
    .brand-contact-page { display: grid; grid-template-columns: minmax(0, .7fr) minmax(0, 1.3fr); align-items: start; gap: clamp(44px, 7vw, 96px); padding-top: 42px; border-top: 1px solid var(--line); }
    .brand-contact-intro { position: sticky; top: 125px; min-width: 0; }
    .brand-contact-eyebrow { display: flex; justify-content: space-between; gap: 20px; color: var(--accent); }
    .brand-contact-eyebrow span { color: var(--muted); }
    .brand-contact-intro .brand-display { margin-top: 28px; max-width: 440px; font-size: clamp(44px, 5.5vw, 70px); line-height: 1.06; }
    .brand-contact-lead { max-width: 290px; margin-top: 24px; color: var(--muted); font-size: 16px; line-height: 1.8; }
    .brand-contact-mark { display: grid; place-items: center; width: 70px; height: 70px; margin-top: 40px; border: 1px solid var(--line); color: var(--accent); }
    .brand-contact-mark .brand-arrow { width: 27px; height: 27px; stroke-width: 1.2; }
    .brand-contact-page .brand-form { gap: 28px; min-width: 0; padding: clamp(24px, 3.3vw, 42px); border: 1px solid var(--line); background: var(--surface); }
    .brand-contact-page .brand-form-head { padding-bottom: 26px; border-bottom: 1px solid var(--line); }
    .brand-contact-page .brand-form-head h2 { font-size: clamp(28px, 3vw, 38px); line-height: 1.12; }
    .brand-contact-page .brand-form-head p { margin-top: 10px; }
    .brand-contact-page fieldset.brand-contact-topic { padding-bottom: 28px; border-bottom: 1px solid var(--line); }
    .brand-contact-page .brand-form legend, .brand-contact-page .brand-field > .brand-label { color: var(--ink); }
    .brand-contact-topics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .brand-contact-topics .brand-chip { min-width: 0; }
    .brand-contact-topics .brand-chip span { width: 100%; min-height: 58px; display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--paper); }
    .brand-contact-topics .brand-chip span::before { content: ''; width: 14px; height: 14px; flex: none; border: 1px solid #858991; border-radius: 50%; }
    .brand-contact-topics .brand-chip input:checked + span { background: rgb(255 122 26 / .08); }
    .brand-contact-topics .brand-chip input:checked + span::before { border-color: var(--accent); background: var(--accent); box-shadow: inset 0 0 0 3px var(--surface); }
    .brand-contact-page .brand-field :is(input, select, textarea) { background: var(--paper); }
    .brand-contact-page .brand-field :is(input, select, textarea):focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    .brand-contact-page .brand-field textarea { min-height: 180px; }
    .brand-contact-page fieldset.brand-form-project { background: var(--paper); }
    .brand-contact-page .brand-form-foot { align-items: center; padding-top: 24px; }
    .brand-contact-page .brand-form-foot :is(p, .brand-button) { margin-top: 0; }
    .brand-contact-page .brand-form-foot p { max-width: 300px; font-size: 12px; }
    @media (max-width: 1000px) {
        .brand-contact-page { gap: 38px; }
    }
    @media (max-width: 760px) {
        .brand-contact-page { grid-template-columns: minmax(0, 1fr); gap: 30px; padding-top: 30px; }
        .brand-contact-intro { position: static; }
        .brand-contact-intro .brand-display { max-width: none; margin-top: 20px; font-size: clamp(42px, 10vw, 60px); }
        .brand-contact-lead { max-width: 380px; margin-top: 16px; font-size: 15px; }
        .brand-contact-mark { display: none; }
        .brand-contact-page .brand-form { gap: 24px; padding: 22px 18px; }
        .brand-contact-page .brand-form-head { padding-bottom: 22px; }
        .brand-contact-topics .brand-chip span { min-height: 56px; padding: 10px; font-size: 13px; }
        .brand-contact-page .brand-form-foot { display: grid; gap: 20px; }
        .brand-contact-page .brand-form-foot p { max-width: 100%; }
        .brand-contact-page .brand-form-foot .brand-button { width: 100%; }
    }
    @media (max-width: 360px) {
        .brand-contact-topics { grid-template-columns: 1fr; }
    }
</style>
