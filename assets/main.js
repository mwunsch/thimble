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
  var appearanceSelector = $('#appearance-selector summary');

  function fontSelector (selected) {
    var select = $('<select></select>');
    select.append('<option value="">(default)</option>');
    select.append("<option value=\"Arial\" style=\"font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;\">Arial</option>");
    select.append("<option value=\"Arial Black\" style=\"font-family:'Arial Black', Arial, 'Helvetica Neue', Helvetica, sans-serif;\">Arial Black</option>");
    select.append("<option value=\"Baskerville\" style=\"font-family:Baskerville, 'Times New Roman', Times, serif;\">Baskerville</option>");
    select.append("<option value=\"Century Gothic\" style=\"font-family:'Century Gothic', 'Apple Gothic', sans-serif;\">Century Gothic</option>");
    select.append("<option value=\"Copperlate Light\" style=\"font-family:'Copperplate Light', 'Copperplate Gothic Light', serif;\">Copperlate Light</option>");
    select.append("<option value=\"Courier New\" style=\"font-family:'Courier New', Courier, monospace;\">Courier New</option>");
    select.append("<option value=\"Futura\" style=\"font-family:Futura, 'Century Gothic', AppleGothic, sans-serif;\">Futura</option>");
    select.append("<option value=\"Garamond\" style=\"font-family:Garamond, 'Hoefler Text', Times New Roman, Times, serif;\">Garamond</option>");
    select.append("<option value=\"Geneva\" style=\"font-family:Geneva, 'Lucida Sans', 'Lucida Grande', 'Lucida Sans Unicode', Verdana, sans-serif;\">Geneva</option>");
    select.append("<option value=\"Georgia\" style=\"font-family:Georgia, Palatino, 'Palatino Linotype', Times, 'Times New Roman', serif;\">Georgia</option>");
    select.append("<option value=\"Helvetica\" style=\"font-family:Helvetica, Arial, sans-serif;\">Helvetica</option>");
    select.append("<option value=\"Helvetica Neue\" style=\"font-family:'Helvetica Neue', Arial, Helvetica, sans-serif;\">Helvetica Neue</option>");
    select.append("<option value=\"Impact\" style=\"font-family:Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;\">Impact</option>");
    select.append("<option value=\"Lucida Sans\" style=\"font-family:'Lucida Sans', 'Lucida Grande', 'Lucida Sans Unicode', sans-serif;\">Lucida Sans</option>");
    select.append("<option value=\"Trebuchet MS\" style=\"font-family:'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;\">Trebuchet MS</option>");
    select.append("<option value=\"Verdana\" style=\"font-family:Verdana, Geneva, Tahoma, sans-serif;\">Verdana</option>");
    if (selected && select.children("option[value='"+selected+"']").length) {
      select.children("option[value='"+selected+"']").attr('selected','selected');
    }
    return select;
  }

  function setAppearanceOptions (options) {
    var selector = $('#appearance-selector .options');
    selector.children(':not(:submit)').remove();
    $.each(options, function (key, value) {
      $.each(value, function (name, content) {
        var p = $('<p><label>'+name+'</label></p>');
        var k = key.toLowerCase();
        switch (k) {
        case 'boolean':
          var is_content_true = false;
          if (content.length) {
            is_content_true = true;
            if (parseInt(content) >= 0) {
              is_content_true = (parseInt(content) > 0);
            }
          }
          p.prepend('<input type="checkbox" name="if:'+name+'" '+(is_content_true ? 'checked' : '')+' />');
          break;
        case 'font':
          p.append(fontSelector(content).attr('name',k+':'+name));
          break;
        default:
          p.append('<input type="text" value="'+content+'" name="'+k+':'+name+'" />');
          break; 
        }
        p.insertBefore(selector.children(':submit'));
      });
    });
  }

  appearanceSelector.bind('click', function (e) {
    var options = $(e.target).siblings('.options');
    var parent = options.parent();
    if (options.is(':hidden')) {
      parent.addClass('open');
      options.show();
    } else {
      parent.removeClass('open');
      options.hide();
    }
  });

  form.bind('submit',function(e){
    var location;
    var modal = appearanceSelector.siblings('.options');
    if (window.location.hash) {
      location = window.location.href.split('#')[0];
    } else {
      location = window.location.href;
    }
    if (modal.is(':visible')) {
      appearanceSelector.trigger('click');
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

  iframe.bind('load', function (e) {
    var frame = $(this.contentWindow? this.contentWindow.document : this.contentDocument.defaultView.document);
    var metaElements = frame.find('meta[name]');
    var appearanceOptions = { 
      'Color': {},
      'Font': {},
      'Boolean': {},
      'Text': {},
      'Image': {}
    };
    metaElements.each( function (i, val) {
      var $this = $(this);
      var option = $this.attr('name').split(':');
      var value = $this.attr('content');
      if (option.length > 1) {
        switch (option[0]) {
        case 'if':
          appearanceOptions['Boolean'][option[1]] = value;
          break;
        case 'color':
          appearanceOptions['Color'][option[1]] = value;
          break;
        case 'font':
          appearanceOptions['Font'][option[1]] = value;
          break;
        case 'image':
          appearanceOptions['Image'][option[1]] = value;
          break;
        case 'text':
          appearanceOptions['Text'][option[1]] = value;
          break;
        default:
          throw "This is not a recognized meta apperance option.";
        }
      }
    });
    setAppearanceOptions(appearanceOptions);
  });
  
});
