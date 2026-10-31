<x-mail::message>
# Your call is booked

Hi {{ $consultation->name }},

Thanks for booking a free call with {{ $businessName }}.

**{{ $consultation->when() }}**
{{ $consultation->minutes }}-minute {{ strtolower($consultation->methodLabel()) }}. We'll contact you on {{ $consultation->phone }}.

<x-mail::button :url="$calendarUrl" color="success">
Add to Google Calendar
</x-mail::button>

Need to change the time? Message us on WhatsApp at {{ $whatsApp }}.

{{ $businessName }}
</x-mail::message>
