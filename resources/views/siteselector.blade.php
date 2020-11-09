<form method="GET" action="{{route($route)}}">
    <select id="site_id" name="site_id" class="form-control">
        @foreach($sites as $site)
        @php ($current_site_id = isset($site->site_id)? $site->site_id : $site->id) @endphp
        @if($site_id == $current_site_id)
        <option value="{{$current_site_id}}" selected>{{$site->domain}}  @if(isset($site->profile_name)) ({{$site->profile_name}}) @endif</option>
        @else
        <option value="{{$current_site_id}}">{{$site->domain}} @if(isset($site->profile_name)) ({{$site->profile_name}}) @endif</option>
        @endif
        @endforeach
    </select>
</form>
