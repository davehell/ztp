function zvyraznitZmenu (zmena) {
  zmena.addClass( 'zvyraznena' );
  setTimeout(function() {zmena.removeClass('zvyraznena')}, 5000);
}

$( document ).ready(function() {
  //barevné zvýraznění změny, která je zadána v url
  var hash = window.location.hash; //např.: "#z115"
  if (hash.substring(0, 2) == '#z') {
    var zmena = $('.zmena[data-id="' + hash.substring(2) + '"]');
    zvyraznitZmenu(zmena);
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

        /**
         * Zobrazení formuláře pro zadání výsledku testování změny.
         */
        $('button.zmenaFunguje').click(function(event) {
          var zmena = $(this).closest('.zmena');
          $('#frm-testForm-id').val(zmena.data('id'));
          var vysledek = zmena.find('.vysledekTestu').html();
          console.log(vysledek);
          $('#frm-testForm-vysledek_testu').val(vysledek ? vysledek : 'bez připomínek');
          $('#modalZmenaFunguje').modal('show');
          zvyraznitZmenu(zmena);
        });

        /**
         * Vyplnění pole pro výsledek textu na základě zvolené varianty ze seznamu.
         */
        $('#modalZmenaFunguje li button').click(function(event) {
          var text = $(this).data('text');
          $('#frm-testForm-vysledek_testu').val(text);
        });
      },
      success: function(payload) {
        //Po úspěšném zadání výsledku testování změny.
        if(payload.akce == 'testFormSuccess') {
          $('#modalZmenaFunguje').modal('hide');
          var zmena = $('.zmena[data-id="' + payload.zmena + '"]');
          zvyraznitZmenu(zmena);
        }
        if(payload.chyba) {
          alert(payload.chyba);
        }
      }
  });
  $.nette.init();


  /**
   * Potvrzovací dialog při kliknutí na odkaz pro smazání.
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
    zvyraznitZmenu(zmena);
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
    zvyraznitZmenu(zmena);
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
    var url = $('ul#zmeny').data('url');
    $( '.zmena' ).each(function() {
      poradi.push($(this).data('id'));
    });

    $.get(url, { seznam: poradi.join() });
  }
});
