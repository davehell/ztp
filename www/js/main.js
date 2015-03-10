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
    var url = $('h2#zmeny').data('url')
    $( '.zmena' ).each(function() {
      poradi.push($(this).data('id'));
    });

    $.get(url, { seznam: poradi.join() });
  }
});
