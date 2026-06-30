$(function() {
  $('[data-toggle="tooltip"]').tooltip();
});

$(document).on('click', '.anchor-link', function (event) {
  event.preventDefault();

  $('html, body').animate({
    scrollTop: $($.attr(this, 'href')).offset().top - 50
  }, 500);
});

$('.mark-indication').on('click mouseenter mouseleave', function() {
  let prefix = 'indication-';
  let selector = '#' + prefix + $(this).attr('data-indication');

  $('*[id^="' + prefix + '"]').removeClass('active');

  $(selector).toggleClass('active');
  $(selector + '-text').toggleClass('active');
});