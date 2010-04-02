$(document).ready(function(){
  var form = $('#theme-select');
  var select = form.children('#theme-selector');
  var iframe = $('#theme-preview');
  var hash = window.location.hash;
  
  form.bind('submit',function(e){
    var location;
    if (hash) {
      location = window.location.href.split('#')[0];
    } else {
      location = window.location.href;
    }
    window.location = location+"#/" + select.children(':selected').val();
    iframe.attr('src','theme.php?'+$(this).serialize());
    return false;
  });
  
  select.bind('change',function(e){
    form.trigger('submit');
  });
  
  if (hash) {
    select.children('option[value='+hash.split('/')[1]+']').attr('selected','selected');
    select.trigger('change')
  } else {
    form.trigger('submit');
  }
});