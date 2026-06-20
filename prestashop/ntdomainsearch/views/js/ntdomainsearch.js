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
            html += '<button type="button" disabled>Satın Al (V2)</button>';
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
});
