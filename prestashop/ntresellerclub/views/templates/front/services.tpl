{extends file='page.tpl'}

{block name='page_content'}
<h1>{l s='Hizmetlerim' mod='ntresellerclub'}</h1>

{if $nt_services|count}
  <table class="table table-striped">
    <thead>
      <tr>
        <th>{l s='Hizmet' mod='ntresellerclub'}</th>
        <th>{l s='Provider' mod='ntresellerclub'}</th>
        <th>{l s='Alan Adı' mod='ntresellerclub'}</th>
        <th>{l s='Durum' mod='ntresellerclub'}</th>
        <th>{l s='Başlangıç' mod='ntresellerclub'}</th>
        <th>{l s='Bitiş Tarihi' mod='ntresellerclub'}</th>
        <th>{l s='İşlem' mod='ntresellerclub'}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$nt_services item=s}
        <tr>
          <td>{$s.service_type|escape:'html':'UTF-8'}</td>
          <td>{$s.provider_code|escape:'html':'UTF-8'}</td>
          <td>{$s.domain_name|escape:'html':'UTF-8'}</td>
          <td>{$s.status|escape:'html':'UTF-8'}</td>
          <td>{$s.start_date|escape:'html':'UTF-8'}</td>
          <td>{$s.expiry_date|escape:'html':'UTF-8'}</td>
          <td><a class="btn btn-primary" href="{$s.manage_url|escape:'html':'UTF-8'}">{l s='Yönet' mod='ntresellerclub'}</a></td>
        </tr>
      {/foreach}
    </tbody>
  </table>
{else}
  <p>{l s='Henüz kayıtlı hizmetiniz bulunmuyor.' mod='ntresellerclub'}</p>
{/if}
{/block}
