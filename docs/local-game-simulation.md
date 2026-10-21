# Yerel oyun simülasyonu

`APP_ENV=local` olan geliştirme ortamında Word Imposter veya Vampir Köylü odası
oluştur. Oda sahibinin ekranında **Yerel test** paneli görünür. `APP_DEBUG` bu
özelliği açmaz; production ve staging ortamları sunucuda reddedilir.

1. Lobide bot sayısını seçip **Bot ekle** düğmesine bas. Her odada en fazla 12 bot
   bulunabilir. Parolalı odalar da aynı normal katılım kurallarını kullanır.
2. Vampir Köylü için paneldeki oyuncu / rol toplamını karşılaştır ve mevcut rol
   ayarlarını eşitle. Bot eklemek bu ayarları değiştirmez. Oyun en az dört oyuncu
   ister; Word Imposter için en az üç oyuncu gerekir.
3. Oyunu mevcut oda sahibi düğmesiyle başlat. **Botları oynat** mevcut aşamadaki
   uygun botları çalıştırır. Word Imposter insanın oy sırasına geldiğinde bekler.
4. İstersen **Otomatik hamleler** seçeneğini aç. Botlar açık sayfanın yenileme
   döngüsünde oynar. Bu seçenek varsayılan kapalıdır; sekme kapanınca çalışmaz.
5. Aşama geçişlerini ve sonuç açıklamasını mevcut oda sahibi kontrollerinden
   yap. Lobide **Botları kaldır** yalnız simülatörün eklediği botları kaldırır.

Vampir Köylü'nün mevcut tek aktif gece aşamasında vampir, doktor ve dedektif
hamleleri yapılabilir. Gece sonuçlandırılınca bot yeni gece hamlesi yapmaz.
Doktor önceki gece koruduğu hedefi, dedektif daha önce sorguladığı hedefi seçmez.
Ölü bot normal oy kullanmaz; ölen avcı için oyunun son atış kuralı geçerlidir.
Hedefler botun kendi oyuncu görünümünden seçilir; gizli roller tercihe katılmaz.

Oda sahibi değişirse otomatik hamleler durur. Yeni oda sahibi panelden tekrar
açabilir. Bot kaydı oda ve oyun türüne özeldir; sayfaya bot kimlik kaydı veya
diğer oyuncuların gizli bilgileri gönderilmez. Eşzamanlı istekler simülasyon
kilidinde sıralanır, her oyun işlemi ayrıca kendi oda kilidinde doğrulanır.

Bu araç oyun servislerinin akışını inceler. Ayrı gerçek tarayıcı oturumlarını,
bağlantı kopmasını, ağ gecikmesini veya insanın blöf davranışını temsil etmez.
