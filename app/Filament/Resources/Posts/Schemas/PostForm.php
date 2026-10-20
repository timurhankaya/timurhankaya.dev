<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Portfolio\ProjectCatalog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        $editor = fn (string $name) => RichEditor::make($name)->label('İçerik')->fileAttachments(false)
            ->toolbarButtons([['bold', 'italic', 'underline', 'strike', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'table', 'horizontalRule'], ['undo', 'redo']])->columnSpanFull();

        return $schema->components([
            Section::make('Yayın ayarları')->schema([
                Select::make('type')->label('İçerik türü')->options(PostType::class)->default('article')->required()->live()
                    ->disabled(fn (?Post $record) => $record?->first_published_at !== null),
                TextInput::make('slug')->label('Kalıcı içerik anahtarı')->helperText('Eski bağlantılar için de korunur. Dil adreslerini aşağıdaki sekmelerde belirleyebilirsin.')->required()->maxLength(255)->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->disabled(fn (?Post $record) => $record?->first_published_at !== null),
                DateTimePicker::make('published_at')->label('Yayın tarihi')->seconds(false)->helperText('Boş bırak: taslak. İleri tarih: zamanlanmış yayın.'),
                FileUpload::make('cover_image')->label('Kapak görseli')->disk('public')->directory('content/covers')->visibility('public')
                    ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->preventFilePathTampering(),
            ])->columns(2)->columnSpanFull(),
            Tabs::make('Diller')->tabs([
                Tab::make('Türkçe · Ana içerik')->schema([
                    TextInput::make('slug_tr')->label('Türkçe adres')->maxLength(255)->unique(ignoreRecord: true)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->helperText('Boşsa kalıcı içerik anahtarı kullanılır. İlk yayından sonra değişmez.')
                        ->disabled(fn (?Post $record) => $record?->first_published_at !== null),
                    TextInput::make('title')->label('Başlık')->required()->maxLength(255)->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?Post $record): void {
                            if (! $record && blank($get('slug'))) {
                                $set('slug', Str::slug($state ?? '', '-', 'tr'));
                            }
                            if (! $record?->first_published_at && blank($get('slug_tr'))) {
                                $set('slug_tr', Str::slug($state ?? '', '-', 'tr'));
                            }
                        }),
                    Textarea::make('excerpt')->label('Özet')->rows(3)->maxLength(2000)->required(fn (Get $get) => filled($get('published_at'))),
                    $editor('content')->required(fn (Get $get) => filled($get('published_at'))),
                    Section::make('Arama sonucu (SEO)')->description('Boş bırakırsan başlık ve özet kullanılır.')->collapsed()->columnSpanFull()->schema([
                        TextInput::make('meta_title')->label('SEO başlığı')->maxLength(70)->helperText('En fazla 70 karakter. Marka adı otomatik eklenir.'),
                        Textarea::make('meta_description')->label('SEO açıklaması')->rows(2)->maxLength(160)->helperText('En fazla 160 karakter.'),
                    ]),
                ]),
                Tab::make('English · Optional')->schema([
                    TextInput::make('slug_en')->label('English URL slug')->maxLength(255)->unique(ignoreRecord: true)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->helperText('Leave empty to use the permanent content key. Fixed after first publication.')
                        ->disabled(fn (?Post $record) => $record?->first_published_at !== null),
                    TextInput::make('translations.en.title')->label('Title')->maxLength(255)->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?Post $record): void {
                            if (! $record?->first_published_at && blank($get('slug_en'))) {
                                $set('slug_en', Str::slug($state ?? ''));
                            }
                        }),
                    Textarea::make('translations.en.excerpt')->label('Summary')->rows(3)->maxLength(2000),
                    $editor('translations.en.content')->label('Content')->helperText('Eksik alanlar ana içerikten alınır.'),
                    Section::make('Search result (SEO)')->description('Leave empty to use the title and summary.')->collapsed()->columnSpanFull()->schema([
                        TextInput::make('translations.en.meta_title')->label('SEO title')->maxLength(70),
                        Textarea::make('translations.en.meta_description')->label('SEO description')->rows(2)->maxLength(160),
                    ]),
                ]),
            ])->columnSpanFull(),
            Section::make('Case study bilgileri')->schema([
                Select::make('project_key')->label('İlgili proje')->options(fn () => app(ProjectCatalog::class)->options())->searchable()->nullable()
                    ->helperText('Bir projeye birden fazla case study bağlanabilir; sıra aşağıdaki alanla belirlenir.'),
                TextInput::make('sort_order')->label('Seri sırası')->numeric()->integer()->minValue(0)->maxValue(65535)->default(100)->required()
                    ->helperText('Küçük sayı önce gelir. Aynı projedeki case study\'ler bu sırayla listelenir.'),
                TextInput::make('case_meta.project_name')->label('Proje adı')->required(fn (Get $get) => filled($get('published_at')))->maxLength(255),
                TextInput::make('case_meta.project_name_en')->label('Proje adı (İngilizce)')->helperText('Boşsa İngilizce sayfada da Türkçe ad görünür.')->maxLength(255),
                Select::make('case_meta.context')->label('Çalışma türü')->options(['founder' => 'Kurucu ortak', 'employment' => 'Maaşlı çalışma', 'client' => 'Müşteri çalışması', 'personal' => 'Kişisel proje'])->required(fn (Get $get) => filled($get('published_at'))),
                TextInput::make('case_meta.period')->label('Dönem')->helperText('İsimsiz işlerde boş bırakılabilir.')->maxLength(100),
                TagsInput::make('case_meta.technologies')->label('Teknolojiler')->required(fn (Get $get) => filled($get('published_at')))->splitKeys([','])->nestedRecursiveRules(['string', 'max:100']),
                TextInput::make('case_meta.url')->label('Proje bağlantısı')->url()->rules(['nullable', 'url:http,https'])->maxLength(2000),
                Repeater::make('case_meta.links')->label('İlgili bağlantılar')->maxItems(8)->defaultItems(0)->addActionLabel('Bağlantı ekle')->columnSpanFull()->columns(3)->schema([
                    TextInput::make('label.tr')->label('Etiket (TR)')->required()->maxLength(120),
                    TextInput::make('label.en')->label('Label (EN)')->maxLength(120),
                    TextInput::make('url')->label('Adres')->required()->url()->rules(['url:http,https'])->maxLength(2000),
                ]),
            ])->visible(fn (Get $get): bool => $get('type') === 'case_study' || $get('type') === PostType::CaseStudy)->columns(2)->columnSpanFull(),
        ]);
    }
}
