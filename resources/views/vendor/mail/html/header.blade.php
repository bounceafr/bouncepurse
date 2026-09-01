@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ \App\Support\VersionedAsset::url('bounce_logo.png') }}" class="logo" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
