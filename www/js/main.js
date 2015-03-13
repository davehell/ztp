$( document ).ready(function() {
  //barevné zvýraznění změny, která je zadána v url
  var hash = window.location.hash; //např.: "#z115"
  if (hash.substring(0, 2) == '#z') {
    var el = $('.zmena[data-id="' + hash.substring(2) + '"]');
    el.addClass( 'bg-danger' );
    setTimeout(function() {el.removeClass('bg-danger')}, 5000);
  }
});


/**
 * odeslání formuláře pomocí ctrl + enter
 */
$.fn.enterKey = function (fnc, mod) {
  return this.each(function () {
    $(this).keypress(function (ev) {
      var keycode = (ev.keyCode ? ev.keyCode : ev.which);
      if ((keycode == '13' || keycode == '10') && (!mod || ev[mod + 'Key'])) {
          fnc.call(this, ev);
      }
    })
  })
}
$('textarea').enterKey(function() {$(this).closest('form').submit(); }, 'ctrl');



$(function () {
  $.nette.ext({
      load: function() {

      }
  });
  $.nette.init();

  /**
   * Potvrzovací dialog při kliknutí na odkaz.
   */
  $('.btn-danger').click(function(event) {
    event.preventDefault();
    var url = $(this).attr('href');
    var confirm_box = confirm('Opravdu?');
    if(confirm_box) {
      window.location = url;
    }
  });

  /**
   * Po výběru verze v select boxu v menu se otevře stránka s danou verzí.
   */
  $('#menuForm #vyberVerze, #menuForm #vyberUzivatele, #menuForm #vyberProtokolu').change(function(event) {
    var id = $( this ).val();
    if(!id) return;
    $('#menuForm').submit();
  });

  /**
   * Posunutí změny nahoru
   */
  $('.posunNahoru').click(function() {
    var zmena = $(this).closest('.zmena');
    var predchudce = zmena.prevAll('.zmena:first')
    if(predchudce.length) {
      predchudce.before(zmena);
      ulozitPoradi();
    }
  });

  /**
   * Posunutí změny dolů
   */
  $('.posunDolu').click(function() {
    var zmena = $(this).closest('.zmena');
    var naslednik = zmena.nextAll('.zmena:first')
    if(naslednik.length) {
      naslednik.after(zmena);
      ulozitPoradi();
    }
  });

  /**
   * Uložení pořadí změn v protokolu
   */
  function ulozitPoradi() {
    var poradi = new Array();
    var url = $('ul#zmeny').data('url')
    $( '.zmena' ).each(function() {
      poradi.push($(this).data('id'));
    });

    $.get(url, { seznam: poradi.join() });
  }
});
