<?php

namespace App\Http\Requests;

use App\Models\ContactRequest;
use App\Support\Seo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The form posts to one address for both languages, so errors follow the page the visitor wrote on. */
    protected function prepareForValidation(): void
    {
        $locale = in_array($this->input('locale'), Seo::supportedLocales(), true) ? $this->input('locale') : Seo::fallbackLocale();
        app()->setLocale($locale);
        $this->merge(['locale' => $locale]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'topic' => ['required', Rule::in(ContactRequest::TOPICS)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'company' => ['nullable', 'string', 'max:120'],
            'services' => ['nullable', 'array', 'max:6'],
            'services.*' => [Rule::in(ContactRequest::SERVICES)],
            'budget' => ['nullable', 'string', 'max:120'],
            'timeline' => ['nullable', Rule::in(ContactRequest::TIMELINES)],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'locale' => ['required', Rule::in(Seo::supportedLocales())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'topic.*' => __('brand.form_error_topic'),
            'name.required' => __('brand.form_error_name'),
            'name.max' => __('brand.form_error_long'),
            'email.*' => __('brand.form_error_email'),
            'company.max' => __('brand.form_error_long'),
            'budget.max' => __('brand.form_error_long'),
            'services.*' => __('brand.form_error_topic'),
            'timeline.*' => __('brand.form_error_topic'),
            'message.required' => __('brand.form_error_message'),
            'message.min' => __('brand.form_error_message'),
            'message.max' => __('brand.form_error_message_long'),
        ];
    }
}
