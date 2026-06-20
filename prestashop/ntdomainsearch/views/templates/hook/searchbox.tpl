<section class="nt-domain-search-box">
  <div class="nt-domain-search-inner">
    <h2>{l s='Alan adınızı arayın' mod='ntdomainsearch'}</h2>
    <p>{l s='Uygun alan adını bulun, PrestaShop üzerinden satın alın.' mod='ntdomainsearch'}</p>
    <form id="nt-domain-search-form">
      <input type="text" name="domain" placeholder="netwoturk" autocomplete="off">
      <div class="nt-tlds">
        {foreach from=$nt_tlds item=tld}
          <label>
            <input type="checkbox" name="tlds[]" value="{$tld|escape:'html':'UTF-8'}" {if $tld == 'com'}checked{/if}>
            .{$tld|escape:'html':'UTF-8'}
          </label>
        {/foreach}
      </div>
      <button type="submit">{l s='Sorgula' mod='ntdomainsearch'}</button>
    </form>
    <div id="nt-domain-search-result"></div>
  </div>
</section>
