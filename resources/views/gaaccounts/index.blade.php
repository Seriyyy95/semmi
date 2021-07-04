@extends('adminlte::page')

@section('title', 'Загрузка данных из Google Analytics')

@section('content_header')
    <div class="modal" id="request-details" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Журнал</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        id="request-details-close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Что то пошло не так... :(</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<div class="row">
    <div class="col-md-9">
        <h1 class="m-0 text-dark">Загрузка данных из Google Analytics</h1>
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-warning" data-toggle="modal"
            data-target="#request-details"
            data-remote="{{route('logs.index', "ga")}}">Журнал</button>
    </div>
</div>
@endsection

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
                    <th>Автозагрузка</th>
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
                        <label class="checkbox">
                            <input type="checkbox" class="autoload-checkbox" autocomplete="off" data-site-id="{{$site->id}}" @if($site->autoload) checked @endif>
                        </label>
                    </td>
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
                let response = await fetch('/api/gaaccounts/' + site_id + '/status?api_token={{$api_token}}');
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
            let response = await fetch("/api/gaaccounts/"+site_id+"/load?api_token={{$api_token}}&last_task_id="+loadNextDates.taskId);
            let data = await response.json();
            loadNextDates.count += data["count"]
            loadNextDates.taskId = data["last_task_id"]
            if(data["count"] === 0){
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
            var loader = async function(){
                let result = await loadNextDates(site_id);
                if(result === false){
                    window.location.reload();
                }else{
                    setTimeout(loader, 200)
                }
            };
            loader();
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        $('#request-details').on('show.bs.modal', function (e) {
            var button = $(e.relatedTarget);
            var modal = $(this);
            modal.find('.modal-body').load(button.data("remote"));
        });
        $('.autoload-checkbox').on('change', async function (e) {
            var site_id = $(this).data('site-id');
            var state = "disabled";
            if($(this).is(":checked")){
                state = "enabled";
            }
            let response = await fetch("/api/gaaccounts/"+site_id+"/autoload/" + state + "?api_token={{$api_token}}");
            let data = await response.json();
        });


    });
    </script>

@endsection
