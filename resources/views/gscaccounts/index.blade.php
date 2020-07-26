@extends('adminlte::page')

@section('title', 'Загрузка данных из Google Search Console')

@section('content_header')
<h1 class="m-0 text-dark">Загрузка данных из Google Search Console</h1>
@stop

@section('content')
@include('notifications')
<div class="row">
    <div class="col-md-12">
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Адрес сайта</th>
                    <th>Доступно данных</th>
                    <th>Загружено или запланировано</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sites as $site)
                <tr>
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
                                <a href="{{route('gscaccounts.stop', $site->id)}}" class="btn btn-danger">Остановить</a>
                            </div>
                            @else
                            <a href="{{route('gscaccounts.load', $site->id)}}" class="btn btn-primary">Загрузить
                                данные</a>
                            <a href="{{route('gscaccounts.delete', $site->id)}}" class="btn btn-danger">Удалить
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

@section('js')
<script>
    async function updateParsent(){
        $('.gsc-site-progress').each(await async function(index, element){
            let site_id =  $(element).data('site-id');
            let response = await fetch('/gscaccounts/' + site_id + '/status');
            let data = await response.json();
            //console.log(data);
            $(element).css({width: data.parsent + '%'});
            $(element).text(data.parsent + '%');
            setTimeout(updateParsent, 1000);
        });
    }
    updateParsent();
</script>
@endsection