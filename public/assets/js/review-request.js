
'use strict';
document.querySelectorAll('[data-review-cancel]').forEach(button=>{
  button.hidden=false;
  button.addEventListener('click',()=>{
    const details=button.closest('details');
    details.querySelector('form').reset();
    details.open=false;
    details.querySelector('summary').focus();
  });
});
