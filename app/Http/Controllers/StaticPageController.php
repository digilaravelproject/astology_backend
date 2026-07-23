<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;

class StaticPageController extends Controller
{
    public function show(string $type)
    {
        $page = StaticPage::where('type', $type)->where('is_active', true)->firstOrFail();
        return view('static-pages.show', compact('page'));
    }

    public function faq()              { return $this->show('faq'); }
    public function privacyPolicy()    { return $this->show('privacy_policy'); }
    public function termsConditions() { return $this->show('terms_and_conditions'); }
    public function paymentPolicy()   { return $this->show('payment_policy'); }
    public function aboutUs()          { return $this->show('about_us'); }
    public function customerSupport()  { return $this->show('customer_support'); }
    public function contactUs()         { return $this->show('contact_us'); }
}
