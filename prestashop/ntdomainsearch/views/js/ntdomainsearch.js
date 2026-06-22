document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('nt-domain-search-form');
  var result = document.getElementById('nt-domain-search-result');
  if (!form || !result) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    result.innerHTML = '<div class="nt-loading">Sorgulanıyor...</div>';

    var data = new FormData(form);

    fetch(ntDomainSearchAjax, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        if (!json.success) {
          result.innerHTML = '<div class="nt-error">' + (json.message || json.error || 'Sorgu başarısız') + '</div>';
          return;
        }

        var html = '<div class="nt-results">';
        Object.keys(json.data || {}).forEach(function (name) {
          var item = json.data[name];
          var status = item.status || item;
          html += '<div class="nt-result nt-status-' + status + '">';
          html += '<strong>' + name + '</strong><span>' + status + '</span>';
          if (status === 'available') {
            html += '<button type="button" class="nt-buy-domain" data-domain="' + name + '">Satın Al</button>';
          }
          html += '</div>';
        });
        html += '</div>';
        result.innerHTML = html;
      })
      .catch(function () {
        result.innerHTML = '<div class="nt-error">Bağlantı hatası oluştu.</div>';
      });
  });

  result.addEventListener('click', function (e) {
    if (!e.target.classList.contains('nt-buy-domain')) return;

    var domain = e.target.getAttribute('data-domain');
    var data = new FormData();
    data.append('domain', domain);
    data.append('years', '1');

    e.target.disabled = true;
    e.target.innerHTML = 'Ekleniyor...';

    fetch(ntDomainAddToCartAjax, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        if (!json.success) {
          e.target.disabled = false;
          e.target.innerHTML = 'Satın Al';
          alert(json.message || 'Sepete ekleme başarısız.');
          return;
        }
        e.target.innerHTML = 'Sepete Hazır';
      })
      .catch(function () {
        e.target.disabled = false;
        e.target.innerHTML = 'Satın Al';
        alert('Bağlantı hatası oluştu.');
      });
  });
});
