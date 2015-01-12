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
  $('#menuForm #vyberVerze, #menuForm #vyberPohledu, #menuForm #vyberUzivatele, #menuForm #vyberProtokolu').change(function(event) {
    var id = $( this ).val();
    if(!id) return;
    $('#menuForm').submit();
  });
});
