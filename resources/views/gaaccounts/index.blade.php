@extends('adminlte::page')

@section('title', 'Загрузка данных из Google Analytics')

@section('content_header')
<h1 class="m-0 text-dark">Загрузка данных из Google Analytics</h1>
@stop

@section('content')
@include('notifications')
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Профиль</th>
                    <th>Домен</th>
                    <th>Доступно данных</th>
                    <th>Загружено или запланировано</th>
                    <th style="width: 250px">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sites as $site)
                <tr>
                    <td>{{$site->profile_name}}</td>
                    <td>{{$site->domain}}</td>
                    <td>{{$site->start_date}} - {{$site->end_date}}</td>
                    <td>{{$site->first_date}} - {{$site->last_date}}</td>
                    <td>
                        @if($site->hasActiveTasks())
                        <div class="row">
                            <div class="col-md-6">
                                <div class="progress" style="height: 40px;">
                                    <div class="gsc-site-progress progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                        role="progressbar" aria-valuenow="{{$site->parsent}}" aria-valuemin="0"
                                        aria-valuemax="100" style="width: {{$site->parsent}}%"
                                        data-site-id={{$site->id}}>
                                        {{$site->parsent}}%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <a href="{{route('gaaccounts.stop', $site->id)}}" class="btn btn-danger">Остановить</a>
                            </div>
                            @else
                            <a href="#" data-site-id="{{$site->id}}" class="btn btn-primary load-btn">Загрузить
                                данные</a>
                            <a href="{{route('gaaccounts.delete', $site->id)}}" class="btn btn-danger">Удалить
                                данные</a>
                            @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section("js")
    <script>
        async function updateParsent(){
            $('.gsc-site-progress').each(await async function(index, element){
                let site_id =  $(element).data('site-id');
                let response = await fetch('/gaaccounts/' + site_id + '/status');
                let data = await response.json();
                //console.log(data);
                $(element).css({width: data.parsent + '%'});
                $(element).text(data.parsent + '%');
                setTimeout(updateParsent, 1000);
            });
        }
        updateParsent();

        async function loadNextDates(site_id){
            console.log("Load next chunk");
            if(loadNextDates.taskId === "undefined"){
                loadNextDates.taskId = 0;
            }
            if(loadNextDates.count === "undefined"){
                loadNextDates.count = 0;
            }
            let response = await fetch("/gaaccounts/"+site_id+"/load?last_task_id="+loadNextDates.taskId);
            let data = await response.json();
            console.log(data)
            loadNextDates.count += data["count"]
            loadNextDates.taskId = data["last_task_id"]
            if(data["count"] == 0){
                return false;
            } else{
                return true;
            }

        }

        $(".load-btn").click(async function(evt){
            evt.preventDefault()
            let site_id = $(this).data("site-id")
            let spinner = $("<i></i>")
            spinner.addClass("fa fa-spinner fa-spin");
            $(this).empty();
            $(this).append(spinner);
            var timer = setInterval(await async function(){
                let result = await loadNextDates(site_id);
                if(result == false){
                    clearInterval(timer)
                    window.location.reload();
                }
            }, 200);
        });
    </script>

@endsection
