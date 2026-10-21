# Yerel oyun simülasyonu, aksiyon çubuğu ve CV sunumu

Tarih: 21 Ekim 2026, Europe/Istanbul

Durum: Uygulama ve botlarla tarayıcı doğrulaması tamamlandı. Kullanıcının devam
talebiyle 45 saniyelik otomatik gece sonu ve gece hazırlıkları eklendi.

## Devam talebi: gece süresi ve site tasarımı

Kullanıcı gece süresini 45 saniye olarak onayladı. Bu normal oyun kuralı,
simülasyondan bağımsız bir sunucu bitiş zamanı ve gecikmeli kuyruk işlemiyle
uygulanır. Sekmeler kapalı olsa da çalışan kuyruk geceyi sonuçlandırır. Katılmış
oyuncuların yenilemesi aynı bitiş zamanını kontrol eden yedektir. Eski tur/gece
işlemleri ve geç gelen rol kararları yeni sonucu değiştiremez.

Yaşayan tüm rollere aynı isteğe bağlı kapı, perde ve mum hazırlıkları gösterilir.
Bunlar oyun sonucu veya koruma üretmez. Köylü de gece ekranında etkileşebilir;
hazırlıkların bulunması rolünü açığa çıkarmaz.

Kullanıcının tasarım düzeltmesiyle tüm aşamalarda mevcut siyah/grafit yüzeyler
ve turuncu vurgular korunur; kahverengi gündüz yüzeyleri kaldırılır. Aşamalar
metin ve simgelerle ayırt edilir. İşletim ayrıntıları: `docs/vampire-night-timer.md`.

## Amaç ve kabul edilen kapsam

Word Imposter ve Vampir Köylü, geliştirme sırasında başka oyuncular beklenmeden
tek tarayıcıdan denenebilecek. Kullanıcı bot ekleyip kaldırabilecek, mevcut
aşamadaki bot hamlelerini elle çalıştırabilecek ve isterse otomatik bot hamlelerini
açabilecek. Oyun aşamalarını değiştiren mevcut oda sahibi kontrolleri korunacak.

Bu çalışmaya ayrıca şunlar dahil:

- “Oylama başlat” ve diğer oda sahibi eylemlerinin alt çubuğunda arka plan blur'u.
- Türkçe ve İngilizce CV'de kurucu ortak unvanının kaldırılması; Monafy'nin yapılan
  ürün ve mühendislik çalışmaları üzerinden anlatılması.
- Çalışan ve doğrulanan geliştirme için iki dilli bir case study yayımlanması.

Kullanıcı testleri ve `npm run build` komutunu yalnız geliştirmeler finalize
edilirken çalıştırmamızı istiyor. Mevcut `.env.example` değişikliği bu çalışmaya
ait değil; korunacak ve commitlere alınmayacak.

## Mevcut yapıdan çıkan kararlar

Oyunlar Livewire oda bileşenlerini, `CacheRoomService` ortak oda işlemlerini ve
oyuna özgü `ImposterRoomService` / `VampireRoomService` kurallarını kullanıyor.
Oda yazımları mevcut servislerde kilit altında yapılıyor. İstemciye giden veri
ayrı presenter'lardan geçiyor; diğer oyuncuların gizli bilgileri burada ayıklanıyor.

Imposter en az üç oyuncu istiyor ve oy verme sırasını `currentVoterId` ile
yönetiyor. Vampir Köylü en az dört oyuncu istiyor; oyuncu sayısı yapılandırılmış
rollerin toplamıyla eşleşmeli. Simülasyon bu koşulları aşmayacak.

Oda sayfaları zaten belirli aralıklarla `refreshRoom` çağırıyor. İlk sürüm, açık
oda sayfasının yenileme döngüsünü kullanacak; ayrı daemon, scheduler veya kuyruk
işçisi gerektirmeyecek.

Derlenmiş CSS incelendiğinde `.game-host-bar` için yalnız
`-webkit-backdrop-filter` kaldığı görüldü. Kaynakta standart özelliğin öne
yazılması, mevcut üretim optimizasyonunda bu sonuca neden oluyor. Navbar'da
kullanılan, standart özelliği sona koyan düzen burada da uygulanacak.

CV şu anda anasayfayla aynı `stats.role_value`, `about.p1/p2/p3` ve proje
açıklamalarını kullanıyor. CV paylaşım kartı da ortak kurucu ortak unvanını
taşıyor. CV için açık içerik alanları kullanmak, metin üzerinde kelime silme
işlemleri yapmadan doğru sunumu sağlayacak.

## Seçilen yaklaşım ve alternatif

Seçilen yaklaşım, mevcut oyun servisleri üzerinden çalışan yerel simülasyondur.
Gerçek oyuncuların kullandığı katılım ve hamle kuralları tekrar kullanılacak;
oyun durumuna doğrudan sahte sonuç yazılmayacak. Basit, kural tabanlı botlar
kullanılacak; LLM veya harici AI API'si gerekmiyor.

Alternatif, her bot için gerçek bir tarayıcı oturumu açmaktır. Bu yaklaşım
istemci bağlantılarını ve sayfa davranışlarını da kapsar; daha fazla kaynak ve
oturum yönetimi gerektirir. İlk sürümün amacı hızlı yerel oyun akışı kontrolüdür.
Gerçek çoklu oturum testleri ayrı doğrulama olarak kalır.

## Yerel test paneli

Her iki oyunun oda sayfasında yalnız yerel ortamda, gerçek oda sahibine görünen
“Yerel test” paneli bulunacak. Türkçe ve İngilizce metinleri olacak.

Panel şu işlemleri sunacak:

- Belirtilen sayıda bot ekle ve yalnız eklenen botları kaldır. Katılım ve kaldırma
  lobiyle sınırlı olacak. Bir odada en fazla 12 simülasyon botu bulunacak.
- “Botları oynat”: Mevcut aşamada hareket edebilen botların henüz yapılmamış
  hamlelerini çalıştır. İnsan oyuncunun sırası geldiğinde bekle.
- “Otomatik hamleler”: Varsayılan kapalı. Açıldığında mevcut yenileme döngüsünde
  uygun bot hamleleri çalışır; kapatıldığında yeni bot hamlesi üretilmez.
- Güncel bot sayısı, otomatik çalışma durumu ve durma/bekleme nedeni gösterilir.

Vampir Köylü'de bot eklemek rol ayarlarını sessizce değiştirmeyecek. Panel,
oyuncu sayısı ile rol toplamını birlikte gösterecek ve uyuşmazlığı açıklayacak.
Başlatma kararı mevcut oyun servisinin kontrolünde kalacak.

Panel yalnız otomatik bot hamlelerini yönetir. Oyunu başlatma, gece aşamasını
ilerletme, gündüz oylamasını açma, sonuç açıklama ve yeni tur başlatma gibi oda
sahibi kararlarını kendiliğinden çalıştırmaz. Sekme kapalıyken otomatik oyun
devam etmez; bu davranış panelde kısa biçimde açıklanır.

## Sunucu sınırı ve simülasyon durumu

`app/Services/Games/Simulation/` altında küçük bir düzenleyici, oyun başına hamle
seçimi ve sunucuda tutulan bot kaydı bulunacak. İki oyunun Livewire bileşenleri
aynı düzenleyiciyi çağıracak; oyuna özgü kararlar ayrı kalacak.

Her ekleme, kaldırma, elle oynatma ve otomatik oynatma isteğinde sunucu:

1. Ortamın `local` veya `testing` olduğunu doğrular. `APP_DEBUG` yeterli sayılmaz.
2. Oyuncu kimliğini mevcut oyunun sunucu session kaydından alır.
3. Odayı yeniden okuyup bu oyuncunun güncel oda sahibi olduğunu doğrular.
4. Hamle yapılacak kimliğin o oda için sunucuda kayıtlı bir bot olduğunu doğrular.

İstemciden gelen `isHost`, oyuncu listesi veya bot kimlikleri yetki kaynağı
olmaz. Production ortamında panelin gizlenmesine ek olarak sunucu işlemleri de
reddedilir; oda durumunda hiçbir değişiklik yapılmaz.

Botlar normal katılım metodu üzerinden oyuncu kimliği alır. Parolalı odada
parola, yetkilendirilmiş sunucu oda kaydından kullanılır; yeni istemci alanı
olarak dışarı verilmez. Bot kaydı yalnız sunucuda, oyun türü ve oda koduyla
ayrılmış bir cache anahtarında tutulur. İnsan oyuncular bot kaydına eklenmez.

Kayıt, bot kimliklerini, otomatik çalışma seçimini ve aynı aşamada tamamlanan
bot işlemlerini taşır. Odanın mevcut yaşam süresiyle uyumlu süreli cache
kullanılır. Oda bulunamazsa kayıt temizlenir. Oda sahibi değişirse otomatik
çalışma durur; yeni sahibi açıkça yeniden etkinleştirebilir.

Paralel yenileme ve çift tıklama, ayrı bir simülasyon kilidiyle sıralanır.
Bu kilit mevcut oyun oda kilidiyle aynı anahtar değildir; oyun servislerinin
kendi kilitleri korunur. Her hamleden önce güncel oda yeniden okunur. Başarılı
işlem, oyun/tur/aşama/bot/hamle türüyle kayıt altına alınır. Aynı isteğin yeniden
gelmesi ikinci bir hamle üretmez; geçersizleşmiş aşamada işlem yapılmaz.

Oda kodu kullanılarak farklı bir odaya geçilmesi veya gerçek oyuncu adına hamle
yapılması desteklenmez. Bot kimliği yalnız o odaya ait sunucu kaydından seçilir.

## Botların oyun davranışı

### Word Imposter

Bot ekleme normal lobi katılımıyla yapılır. Oyun ve oylama normal oda sahibi
eylemleriyle başlatılır. Sırası gelen bot, kendisi dışındaki geçerli bir oyuncuya
mevcut `castVote` üzerinden oy verir. Arka arkaya bot sıraları varsa elle oynatma
işlemi bunları ilerletir; insan sırasına veya aşama sonuna gelince durur.

Botlar sohbet veya kelime ipucu üretmez; Imposter'ın kelimeyi bildiğini ilan eden
oda sahibi eylemini taklit etmez. Amaç oy verme ve sonuç ekranlarının akışıdır.

### Vampir Köylü

Bot, yalnız kendi rolünün ve mevcut gece alt aşamasının izin verdiği işlemi yapar:
vampir gece oyu, doktor koruması, dedektif sorgusu, gerekiyorsa avcının son atışı
ve yaşayan oyuncuların gündüz oyları. Ölü botların normal oyları çalıştırılmaz.

Hedefler, botun oyuncu olarak bilmesine izin verilen bağlamdan seçilir. Diğer
rollerin gizli bilgileri, rolünün bilmesine izin verilmeyen takım bilgileri veya
gizli doktor hedefleri tercih üstünlüğü sağlamak için kullanılmaz. Rolün geçerli
hedef ve tekrar kuralları mevcut servislerce uygulanır. Uygun hedef bulunamazsa
bot hamle üretmez; panel bekleme nedenini bildirir.

Hedef seçimi, aynı oda ve aşamadaki aynı uygun aday kümesi için kararlı olacak.
Bu tercih hata ayıklamayı kolaylaştırır; rol dağıtımını deterministik veya
gerçek insan davranışını temsil ediyor hale getirdiği iddia edilmeyecek.

Presenter'ların gizlilik sınırı korunur. Test paneli sunucu bot kaydını, diğer
oyuncuların rollerini veya ham bilgiyi Livewire public property'lerine taşımaz.

## Alt aksiyon çubuğunun blur'u

İki oyunun ortak `.game-host-bar` stilinde yarı saydam zemin ve standart / WebKit
blur özellikleri birlikte korunacak. Standart `backdrop-filter` en son yazılacak.
Blur desteği olmayan tarayıcılar için okunabilir, daha opak bir zemin bulunacak.
Mevcut sticky konumu, buton erişilebilirliği ve mobil güvenli alan boşluğu korunur.

Doğrulama ham CSS ile sınırlı kalmaz: production build sonrasında gerçek
tarayıcıda hesaplanan blur değeri ve arka plandaki içerikle görünümü kontrol edilir.

## CV'nin Monafy sunumu

Portfolio içeriklerinde CV'ye özgü `cv.role`, `cv.profile` ve Monafy proje
açıklaması tanımlanacak. CV şablonu açık alanları kullanacak; genel metinden
“kurucu ortak” kelimelerini otomatik ayıklamayacak.

CV rolü:

- TR: **Yazılım ve AI Mühendisi**
- EN: **Software & AI Engineer**

Monafy'nin CV açıklaması ürünün kapsamını ve yapılan işi anlatacak:

> Türkiye ve ABD borsaları için finansal verileri, piyasa raporlarını ve AI
> asistanını bir araya getiren araştırma platformu. Ürün akışlarını ve servis
> mimarisini tasarlıyor, AI entegrasyonlarını ve kodlama ajanlarıyla geliştirme
> sürecini yönetiyorum.

İngilizce metin aynı kapsamla doğal biçimde yazılacak. CV profilinde Wingie
Enuygun Group'taki çalışma ile Monafy'deki ürün çalışması ayrı anlatılacak;
Monafy yeni bir maaşlı iş kaydı gibi eklenmeyecek. Yapılmamış görev, yönetim
sorumluluğu veya ölçülmemiş sonuç eklenmeyecek.

Görünür CV, yazdırılabilir sürüm, CV metadata'sı, Person verisi ve CV paylaşım
görselleri bu anlatımla uyumlu olacak. `og-images.mjs` CV kartına özgü rol
tanımını destekleyecek; diğer kartların ortak rol tanımı korunacak. Değişen CV
görsellerinin önbellekten eski unvanı göstermemesi için sürümlü görsel adresi
kullanılacak.

## Case study ve yayın

Yazı, uygulama ve final doğrulama bittikten sonra hazırlanıp yayımlanacak.
Başlık yönü: **Çok oyunculu oyunları tek tarayıcıdan test etmek**.

Tek iki dilli vaka; iki oyunda ortak test problemini, mevcut kuralları yeniden
kullanma kararını, yerel ortam sınırını ve kontrol edilen akışları anlatacak.
Mevcut akış diyagramı ve gerekiyorsa kısa, sadeleştirilmiş bir kod örneği
kullanılabilir. Başlık, kart özeti ve SEO açıklaması ayrı editoryal kontrolden geçer.

Sınır açıkça anlatılır: servis tabanlı botlar, çoklu gerçek istemci bağlantısını,
ağ gecikmesini veya insanın blöf davranışını test etmez. Test süresinde sayısal
kazanç, kapsama oranı veya production kullanım sonucu ölçülmedikçe yazılmaz.
Bu özellik yerel geliştirme aracıdır; canlı oyunlara bot ekleme özelliği olarak
tanıtılmaz. Özel altyapı, gerçek oda kodu ve kişisel test bilgileri yayımlanmaz.

Yazının iki dilde kendine ait slug'ı, canonical/hreflang alanları, paylaşım
görseli ve gerçek çalışan bağlantıları doğrulanır. Kullanıcının bu vaka için
yayın isteği uygulanır; dış kanallara paylaşım yapılmaz.

## Final doğrulama ve tamamlanma ölçütleri

Testler ve build, uygulama finalize edilirken çalıştırılır. Asgari kontroller:

- Her iki oyunda bot ekleme/kaldırma lobiyle ve kayıtlı botlarla sınırlıdır;
  insan oyuncular kaldırılmaz. Parolalı katılım ve oda sınırı korunur.
- Production ve yetkisiz/oda sahibi olmayan istekler, Livewire eylemi doğrudan
  çağrılsa da oda durumunu değiştirmez.
- Imposter'ın bot sıraları ilerler; insan sırası beklenir ve geçersiz hedef
  seçilmez. Aynı işlem ikinci oy oluşturmaz.
- Vampir Köylü'de rol toplamı normal kurallarla doğrulanır. Gece rolleri, ölü
  oyuncular, tekrar koruma/sorgu sınırları ve avcı son atışı normal kurallara uyar.
- Otomatik mod açılıp kapatılır; faz geçişini üstlenmez. Yeni turda işlem geçmişi
  doğru sınırdan başlar; oda sahibi değişimi ve silinmiş oda güvenle ele alınır.
- Bot eklenmiş odalarda da gizli bilgi içeren presenter/Livewire veri sızıntısı
  testleri geçer.
- Türkçe/İngilizce CV'de kurucu ortak unvanı görünür metin, CV özeti, meslek
  verisi ve CV paylaşım görselinde yer almaz. Monafy'nin teknik katkı anlatımı
  doğrulanır; A4 yazdırma görünümünde taşma olmaz.
- Derlenmiş CSS'te ve masaüstü/mobil gerçek tarayıcıda alt aksiyon çubuğunun
  blur'u çalışır; yerel test paneli üretimde görünmez ve kullanılamaz.
- Tam test paketi ve `npm run build` başarılıdır. Kod canlıya alınır; botların
  yalnız yerelde kullanılabildiği, CV ve vaka sayfalarının canlıda doğru olduğu
  doğrulanır.

## Tasarım öz incelemesi

Kapsam onayı, kullanıcı tarafından seçilen kontrollü simülasyonla uyumlu.
Otomatik bot hamlesi ile oda sahibi faz kararı ayrıldı. İstemci gizliliği,
sunucu ortam/yetki kontrolü, cache yaşam süresi ve tekrar işleme davranışı
tanımlandı. Case study'nin gözlemlenebilir sonuç sınırı açık. Uygulama planı
bu yazılı tasarım onaylandıktan sonra hazırlanacak.
