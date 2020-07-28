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
                @php ($balance = $totalGrown + $totalDown)
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
                <a class="nav-link active" data-toggle="tab" href="#keywords-grown-panel">Точки роста</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#keywords-down-panel">Точки падения</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#keywords-stable-panel">Не изменились</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div id="keywords-grown-panel" class="tab-pane fade in active show">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                    </thead>
                    <tbody>
                        @foreach($grownData as $row)
                        <tr>
                            <td>{{$row["keyword"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td style="color:green">+{{$row["result"]}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="keywords-down-panel" class="tab-pane fade in">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                    </thead>
                    <tbody>
                        @foreach($downData as $row)
                        <tr>
                            <td>{{$row["keyword"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td style="color:red">{{$row["result"]}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
             <div id="keywords-stable-panel" class="tab-pane fade in">
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <th>Ссылка</th>
                        <th>Первый период</th>
                        <th>Второй период</th>
                        <th>Разница</th>
                    </thead>
                    <tbody>
                        @foreach($stableData as $row)
                        <tr>
                            <td>{{$row["keyword"]}}</td>
                            <td>{{$row["data"]}}</td>
                            <td>{{$row["previous_data"]}}</td>
                            <td>{{$row["result"]}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
