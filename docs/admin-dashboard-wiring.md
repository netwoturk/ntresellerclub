# Admin Dashboard Wiring

Bu adımda admin dashboard için veri katmanı eklendi.

Eklenen sınıf:

- `prestashop/ntresellerclub/classes/NtRcDashboard.php`

Bu sınıf şu verileri hazırlar:

| Veri | Kaynak |
|---|---|
| Provider listesi | `ps_ntresellerclub_provider` |
| Servis özeti | `ps_ntresellerclub_service` |
| TLD route listesi | `ps_ntresellerclub_tld_route` |
| Yenileme bildirim özeti | `ps_ntresellerclub_notice` |
| Son loglar | `ps_ntresellerclub_log` |

Bir sonraki fazda ana `ntresellerclub.php` yapılandırma ekranına bu veri katmanı bağlanacak. Admin ekranda provider sayısı, route sayısı, servis durumu ve son loglar kutular halinde gösterilecek.
