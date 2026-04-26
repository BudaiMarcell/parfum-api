@component('mail::message')
# Üdvözlünk a Buttercup Perfumery-nél, {{ $name }}!

Köszönjük, hogy regisztráltál. A folytatáshoz erősítsd meg az e-mail címed
az alábbi gombra kattintva:

@component('mail::button', ['url' => $verifyUrl, 'color' => 'primary'])
E-mail cím megerősítése
@endcomponent

Ha nem te regisztráltál a Buttercup Perfumery oldalán, hagyd figyelmen
kívül ezt az üzenetet — a fiók nem aktiválódik megerősítés nélkül.

A megerősítő link **60 percig** érvényes. Ha lejár, jelentkezz be a
fiókodba, és kérj új linket a profil beállításaiból.

Üdvözlettel,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Ha a gomb nem működik, másold be ezt a hivatkozást a böngésződbe:
[{{ $verifyUrl }}]({{ $verifyUrl }})
@endcomponent
@endcomponent
