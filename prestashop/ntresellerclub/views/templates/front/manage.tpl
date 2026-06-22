{extends file='page.tpl'}

{block name='page_content'}
<h1>{l s='Hizmet Yonetimi' mod='ntresellerclub'}</h1>
<p><a href='{$nt_back_url}'>{l s='Hizmetlerime Don' mod='ntresellerclub'}</a></p>
<p>{$nt_service.domain_name}</p>
<p>{$nt_service.provider_code}</p>
<p>{$nt_service.status}</p>
{/block}
