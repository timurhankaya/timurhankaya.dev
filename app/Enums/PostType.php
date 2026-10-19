<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PostType: string implements HasLabel
{
    case Article = 'article';
    case CaseStudy = 'case_study';

    public function getLabel(): string
    {
        return $this === self::CaseStudy ? __('brand.case_type') : __('brand.type_article');
    }
}
