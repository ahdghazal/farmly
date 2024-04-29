@component('mail::message')
# Hi {{ $name }},

Here is your OTP for resetting your password: {{ $otp }}

Thanks,<br>
{{ config('Farmly') }}
@endcomponent
