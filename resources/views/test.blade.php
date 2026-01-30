@if($errors->any())
<ul></ul>
@foreach($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
@endif
<form method="POST" action="{{ route('api.apps.reports.export') }}">
  @csrf
  <input type="hidden" name="account_id" value="1">
  <input type="text" name="start_date" value="{{ now()->startOfMonth() }}">
  <input type="text" name="end_date" value="{{ now()->endOfMonth() }}">
  <input type="text" name="format" value="pdf">
  
  <button type="submit">Export</button>
</form>