var THIMBLE_POLL;

function ThimblePoll(theme, iframe) {
  $.ajax({
    type: 'HEAD',
    url: 'theme.php?theme='+theme,
    ifModified: true,
    success: function(data, status, xhr) {
      if (status !== "notmodified") {
        iframe.src = iframe.src;
      }
      THIMBLE_POLL = setTimeout(function(){
        ThimblePoll(theme, iframe);        
      }, 5000);
    }
  });
  return THIMBLE_POLL;
}

$(document).ready(function(){
  var form = $('#theme-select');
  var select = form.children('#theme-selector');
  var refresh = form.children('#auto-refresh');
  var iframe = $('#theme-preview');
  var hash = window.location.hash;
  
  form.bind('submit',function(e){
    var location;
    if (window.location.hash) {
      location = window.location.href.split('#')[0];
    } else {
      location = window.location.href;
    }
    window.location = location+"#/" + select.children(':selected').val();
    clearTimeout(THIMBLE_POLL);
    iframe.attr('src','theme.php?'+$(this).serialize());
    if (refresh.is(':checked')) {
      ThimblePoll(select.children(':selected').val(), iframe.get(0));
    }
    return false;
  });
  
  select.bind('change',function(e){
    form.trigger('submit');
  });
  
  refresh.bind('change',function(e){
    if (refresh.is(':checked')) {
      ThimblePoll(select.children(':selected').val(), iframe.get(0));
    } else {
      clearTimeout(THIMBLE_POLL);
    }
  });
  
  if (hash) {
    select.children('option[value='+hash.split('/')[1]+']').attr('selected','selected');
    select.trigger('change')
  } else {
    form.trigger('submit');
  }
  
});
