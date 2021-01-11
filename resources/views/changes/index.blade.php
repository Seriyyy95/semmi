@extends('adminlte::page')

@section('title', "Изменение переходов на сайт")

@section('content_header')
<div class="row">
    <div class="col-md-9">
        <form method="GET" id="periods-form">
            <div class="row">
                <div class="col-md-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control float-right" name="first_period" id="first-period">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control float-right" name="second_period" id="second-period">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="submit" value="ОК" class="btn btn-primary">
                </div>
            </div>
        </form>
    </div>
    <div class="col-md-3">
        @include ('siteselector', ["route" => "stats.select_ga_site"])
    </div>
</div>
@endsection
@section('content')
@include('notifications')
<div class="modal" id="keywords-details" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Выбранные слова</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    id="keywords-details-close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <textarea id="selected-words" class="form-control" rows="20"></textarea>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<table class="table table-striped">
    <thead class="thead-dark">
        <th>Показатель</th>
        <th>Значение</th>
    </thead>
    <tbody id="data-container">
        <tr>
            <td>Всего за первый период:</td>
            <td>{{$totalData}}</td>
        </tr>
        <tr>
            <td>Всего рост:</td>
            <td style="color:green">+{{$totalGrown}}</td>
        </tr>
        <tr>
            <td>Всего падение:</td>
            <td style="color:red">{{$totalDown}}</td>
        </tr>
        <tr>
            <td>Баланс:</td>
            <td>
                @php ($balance = $totalGrown + $totalDown) @endphp
                @if ($balance > 0)
                    <span style="color:green">+{{$balance}}</span>
                @else
                    <span style="color:red">{{$balance}}</span>
                @endif
            </td>
        </tr>

        <tr>
            <td>Точек роста:</td>
            <td>{{$countGrown}}</td>
        </tr>
        <tr>
            <td>Точек падения:</td>
            <td>{{$countDown}}</td>
        </tr>
        <tr>
            <td>Не изменилось:</td>
            <td>{{$countStable}}</td>
        </tr>
    </tbody>
</table>
<h3>Подробности</h3>
<div class="card">
    <div class="card-header d-flex p-0">
        <ul class="nav nav-pills ml-auto p-2">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#grown-panel">Точки роста</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#down-panel">Точки падения</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#stable-panel">Не изменились</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div id="grown-panel" class="tab-pane fade in active show">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                        <th>Подробности</th>
                    </thead>
                    <tbody>
                        @foreach($grownData as $row)
                        <tr>
                            <td>{{$row["url"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td style="color:green">+{{$row["result"]}}</td>
                            <td>
                                @php $params = array() @endphp
                                @php $params["url"] = $row["url"] @endphp
                                @php $params["first_period"] = $first_period["startDate"] . " - ". $first_period["endDate"] @endphp
                                @php $params["second_period"] = $second_period["startDate"] . " - ". $second_period["endDate"] @endphp

                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#keywords-details"
                                data-remote="{{route('changes.keywords', $params)}}">Ключи</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="down-panel" class="tab-pane fade in">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                        <th>Детали<th>
                    </thead>
                    <tbody>
                        @foreach($downData as $row)
                        <tr>
                            <td>{{$row["url"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td style="color:red">{{$row["result"]}}</td>
                            <td>
                                @php
                                    $params = array();
                                    $params["url"] = $row["url"];
                                    $params["first_period"] = $first_period["startDate"] . " - ". $first_period["endDate"];
                                    $params["second_period"] = $second_period["startDate"] . " - ". $second_period["endDate"];
                                @endphp
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#keywords-details"
                                data-remote="{{route('changes.keywords', $params)}}">Ключи</button>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
             <div id="stable-panel" class="tab-pane fade in">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                        <th>Детали</th>
                    </thead>
                    <tbody>
                        @foreach($stableData as $row)
                        <tr>
                            <td>{{$row["url"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td>{{$row["result"]}}</td>
                            <td>
                                @php
                                    $params = array();
                                    $params["url"] = $row["url"];
                                    $params["first_period"] = $first_period["startDate"] . " - ". $first_period["endDate"];
                                    $params["second_period"] = $second_period["startDate"] . " - ". $second_period["endDate"];
                                @endphp
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                data-target="#keywords-details"
                                data-remote="{{route('changes.keywords', $params)}}">Ключи</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    let fStartDate = '{{$first_period["startDate"]}}';
    let fEndDate = '{{$first_period["endDate"]}}';
    let sStartDate = '{{$second_period["startDate"]}}';
    let sEndDate = '{{$second_period["endDate"]}}';

    $("#first-period").daterangepicker({
        minDate: '{{$minDate}}',
        maxDate: '{{$maxDate}}',
        startDate: fStartDate,
        endDate: fEndDate,
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    $("#second-period").daterangepicker({
        minDate: '{{$minDate}}',
        maxDate: '{{$maxDate}}',
        startDate: sStartDate,
        endDate: sEndDate,
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    document.addEventListener('DOMContentLoaded', function(){
        $('#keywords-details').on('show.bs.modal', function (e) {
            var button = $(e.relatedTarget);
            var modal = $(this);
            modal.find('.modal-body').load(button.data("remote"));
        });
    });
    document.addEventListener('DOMContentLoaded', function(){
            $('#site_id').change(function(){
                $(this).closest("form").submit();
            });
    },true);
</script>
@endsection
