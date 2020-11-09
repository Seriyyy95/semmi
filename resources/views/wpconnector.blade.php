@extends('adminlte::page')

@section('title', "Настройка WPConnector")

@section('content_header')
<h1>Настройка WPConnector</h1>
@endsection
@section('content')
@include('notifications')
<form method="POST" action="{{route('wpconnector.save')}}">
    <div class="form-group">
        <label for="wpconnector_key">Ключ для доступа к WPConnector</label>
        <input type="text" name="wpconnector_key" value="{{$wpconnectorKey}}" class="form-control" />
    </div>
    <input type="submit" class="btn btn-primary" value="Сохранить" />
    @csrf
</form>
@if(count($sites) > 0)
    <h3>Доступные сайты</h3>
    <table class="table table-stripped">
        <thead>
            <tr>
                <th>Сайт</th>
                <th>Профиль Google Analytics</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sites as $site_id=>$site)
                <tr>
                    <td>{{$site}}</td>
                    <td>
                        <form action="{{route('wpconnector.bind')}}" method="POST">
                            @csrf
                            <input type="hidden" name="site_id" value="{{$site_id}}" />
                            <select name="ga_site" class="form-control site-select">
                                <option value="0" disabled selected>Не выбрано</option>
                                @foreach($gaSites as $gSite)
                                    @if(isset($bindings[$site_id]) && $bindings[$site_id] == $gSite->id)
                                        <option value="{{$gSite->id}}" selected>{{$gSite->domain}} ({{$gSite->profile_name}})</option>
                                    @else
                                        <option value="{{$gSite->id}}">{{$gSite->domain}} ({{$gSite->profile_name}})</option>
                                    @endif
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <a href="{{route('wpconnector.update', array('site_id' => $site_id))}}" class="btn btn-warning">Обновить</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </ul>
@endif
@endsection
@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            $(".site-select").change(function(){
                $(this).closest("form").submit();
            });
        },true)
    </script>
@endsection
