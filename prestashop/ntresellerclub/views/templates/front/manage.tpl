{extends file='page.tpl'}

{block name='page_content'}
<h1>{l s='Hizmet Yonetimi' mod='ntresellerclub'}</h1>
<p><a class='btn btn-secondary' href='{$nt_back_url}'>{l s='Hizmetlerime Don' mod='ntresellerclub'}</a></p>

{if $nt_action_result}
  {if $nt_action_result.success}
    <div class='alert alert-success'>{l s='Islem basarili' mod='ntresellerclub'}</div>
  {else}
    <div class='alert alert-danger'>{l s='Islem basarisiz' mod='ntresellerclub'}</div>
  {/if}
{/if}

<table class='table table-striped'>
  <tr><th>{l s='Alan Adi' mod='ntresellerclub'}</th><td>{$nt_service.domain_name}</td></tr>
  <tr><th>{l s='Provider' mod='ntresellerclub'}</th><td>{$nt_service.provider_code}</td></tr>
  <tr><th>{l s='Hizmet Tipi' mod='ntresellerclub'}</th><td>{$nt_service.service_type}</td></tr>
  <tr><th>{l s='Durum' mod='ntresellerclub'}</th><td>{$nt_service.status}</td></tr>
  <tr><th>{l s='Baslangic' mod='ntresellerclub'}</th><td>{$nt_service.start_date}</td></tr>
  <tr><th>{l s='Bitis' mod='ntresellerclub'}</th><td>{$nt_service.expiry_date}</td></tr>
</table>

<h2>{l s='Islemler' mod='ntresellerclub'}</h2>
{if $nt_actions|count}
  {foreach from=$nt_actions key=action item=label}
    <form method='post' style='display:inline-block;margin:5px;'>
      <input type='hidden' name='nt_service_action' value='{$action}'>
      <button type='submit' class='btn btn-primary'>{$label}</button>
    </form>
  {/foreach}
{else}
  <p>{l s='Bu hizmet icin islem bulunmuyor' mod='ntresellerclub'}</p>
{/if}
{/block}
