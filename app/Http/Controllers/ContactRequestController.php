<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\ContactRequest;
use App\Support\Seo;
use App\Support\VisitorIp;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;

class ContactRequestController extends Controller
{
    /** Humans need a few seconds to fill the form; anything faster is a script. */
    private const MIN_SECONDS = 3;

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! $this->looksAutomated($request)) {
            ContactRequest::create([
                ...$data,
                'services' => array_values($data['services'] ?? []) ?: null,
                'ip_hash' => hash_hmac('sha256', VisitorIp::of($request), (string) config('app.key')),
            ]);
        }

        // Bots get the same answer as people, so they learn nothing about the traps.
        return redirect()->to(Seo::route('contact', [], $data['locale']).'#form')->with('contact_sent', true);
    }

    private function looksAutomated(StoreContactRequest $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }
        try {
            $started = (int) decrypt((string) $request->input('started'));
        } catch (DecryptException) {
            return true;
        }

        return now()->timestamp - $started < self::MIN_SECONDS;
    }
}
