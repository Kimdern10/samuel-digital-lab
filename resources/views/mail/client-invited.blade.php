<x-mail::message>
# Welcome, {{ $client->name }}

{{ $businessName }} has set up your client account. In it you can:

- follow your website's progress, step by step,
- open the preview link while we build,
- see and pay your invoices online.

<x-mail::button :url="$setPasswordUrl" color="success">
Set your password
</x-mail::button>

This link works for 48 hours. After that, go to [the login page]({{ $loginUrl }}) and choose "Forgot your password?" to get a new one.

Your login email is **{{ $client->email }}**.

{{ $businessName }}
</x-mail::message>
