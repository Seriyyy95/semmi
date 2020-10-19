<form method="GET" action="{{route('stats.select_site')}}">
    <select id="site_id" name="site_id" class="form-control">
        @foreach($sites as $site)
        @if($site_id == $site->id)
        <option value="{{$site->id}}" selected>{{$site->domain}}  @if(isset($site->profile_name)) ({{$site->profile_name}}) @endif</option>
        @else
        <option value="{{$site->id}}">{{$site->domain}} @if(isset($site->profile_name)) ({{$site->profile_name}}) @endif</option>
        @endif
        @endforeach
    </select>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            $('#site_id').change(function(){
                $(this).closest("form").submit();
            });
        },true);
    </script>
</form>
