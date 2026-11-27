// Navigation is visible by default; JavaScript only adds mobile collapsing.
const menuToggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#site-nav');
const menuLabel = document.querySelector('[data-menu-label]');
const mobileViewport = window.matchMedia('(max-width: 1000px)');

if (menuToggle && navigation && menuLabel) {
  const setMenu = (open, restoreFocus = false) => {
    menuToggle.setAttribute('aria-expanded', String(open));
    menuLabel.textContent = open ? 'Kapat' : 'Menü';
    navigation.hidden = mobileViewport.matches && !open;
    if (restoreFocus) menuToggle.focus();
  };

  const syncViewport = () => {
    // Never leave focus inside a menu that is about to become hidden.
    const focusWasInNavigation = navigation.contains(document.activeElement);
    setMenu(false, mobileViewport.matches && focusWasInNavigation);
    menuToggle.hidden = !mobileViewport.matches;
  };

  document.documentElement.classList.add('menu-ready');
  syncViewport();

  menuToggle.addEventListener('click', () => {
    setMenu(menuToggle.getAttribute('aria-expanded') !== 'true');
  });

  navigation.addEventListener('click', (event) => {
    if (event.target.closest('a') && mobileViewport.matches) setMenu(false, true);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
      setMenu(false, true);
    }
  });

  mobileViewport.addEventListener('change', syncViewport);
}
