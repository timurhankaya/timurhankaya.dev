# Vampir Köylü gece süresi

Her gece sunucudaki başlangıç zamanından itibaren **45 saniye** sürer. Sayfa
yenilemek süreyi baştan başlatmaz. Oyuncular aynı bitiş zamanını görür. Süre dolunca
verilmiş vampir, doktor ve dedektif kararları mevcut oyun kurallarıyla bir kez
işlenir. Hamle yapmayan roller o gece pas geçer; sistem onlar adına hedef seçmez.
Ölen avcının son atışı normal akışta kalır. Oda sahibi gerekirse geceyi mevcut
“Şafağı açıkla” düğmesiyle erken bitirebilir.

Sunucu her yeni gece için `vampire-timers` kuyruğuna gecikmeli bir işlem koyar.
Bu kuyrukta çalışan bir worker olduğunda sekmeler kapalı olsa da gece sonuçlanır.
İşlem oda, tur, gece numarası ve bitiş zamanını kontrol eder; eski veya tekrarlanan
bir işlem yeni geceyi etkileyemez. Sonuç yazımı mevcut oda kilidinin içindedir.
Süre geçtikten sonra gelen rol hamleleri kabul edilmez.

Yerel geliştirmede yalnız oyun zamanlayıcılarını çalıştırmak için:

```sh
php artisan queue:work --queue=vampire-timers --sleep=1 --tries=3 --timeout=10
```

`composer run dev` içindeki kuyruk dinleyicisi bu kuyruğu da dinler. Production'da
aynı komutu süreç yöneticisi altında, çalışan sürümün `artisan` dosyasıyla başlat.
Deploy sonrasında worker'ı yeniden başlat. `sync` bağlantısı gecikmeli işlemler
için uygun değildir; Redis veya database gibi asenkron bir bağlantı kullan.

Worker gecikirse katılmış oyuncuların oda yenilemesi de aynı sunucu bitiş zamanını
kontrol eder. Gece ekranındaki sayaç yalnız gösterimdir; istemci saati ve public
Livewire alanları geceyi erken bitiremez. Gece yenilemesi arka plandaki sekmelerde
de devam eder. Otomatik gece sonu normal oyun kuralıdır; yerel bot paneli bu aşama
kararını ayrıca çalıştırmaz.

Tüm yaşayan oyuncularda kapıyı kontrol etme, perdeyi kapatma ve mumu söndürme
hazırlıkları bulunur. Bunlar yalnız o ekranda etkileşim verir; koruma, bilgi,
oy veya sonuç üretmez. Böylece köylü ve avcı da gece ekranında uğraşabilir,
yalnız tıklama yapıp yapmamak oyuncunun rolünü açığa çıkarmaz.
