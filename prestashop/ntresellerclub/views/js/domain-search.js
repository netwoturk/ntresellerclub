(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
      return;
    }
    document.addEventListener('DOMContentLoaded', fn);
  }

  function messageForCode(code, fallback) {
    if (code === 'product_mapping_missing') {
      return 'Ürün eşleştirmesi yapılmamış.';
    }
    if (code === 'unavailable') {
      return 'Domain artık müsait değil.';
    }
    if (code === 'duplicate') {
      return 'Bu domain zaten sepetinizde.';
    }
    return fallback || 'İşlem tamamlanamadı.';
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  ready(function () {
    var root = document.querySelector('.ntrc-domain-search');
    if (!root) {
      return;
    }

    var form = root.querySelector('[data-role="domain-search-form"]');
    var results = root.querySelector('[data-role="domain-search-results"]');
    var message = root.querySelector('[data-role="domain-search-message"]');
    var searchUrl = root.getAttribute('data-search-url');
    var cartUrl = root.getAttribute('data-cart-url');
    var cartPageUrl = root.getAttribute('data-cart-page-url');

    function setMessage(text, type) {
      message.className = 'ntrc-domain-search__message is-' + (type || 'info');
      message.textContent = text || '';
    }

    function renderResults(items) {
      if (!items || !items.length) {
        results.innerHTML = '<p class="ntrc-domain-search__empty">Sonuç bulunamadı.</p>';
        return;
      }

      results.innerHTML = items.map(function (item) {
        var add = item.add_to_cart || {};
        var disabled = item.available ? '' : ' disabled';
        var price = item.final_sale_price !== null && item.final_sale_price !== undefined
          ? escapeHtml(item.final_sale_price + ' ' + (item.currency || ''))
          : '-';

        return '<article class="ntrc-domain-result">' +
          '<div><strong>' + escapeHtml(item.domain) + '</strong><span>' + escapeHtml(item.provider_code || '') + '</span></div>' +
          '<div class="ntrc-domain-result__status">' + escapeHtml(item.available ? 'Müsait' : 'Müsait değil') + '</div>' +
          '<div class="ntrc-domain-result__price">' + price + '</div>' +
          '<button type="button" class="btn btn-primary" data-role="add-domain" data-domain="' + escapeHtml(add.domain || item.domain) + '" data-years="' + escapeHtml(add.years || 1) + '" data-token="' + escapeHtml(add.cart_token || '') + '"' + disabled + '>Sepete Ekle</button>' +
          '</article>';
      }).join('');
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var input = form.querySelector('input[name="domain"]');
      var query = input ? input.value.trim() : '';
      if (!query) {
        setMessage('Lütfen bir domain yazın.', 'error');
        return;
      }

      setMessage('Domain kontrol ediliyor...', 'info');
      results.innerHTML = '';

      fetch(searchUrl + (searchUrl.indexOf('?') === -1 ? '?' : '&') + 'domain=' + encodeURIComponent(query), {
        credentials: 'same-origin'
      }).then(function (response) {
        return response.json();
      }).then(function (data) {
        if (!data || data.success === false) {
          setMessage(messageForCode(data && data.code, data && data.error), 'error');
          return;
        }
        setMessage('', 'info');
        renderResults(data.results || []);
      }).catch(function () {
        setMessage('Arama sırasında hata oluştu.', 'error');
      });
    });

    results.addEventListener('click', function (event) {
      var button = event.target.closest('[data-role="add-domain"]');
      if (!button || button.disabled) {
        return;
      }

      var body = new URLSearchParams();
      body.set('domain', button.getAttribute('data-domain'));
      body.set('years', button.getAttribute('data-years') || '1');
      body.set('cart_token', button.getAttribute('data-token') || '');

      button.disabled = true;
      setMessage('Sepete ekleniyor...', 'info');

      fetch(cartUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString()
      }).then(function (response) {
        return response.json();
      }).then(function (data) {
        if (!data || data.success === false) {
          button.disabled = false;
          setMessage(messageForCode(data && data.code, data && data.message), 'error');
          return;
        }
        setMessage('Domain sepete eklendi. ', 'success');
        message.innerHTML = 'Domain sepete eklendi. <a href="' + escapeHtml(cartPageUrl) + '">Sepete Git</a>';
      }).catch(function () {
        button.disabled = false;
        setMessage('Sepete ekleme sırasında hata oluştu.', 'error');
      });
    });
  });
})();
