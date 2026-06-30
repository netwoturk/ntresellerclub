{extends file='page.tpl'}

{block name='page_content'}
<h1>{l s='Hizmetlerim' mod='ntresellerclub'}</h1>

{if $nt_services|count}
  <table class="table table-striped">
    <thead>
      <tr>
        <th>{l s='Domain' mod='ntresellerclub'}</th>
        <th>{l s='Hizmet Tipi' mod='ntresellerclub'}</th>
        <th>{l s='Provider' mod='ntresellerclub'}</th>
        <th>{l s='Servis Durumu' mod='ntresellerclub'}</th>
        <th>{l s='Queue Durumu' mod='ntresellerclub'}</th>
        <th>{l s='Bitiş Tarihi' mod='ntresellerclub'}</th>
        <th>{l s='Oluşturma' mod='ntresellerclub'}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$nt_services item=s}
        <tr>
          <td>{$s.domain_name|escape:'html':'UTF-8'}</td>
          <td>{$s.service_type|escape:'html':'UTF-8'}</td>
          <td>{$s.provider_code|escape:'html':'UTF-8'}</td>
          <td>{$s.status|escape:'html':'UTF-8'}</td>
          <td>{if isset($s.queue_status) && $s.queue_status}{$s.queue_status|escape:'html':'UTF-8'}{else}-{/if}</td>
          <td>{$s.expiry_date|escape:'html':'UTF-8'}</td>
          <td>{$s.created_at|escape:'html':'UTF-8'}</td>
        </tr>
      {/foreach}
    </tbody>
  </table>
{else}
  <p>{l s='Henüz kayıtlı hizmetiniz bulunmuyor.' mod='ntresellerclub'}</p>
{/if}
{/block}
