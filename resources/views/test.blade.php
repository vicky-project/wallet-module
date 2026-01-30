@if($errors->any())
<ul></ul>
@foreach($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
@endif
<form method="POST" action="{{ config('app.url') }}/api/apps/reports/export">
  @csrf
  <input type="hidden" name="account_id" value="1">
  <input type="date" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
  <input type="date" name="end_date" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
  <input type="text" name="format" value="xlsx">
  
  <button type="submit">Export</button>
</form>