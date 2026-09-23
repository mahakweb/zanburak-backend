@component('vendor.mail.html.layout')
{{-- Header --}}
@slot('header')
@component('vendor.mail.html.header', ['url' => config('app.frontend_url', 'https://zanburak.ir')])
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('vendor.mail.html.subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('vendor.mail.html.footer')
© {{ date('Y') }} @lang(':appName All rights reserved.', ['appName' => config('app.name')])
@endcomponent
@endslot
@endcomponent
