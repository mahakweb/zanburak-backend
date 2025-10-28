@component('vendor.mail.html.layout')
{{-- Header --}}
@slot('header')
@component('vendor.mail.html.header', ['url' => config('app.url')])
{{--  {{ config('app.name') }}  --}}
<img src="https://api.zanburak.ir/assets/images/logo/logo-wide.svg" class="logo" alt="zanburak Logo">
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
