'use strict';
const roleField = document.querySelector('#account-role');
const identifier = document.querySelector('#identifier');
const secret = document.querySelector('#secret');
const toggle = document.querySelector('#toggle-secret');
const message = document.querySelector('#login-message');
function selectRole(role) {
  if (!['patient', 'doctor', 'clinic_staff'].includes(role)) return;
  const patient = role === 'patient';
  const pinHelp = document.querySelector('#pin-help');
  pinHelp.hidden = !patient;
  if (!patient) pinHelp.open = false;
  roleField.value = role;
  identifier.type = patient ? 'text' : 'email';
  identifier.inputMode = patient ? 'numeric' : 'email';
  identifier.maxLength = patient ? 15 : 100;
  identifier.value = '';
  document.querySelector('#identifier-label').textContent = patient ? 'IC number' : 'Email address';
  document.querySelector('#secret-label').textContent = patient ? 'PIN' : 'Password';
  if (patient) secret.setAttribute('inputmode', 'numeric'); else secret.removeAttribute('inputmode');
  secret.value = '';
  secret.type = 'password';
  toggle.textContent = 'Show';
  toggle.setAttribute('aria-pressed', 'false');
  message.hidden = true;
  document.querySelectorAll('[data-role]').forEach(link => {
    if (link.dataset.role === role) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
}
document.querySelectorAll('[data-role]').forEach(link => link.addEventListener('click', event => {
  event.preventDefault();
  selectRole(link.dataset.role);
  if (location.protocol !== 'file:') {
    const url = new URL(location.href); url.searchParams.set('role', link.dataset.role);
    history.replaceState(null, '', url);
  }
}));
toggle.addEventListener('click', () => {
  const show = secret.type === 'password';
  secret.type = show ? 'text' : 'password';
  toggle.textContent = show ? 'Hide' : 'Show';
  toggle.setAttribute('aria-pressed', String(show));
});
if (location.protocol === 'file:') {
  document.querySelector('#login-form').addEventListener('submit', event => {
    event.preventDefault();
    message.textContent = 'Open MediFollow using the application launcher to sign in.';
    message.hidden = false;
  });
} else if (/\/index\.html$/.test(location.pathname)) {
  location.replace(new URL('login.php', location.href).href);
}

// Enter uses the same validated form submission as the Sign in button.
secret.addEventListener('keydown', event => {
  if (event.key !== 'Enter' || event.isComposing) return;
  event.preventDefault();
  if (!event.repeat) {
    secret.form.requestSubmit(secret.form.querySelector('.signin-button'));
  }
});

// Clear old errors on refresh. Preserve patient password-manager autofill.
window.addEventListener('pageshow', () => {
  const navigation = performance.getEntriesByType('navigation')[0];
  if (navigation?.type !== 'reload') return;
  message.textContent = '';
  message.hidden = true;
  if (roleField.value !== 'patient') {
    identifier.value = '';
    secret.value = '';
  }
  secret.type = 'password';
  toggle.textContent = 'Show';
  toggle.setAttribute('aria-pressed', 'false');
});

// One sliding highlight follows the selected account role.
const roleOptions = document.querySelector('.role-options');
const roleLinks = Array.from(roleOptions.querySelectorAll('[data-role]'));
function positionRoleHighlight() {
  const selected = roleOptions.querySelector('[aria-current="page"]');
  if (!selected) return;
  roleOptions.style.setProperty('--role-x', selected.offsetLeft + 'px');
  roleOptions.style.setProperty('--role-width', selected.offsetWidth + 'px');
}
roleOptions.classList.add('sliding-roles');
positionRoleHighlight();
// Enable motion after the first position is painted, avoiding an initial sweep.
requestAnimationFrame(() => requestAnimationFrame(() => roleOptions.classList.add('roles-ready')));
roleLinks.forEach(link => link.addEventListener('click', positionRoleHighlight));
window.addEventListener('resize', positionRoleHighlight);
if (document.fonts?.ready) document.fonts.ready.then(positionRoleHighlight);
