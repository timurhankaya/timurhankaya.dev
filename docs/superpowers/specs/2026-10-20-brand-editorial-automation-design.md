# Kişisel marka, içerik yenileme ve yerel yayın otomasyonu

Tarih: 20 Ekim 2026
Durum: Yazılı tasarım, kullanıcı incelemesi bekleniyor. Uygulama başlamadı.

## Amaç ve kullanıcı kararları

Timurhan Kaya'nın kişisel sitesi, ürün geliştirme deneyimini, AI sistemleriyle
çalışma biçimini ve Monafy kurucu ortaklığını somut işler üzerinden anlatacak.
Hedef okuyucular potansiyel müşteriler, teknik ekipler ve iş ortaklarıdır.
Mevcut yazılar ve vaka incelemeleri, başlıklarından gövde metinlerine kadar
yenilenecek. Ziyaretçi, hangi problemin çözüldüğünü ve Timurhan'ın katkısını
anlayabilmelidir.

Kullanıcının açık kararları:

- Türkçe ve İngilizce kimlik metinleri tutarlı hale getirilecek; kurucu ortaklık
  ana tanımda ve Monafy anlatımında yer alacak.
- AI ile geliştirme, prompt ve context engineering ve ürün içindeki AI
  özellikleri, doğrulanabilen proje örnekleriyle anlatılacak.
- Genel ürün anlatımı **Türkiye ve ABD borsaları** olacak. İngilizcesi
  **Turkish and US stock markets**. Başlıklarda ve tanıtımda "BIST ve Nasdaq"
  ikilisi kullanılmayacak. Bu tercih mevcut içerikler ve otomasyon için geçerli.
  Gerçek ürün kapsamı genişletilmiş gibi gösterilmeyecek; özel bir veri kaynağı,
  kod veya ölçüm için gereken teknik piyasa adları açıklama içinde kullanılabilir.
- Oyun kartlarının açıklamaları detay sayfalarıyla tutarlı olacak; kart çizgileri
  ve eylem bağlantıları farklı metin uzunluklarında hizalanacak.
- Monafy'nin yerel repolarından düzenli olarak özgün yazı ve vaka incelemesi
  üretilecek; uygun bulunanlar kişisel sitede otomatik yayımlanacak.
- Dış kanallar için yalnız paylaşım taslakları hazırlanacak. LinkedIn, DEV.to,
  Monafy veya başka bir sitede otomatik paylaşım bu kapsamda yok.
- İlk yeni konu adayı Monafy'nin piyasa raporu özelliğidir.
- Geliştirmeler finalize edilirken testler ve `npm run build` çalıştırılacak.
- Sonuç prod ortamına alınacak ve kullanıcı canlı sitede doğrulayacak.

Tasarım varsayımları: günlük tarama 10.00 Europe/Istanbul; yedi günlük hareketli
pencerede en fazla iki yeni iki dilli içerik. Bunlar yayın zorunluluğu değil üst
sınırdır. Uygun konu yoksa içerik yayımlanmaz.

## Mevcut yapı ve araştırma bulguları

- Laravel 12/PHP 8.4 uygulamasında `Post`, Filament yönetim paneli, makale ve
  vaka incelemesi türleri, yerelleştirilmiş adresler ve yayın doğrulaması var.
- Türkçe birincil alanlarda, İngilizce `translations.en` içinde tutuluyor.
- Kaynak içerikler `resources/content/stories/*.json` dosyalarında; canlı sayfalar
  veritabanından okunuyor. `StorySeeder` mevcut kayıtları güncellemiyor.
  `stories:sync` seçilen mevcut yazıları güncelliyor; Türkçe `meta_description`
  aktarımı da içerik yenilemesinde tamamlanmalı.
- Rol metinleri `brand.php`, iki portfolio JSON dosyası, Blade şablonları,
  JSON-LD ve paylaşım kartlarına dağılmış durumda.
- `FeedController`, sitemap, canonical/hreflang, `llms.txt` ve IndexNow mevcut.
  `routes/console.php` içinde içerik otomasyonu yok.
- Monafy'nin servis ve paketleri ayrı Git repoları. Çok sayıda worktree var.
  Bazı ana checkout'lar `origin/main` geçmişinden geride; yalnız HEAD okumak
  yeni piyasa raporu çalışmalarını kaçırıyor.
- Yerel `codex` mevcut ve `codex login status`, ChatGPT ile oturum açıldığını
  bildiriyor. Tasarım, doğrudan bir model API'si veya yeni API anahtarı gerektirmiyor.
- İncelemede `.env.example` zaten değişmişti. Bu mevcut kullanıcı değişikliği
  korunacak; ilgisiz dosyalar yayına veya commitlere dahil edilmeyecek.

## Seçilen yaklaşım ve alternatifler

**Önerilen yaklaşım:** Yerel Codex zamanlanmış görevi araştırma ve yazarlığı
yapar; bu repodaki deterministik Artisan komutları kaynak toplama, doğrulama ve
yayın aktarımını yürütür. İçerik politikası ve çıktı şeması repoda sürümlenir.
Prod uygulaması yalnız doğrulanan içerik paketlerini içe aktarır.

Alternatif: Laravel scheduler'ın yerelde `codex exec` başlatması. Yapılabilir,
ancak yerel scheduler'ın sürekli çalıştırılması ve süreç/oturum yönetimi için
ayrıca işletim sistemi kurulumu gerektirir. İlk sürümde ikinci bir zamanlayıcı
kurulmaz. Artisan komutları elle de çalıştırılabilir olacak.

Alternatif: Sunucudan doğrudan AI API çağrılarıyla yazı üretmek. Yerel kaynak
erişimi gereksinimini karşılamak için ilave aktarım ve kimlik bilgisi gerekir.
Bu tasarıma alınmaz.

## Marka ve editoryal tasarım

### Kimlik

- Ana İngilizce tanım: **Software & AI Engineer · Co-founder**.
- Türkçe karşılığı: **Yazılım ve AI Mühendisi · Kurucu ortak**.
- Kurucu ortaklık Monafy'ye bağlanır. İş geçmişindeki resmî pozisyon adları
  tarihsel olarak korunur; genel kimlik veya mevcut şirket unvanıyla karıştırılmaz.
- Anasayfa, hakkımda, CV özeti, iletişim, hizmetler, footer, SEO açıklamaları,
  Person verisi ve paylaşım görselleri aynı kimlik ve doğrulanmış kapsamı anlatır.
- İş yapış biçimi; ihtiyacı netleştirme, ajana doğru bağlam sağlama, kabul
  kriterleri, inceleme ve ürünün yayına alınması üzerinden açıklanır.
- Çalışılmamış görevler, değişmemiş resmî unvanlar veya ölçülmemiş başarılar
  özgeçmişe eklenmez.

### Mevcut içeriklerin yenilenmesi

- Tüm sevk edilen makale ve vaka incelemelerinin iki dili gözden geçirilir;
  başlık, özet, ara başlıklar, gövde, SEO alanları ve ilgili bağlantılar yenilenir.
- Yayımlanmış slug ve türler korunur. İçeriği değiştirmek için URL taşınmaz.
- Başlık, okuyucunun konusunu anlamasını sağlar; kısa olmak uğruna teknik anlam
  kaybolmaz. Sayfaya özgü arama başlığı ve meta açıklaması hazırlanır.
- Açılış, okuyucunun sorusuna veya karşılaşılan probleme doğrudan girer.
  Vaka incelemesi problem, kısıtlar, karar ve gözlenebilir sonucu anlatır.
  Makale bir yöntemi açıklayıp uygulama örneği sunar.
- Metinler iki dilde doğal yazılır; Türkçe metne İngilizce cümle yapısı taşınmaz.
- Kaynak ve ölçüm bulunmayan iddialar çıkarılır veya kanıtın sınırı açıklanır.
  Geçmiş yazılardaki iddialar, kaynakta bulunmaları tek başına doğru sayılmaz.
- Mevcut `StoryStyle` ve yazım rehberi bu ihtiyaçla birlikte gözden geçirilir.
  Karakter/uzunluk kuralları editoryal tercihtir, Google'ın zorunlu SEO sınırı
  gibi sunulmaz. Kelime sayısı doldurmak için metin eklenmez.
- Sitemap'in `lastmod` kaynakları, değişen ortak şablonları ve `brand.php` dil
  dosyalarını da kapsar; yalnız eski portfolio JSON zamanına bağlı kalmaz.
  Başlığı değişen yazıların mevcut OG görselleri yeniden üretilir.
- Admin düzenlemeleriyle fark, yayın öncesinde kontrol edilir. Bu tek seferlik
  yenilemede açıkça seçilen kaynak içerikler aktarılır; otomasyon mevcut insan
  düzenlemelerini kendiliğinden güncellemez.

### Oyun kartları ve hizalama

- Ortak `partials/brand-games` üzerinden anasayfa ve oyun listesi aynı açıklama
  kaynağını kullanır. Oyuncu rolleri ve kurallar gerçek oyun davranışına uyar.
- Başlık, açıklama, varsa oyuncu bilgileri ve CTA ayrı düzen alanları olur.
  Kartlar satır yüksekliğini paylaşır; ayırıcı çizgi ve CTA alt kenara yerleşir.
- Metin kesmek veya tek bir genişliğe göre sabit piksel yüksekliği vermek yerine
  esnek düzen kullanılır. İki dil ve dar ekranlarda taşma olmamalıdır.
- Yazı ve proje kartlarında da görülen benzer çizgi/hiza sorunları aynı kapsamda
  düzeltilir. Sitenin mevcut siyah/turuncu görsel kimliği korunur.

## Yerel otomasyon ve veri akışı

### 1. Kaynak toplama

`editorial:collect` komutu yalnız yapılandırılmış Monafy kökü altındaki ana servis
ve paket repolarını keşfeder. `.git` ortak diziniyle kimlik belirler; worktree,
vendor, node_modules, eski kanıt kopyaları ve arşivleri aynı repo sanarak taramaz.
Kullanıcı çalışma dosyaları, branch ve index değiştirilmez.

Normal koşu, uzak ana dalı fetch eder ve önceki başarılı taramadan sonraki yeni
commitleri okur. Checkout veya pull yapılmaz. Referanslar SHA'larla dondurulur;
bir koşunun ortasında değişen dalın iki sürümü karıştırılmaz. İlk koşu son 30
güne bakar. Daha eski, elle seçilen konular için açık bir `--since` seçeneği olur.

Kaynak paketi repo kimliği, taranan aralık, commit SHA'ları, ilgili dosyalar ve
değişiklik özetini taşır. Dosyalar yalnız ihtiyaç halinde seçilen SHA'dan okunur.
Ortam dosyaları, anahtarlar, kullanıcı verisi, sağlayıcı kimlik bilgileri, ham
loglar ve özel altyapı adresleri içerik paketine alınmaz. Repo metinleri talimat
olarak değil, incelenecek veri olarak işlenir.

Ana dala girmiş olmak deploy kanıtı değildir. Canlı davranış iddiası gerekiyorsa
erişilebilir ürün veya dağıtım kaydı ayrıca doğrulanır; doğrulanamayan konu
uygulama kararı olarak anlatılır veya yayın ertelenir.

### 2. Konu seçimi ve kanıt

Otomasyon bağlantılı commitleri tek bir ürün değişikliği etrafında toplar. Konu
seçerken okuyucu ihtiyacı, teknik kararın özgünlüğü, kaynak yeterliliği ve mevcut
yazılarla örtüşme değerlendirilir. Bakım commitleri tek başına yazı gerekçesi
olmaz. Aynı commit kümesinden hem makale hem vaka incelemesi üretilmez.

İlk konu adayı piyasa raporudur: farklı veri kaynaklarını rapor bağlamında
birleştirmek, sayıları kaynak veriye bağlamak, editoryal kurallar ve raporu
kullanıcıya sunmak. Nihai başlık ve iddialar güncel koddan çıkarılacaktır.
Örnek yön: **Monafy'de Türkiye ve ABD borsaları için AI piyasa raporları**.

Her adayın kamuya açık olmayan kanıt dosyası vardır: iddia, repo/SHA/dosya
referansı, varsa ölçüm yöntemi, yayın kapsamı ve konu anahtarı. Özel repo
adresleri ve bu kanıt dosyası doğrudan public HTML'e kopyalanmaz.

### 3. Prompt ve yazarlık aşamaları

Sürüm kontrollü editoryal talimat üç ayrı geçiş tanımlar:

1. **Araştırmacı:** Ürün davranışını ve mühendislik kararını kaynaklarıyla çıkarır;
   gözleneni varsayımdan ayırır. Yayımlanabilir özgün katkı yoksa gerekçeyle durur.
2. **Teknik yazar:** Belirlenen okuyucu ihtiyacına göre makale veya vaka incelemesi
   yazar. Timurhan'ın katkısını doğru anlatır, iki dili bağımsız düzenler, başlık
   seçenekleri arasından açıklayıcı olanı seçer ve SEO alanlarını tamamlar.
3. **Editör:** İddiaları kanıt dosyasına karşı kontrol eder, iki dilin kapsamını
   karşılaştırır, mevcut yazılarla tekrarları ve gereksiz pazarlama dilini ayıklar.

Bu aşamalar ayrı agent gerektirmez. Editoryal kontrolün geçtiğini modelin tek
bir `approved` alanı belirlemez; kaynak referansları ve deterministik doğrulama
birlikte gerekir. Otomatik kontrol, insan incelemesi yapılmış gibi sunulmaz.

### 4. Doğrulama ve içerik paketi

`editorial:validate` şema, iki dilin zorunlu alanları, yayın türü, izin verilen
HTML, başlık/metadata, kaynak referanslarının varlığı, çalışma kapsamı ve tekrar
kontrollerini yapar. Public metin ve private kanıt ayrı tutulur. Script, iframe,
izleme kodu, ortam yolu veya özel altyapı bilgisi içeren paket yayımlanmaz.

Başarılı paket; sürüm, benzersiz paket kimliği, kararlı konu anahtarı, kaynak
SHA kümesi, iki dilli içerik ve üretim zamanını içerir. Mevcut `PostPublication`
ve uyarlanmış `StoryStyle` kuralları yeniden kullanılır. Önemli bir kaynak
çelişkisi veya doğrulama hatası varsa paket yerelde bekler ve nedeni bildirilir.

### 5. Prod yayını ve tekrar güvenliği

`editorial:publish` yerelde doğrulanmış public içerik paketini mevcut siteye ait
SSH bağlantısıyla geçici özel dizine aktarır; sunucudaki `editorial:import`
komutunu çağırır. Prod host ve dizin yerel yapılandırmadan alınır; tahmin edilen
bir hedefe yazılmaz. Yeni public yazma API'si açılmaz. Private kanıt kaynakları
prod'a aktarılmaz.

İçe aktarıcı paketi yeniden doğrular. `editorial_publications` kaydı ve `Post`
oluşturma aynı veritabanı transaction'ındadır. Konu anahtarı ve paket kimliği
benzersizdir; konu hakkındaki sonradan gelen commitler otomatik yeni yazı açmaz.
Soft-delete edilmiş otomasyon kayıtları tekrar yayımlanmaz. Slug çakışması
mevcut yazıyı ezmez. Üretimden sonra var olan yazıları otomatik yeniden yazmak
ilk sürüm kapsamına alınmaz.

Yeni içerik limiti sunucuda son yedi gün üzerinden, transaction içinde uygulanır;
yerel durum dosyasının silinmesi limiti sıfırlamaz. Aynı anda iki yayını engelleyen
kilit ve benzersiz kısıtlar birlikte kullanılır. Sunucu başarıdan sonra post
kimliğini, canonical adresleri ve paket kimliğini döndürür. Bağlantı bu aşamada
kesilirse aynı paketle tekrar deneme aynı kaydı döndürür.

Yayın sonrası iki dilde detay ve liste sayfaları okunur; başlık, canonical ve
yayın kaydı karşılaştırılır. RSS ve sitemap mevcut yayın modelinden beslenir.
IndexNow bildirimi içerik kalıcı hale geldikten sonra yapılır. IndexNow Google'a
indeks garantisi veya bildirim olarak sunulmaz. İçerik yayını rutin olarak kod
deploy'u, sunucuda Git commit veya frontend build'i gerektirmez.

### 6. Dış paylaşım taslakları

Her yayımlanan konu için yerelde Türkçe ve İngilizce kısa LinkedIn paylaşımı ve
DEV.to için özet/uyarlama taslağı hazırlanır. Taslaklar kişisel sitedeki gerçek
canonical adrese bağlanır; başka bir platformda yayımlanmış gibi kaydedilmez.
İleride tam metin kopyalanacaksa platformun canonical desteği ayrıca incelenir.

Kendi site içindeki ilgili yazı ve proje bağlantıları bağlama uygun eklenir.
Üçüncü taraf backlink oluşumu, dış yayın veya editoryal referans gerektirir;
bu otomasyon backlink sayısı, Google News kabulü veya arama sırası vaat etmez.

### 7. Zamanlama ve işletim

- Codex uygulamasının resmi otomasyon aracıyla bu projeye bağlı yerel zamanlanmış
  görev oluşturulur; otomasyon dosyaları elle yazılmaz.
- Günlük 10.00 Europe/Istanbul taraması yapılır. Bilgisayar ve uygulama açık
  olmalıdır. Kaçan koşu sonraki başarılı koşuda son kayıtlı SHA'dan devam eder.
- Talimat, komutlar, şema ve içerik politikası repoda; özel tarama/kanıt durumu
  Git'e girmeyen uygulama storage alanındadır.
- Aynı iş için mevcut otomasyon varsa güncellenir; çift görev kurulmaz.
- Yeni konu bulunmaması sessiz sonuçtur. Yeni yayın, gerekli kullanıcı müdahalesi
  veya hata kısa bir bildirim üretir. Günlük rutin durum mesajı gönderilmez.
- Oturum, ağ veya SSH hatasında yayın zorlanmaz; durum korunur ve sınırlı tekrar
  uygulanır. Hatalı paketler tekrar tekrar AI üretimiyle sınırsız tüketim yaratmaz.
- İlk uçtan uca deneme elle yürütülüp gerçek çıktısı incelenir; ardından görev
  etkinleştirilir. İlk piyasa raporu yazısı bu denemenin ürünü olabilir.

## Google News ve SEO kapsamı

Kullanıcının verdiği `13060993` rehberi, WordPress Reader Revenue Manager
kurulumudur. Mevcut site Laravel'dir; bu entegrasyon yazı gönderme amacıyla eklenmez.
Google News, Mart 2025'ten beri Publisher Center'a eklenen RSS/web kaynaklarını
kullanmıyor ve uygun içeriği otomatik değerlendiriyor. Kişisel mühendislik
yazıları yalnız bu amaçla haber gibi etiketlenmez; doğru Article/BlogPosting
işaretlemesi korunur.

Google'ın yaklaşımı faydalı, özgün, deneyime dayanan içerik ve doğru metadata'dır.
Bu yüzden yayın sıklığına yetişmek için metin üretilmez. Anahtar kelimeler doğal
başlık ve açıklamalarda kullanılır; tarih yalnız gerçek içerik değiştiğinde
güncellenir. AI yardımı, insan tarafından yapılmamış incelemeyi olmuş gibi
göstermeden açıklanabilir.

## Hata durumları ve geri alma

- Şüpheli veya eksik kanıt: yayımlama yok, özel dosyada somut gerekçe.
- Geçersiz paket/HTML/çeviri: transaction açılmadan reddet; prod'da yeniden kontrol et.
- Tekrarlanan paket: önceki başarılı sonucu döndür, ikinci yazı oluşturma.
- Semantik tekrar veya mevcut yazıyla konu çakışması: yeni yazı yayımlama.
- Ağ zaman aşımı: aynı paket kimliğiyle tekrar; yayınlanmış kaydı çoğaltma.
- Site kontrolü başarısız: başarı bildirme; oluşmuş post kimliğini raporla.
- Yanlış yayın: Filament üzerinden yayından kaldırma/soft-delete; import kaydı
  korunur ve aynı içerik kendiliğinden geri gelmez.
- Kod yayını sorununda önceki sürüme dönülebilir; mevcut içerik için alınan
  yayın öncesi yedek, yalnız seçilen kayıtların geri yüklenmesini sağlar.

## Uygulama sınırları ve doğrulama

İlgili alanlar: dil dosyaları ve portfolio JSON'ları, stories JSON'ları, ortak
kart şablonları ve CSS, `StructuredData`, OG üretim kaynakları, mevcut içerik
aktarımı, yeni editorial komutları/servisleri/yayın kaydı ve ilgili testler.
Monafy repoları bu işte kaynak olarak okunur; ürün kodu değiştirilmez.

Geliştirme boyunca kaynak incelemesi ve gerekli statik kontroller yapılır.
Kullanıcının talimatı gereği testler ve `npm run build` yalnız final aşamasında
çalıştırılır. Final doğrulaması şunları kapsar:

- Yayın şeması, kaynak keşfi/worktree tekilleştirmesi, artımlı tarama, gizli
  dosyaların dışlanması, doğrulama hatası, tekrar, slug çakışması, limit ve
  bağlantı kesilmesi sonrası tekrar senaryolarının otomatik testleri.
- İki dilde SEO/URL/metadata ve mevcut yayın akışının regresyon kontrolleri.
- Anasayfa, oyunlar, yazı listeleri, detay, iletişim ve CV'de mobil/masaüstü
  görsel kontrol; kart ayırıcılarının ve CTA'ların hizası.
- Final test paketi, içerik doğrulaması, build ve gerektiğinde OG üretimi.
- Prod deploy ve seçilmiş içerik senkronizasyonu; canonical, RSS, sitemap ve
  ilk otomatik yayın paketinin canlı okunarak doğrulanması.
- Resmi otomasyon aracıyla görevin etkin olarak kaydedildiğinin doğrulanması.

Başarı: site tutarlı kurucu ortak/AI kimliğini taşır, yenilenen yazılar canlıda
okunur, kartlar iki dilde hizalıdır ve yerel otomasyon örnek bir gerçek konuyu
kanıtlarıyla üretip tek kayıt olarak yayımlamıştır. Son kullanıcıya canlı kontrol
adresleri ve dış paylaşım taslaklarının yeri verilir. Çalıştırılmamış kontrol veya
kanıtlanmamış otomatik çalışma başarılı sayılmaz.

## Araştırma kaynakları

- https://developers.google.com/search/docs/fundamentals/creating-helpful-content
- https://developers.google.com/search/docs/appearance/title-link
- https://developers.google.com/search/docs/appearance/snippet
- https://developers.google.com/search/docs/fundamentals/using-gen-ai-content
- https://developers.google.com/search/docs/appearance/structured-data/article
- https://developers.google.com/search/docs/essentials/spam-policies
- https://support.google.com/news/publisher-center/answer/13060993
- https://support.google.com/news/publisher-center/answer/15898024
- https://learn.chatgpt.com/docs/automations?surface=app
- https://learn.chatgpt.com/docs/non-interactive-mode
- https://learn.chatgpt.com/docs/auth

Kaynaklar 20 Ekim 2026'da okundu. SEO sıralaması ve Google News görünürlüğü dış
sistemlerin değerlendirmesidir; uygulanabilir teknik ve editoryal kurallarla
karıştırılmaz.

## Tasarım öz incelemesi

20 Ekim 2026: Kaynak toplama ile public yayın verisi ayrımı, mevcut admin
düzenlemelerinin korunması, günlük tarama ile haftalık yayın limiti, bağlantı
kesilmesinde tekrar ve yanlış yayının geri alınması karşılaştırıldı. Başlıktaki
kimlik ile resmî iş unvanları ayrı tanımlandı. "Türkiye ve ABD borsaları"
düzeltmesi iki dil ve otomasyon talimatına taşındı. Açık yer tutucu yok.
