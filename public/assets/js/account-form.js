'use strict';
if(new URLSearchParams(window.location.search).has('new')){
 const nameField=document.getElementById('account-name');
 if(nameField)nameField.focus({preventScroll:true});
}
