@component('mail::message')
# Köszönjük a rendelésed, {{ $order->user->name }}!

Megkaptuk a megrendelésed (azonosító: **#{{ $order->id }}**), és máris
elkezdjük összeállítani. A státuszt bármikor megnézheted a
[fiókodban]({{ config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')) }}/account/orders).

## Rendelés tételei

@component('mail::table')
| Termék                                     | Mennyiség | Egységár       | Összesen       |
|:-------------------------------------------|:---------:|---------------:|---------------:|
@foreach ($order->items as $item)
| {{ $item->product->name ?? 'Termék #' . $item->product_id }} | {{ $item->quantity }} | {{ number_format($item->unit_price, 0, ',', ' ') }} Ft | {{ number_format($item->subtotal, 0, ',', ' ') }} Ft |
@endforeach
@endcomponent

## Összegzés

- **Végösszeg:** {{ number_format($order->total_amount, 0, ',', ' ') }} Ft
- **Fizetési mód:** {{ $order->payment_method }}
- **Fizetési státusz:** {{ $order->payment_status }}
- **Rendelés státusza:** {{ $order->status }}

## Szállítási cím

@if ($order->address)
{{ $order->address->street }}<br>
{{ $order->address->zip_code }} {{ $order->address->city }}<br>
{{ $order->address->country }}
@else
*Cím nem áll rendelkezésre.*
@endif

@if ($order->notes)
## Megjegyzések
{{ $order->notes }}
@endif

@component('mail::button', ['url' => config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')) . '/account/orders', 'color' => 'primary'])
Rendelés megtekintése
@endcomponent

Ha kérdésed van, válaszolj erre az e-mailre, és segítünk!

Üdvözlettel,<br>
{{ config('app.name') }}
@endcomponent
