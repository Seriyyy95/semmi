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
                <th>Загружено ссылок</th>
                <th>Профиль Google Analytics</th>
                <th>Цена</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sites as $site)
                <tr>
                    <td>{{$site->domain}}</td>
                    <td>{{$site->count}}</td>
                    <td>
                        <form action="{{route('wpconnector.bind')}}" method="POST">
                            @csrf
                            <input type="hidden" name="site_id" value="{{$site->id}}" />
                            <select name="ga_site" class="form-control site-select">
                                <option value="0" disabled selected>Не выбрано</option>
                                @foreach($gaSites as $gSite)
                                    @if($site->ga_site_id == $gSite->id)
                                        <option value="{{$gSite->id}}" selected>{{$gSite->domain}} ({{$gSite->profile_name}})</option>
                                    @else
                                        <option value="{{$gSite->id}}">{{$gSite->domain}} ({{$gSite->profile_name}})</option>
                                    @endif
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="{{route('wpconnector.savePrice')}}" method="POST">
                            @csrf
                            <input type="hidden" name="site_id" value="{{$site->id}}" />
                            <input type="text" name="price" value="{{$site->price}}" class="form-control price-input" />
                       </form>

                    </td>
                    <td>
                        @if($site->hasActiveTasks())
                            <div data-button-id="{{$site->id}}" style="display:none">
                                <a href="{{route('wpconnector.update', array('site_id' => $site->id))}}" class="btn btn-warning">Обновить</a>
                            </div>
                            <div class="wp-loader" data-site-id="{{$site->id}}" data-status="progress">
                                <i class="fa fa-spinner fa-spin"></i>
                            </div>
                        @else
                            <div data-button-id="{{$site->id}}" >
                                <a href="{{route('wpconnector.update', array('site_id' => $site->id))}}" class="btn btn-warning">Обновить</a>
                            </div>
                            <div class="wp-loader" data-site-id="{{$site->id}}" data-status="finished" style="display:none">
                                <i class="fa fa-spinner fa-spin"></i>
                            </div>
                        @endif
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
        },true);
        document.addEventListener("DOMContentLoaded", function(){
            $(".price-input").change(function(){
                $(this).closest("form").submit();
            });
        },true);
        document.addEventListener("DOMContentLoaded", function(){
            setInterval(function(){
                $(".wp-loader").each(async function(index,element){
                    let site_id = $(element).data("site-id");
                    let status = $(element).attr("data-status");
                    let response = await fetch("/wpconnector/status?site_id="+site_id);
                    let data = await response.json();
                    if(data.status == "progress" && status == "finished"){
                        $('div[data-button-id='+site_id+']').css('display', 'none');
                        $(element).css('display', 'block');
                        $(element).attr("data-status", "progress");
                    }else if(data.status == "finished" && status == "progress"){
                        $('div[data-button-id='+site_id+']').css('display', 'block');
                        $(element).css('display', 'none');
                        $(element).attr("data-status", "finished");
                    }
                });
            }, 2000);
        },true);

    </script>
@endsection
