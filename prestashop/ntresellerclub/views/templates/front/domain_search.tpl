{extends file='page.tpl'}

{block name='page_content'}
<section class="ntrc-domain-search" data-search-url="{$ntrc_domain_search_url|escape:'html':'UTF-8'}" data-cart-url="{$ntrc_domain_cart_url|escape:'html':'UTF-8'}" data-cart-page-url="{$ntrc_cart_url|escape:'html':'UTF-8'}">
  <header class="ntrc-domain-search__header">
    <h1>{l s='Domain Ara' mod='ntresellerclub'}</h1>
    <p>{l s='Satın almak istediğiniz alan adını yazın, uygun olanı sepete ekleyin.' mod='ntresellerclub'}</p>
  </header>

  <form class="ntrc-domain-search__form" data-role="domain-search-form">
    <label for="ntrc-domain-query">{l s='Alan adı' mod='ntresellerclub'}</label>
    <div class="ntrc-domain-search__input-row">
      <input id="ntrc-domain-query" name="domain" type="text" placeholder="ornek.com" autocomplete="off" required>
      <button type="submit" class="btn btn-primary">{l s='Ara' mod='ntresellerclub'}</button>
    </div>
  </form>

  <div class="ntrc-domain-search__message" data-role="domain-search-message" aria-live="polite"></div>
  <div class="ntrc-domain-search__results" data-role="domain-search-results"></div>
</section>
{/block}
