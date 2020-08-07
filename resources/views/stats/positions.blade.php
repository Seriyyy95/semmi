@extends('adminlte::page')

@section('title', $title)

@section('content_header')
<div class="row">
    <div class="col-md-3">
        <h1 class="m-0 text-dark">{{$title}}</h1>
    </div>
    <div class="col-md-3">
        <form method="GET" id="interval-form">
            <select class="form-control" id="interval-select" name="interval">
                <option value="7" @if($interval == 7) selected="selected" @endif>Неделя</option>
                <option value="30" @if($interval == 30) selected="selected" @endif>Месяц</option>
                <option value="90" @if($interval == 90) selected="selected" @endif>Квартал</option>
                <option value="180" @if($interval == 180) selected="selected" @endif>Пол года</option>
            </select>
        </form>
    </div>
    <div class="col-md-3">
        <select class="form-control" id="search-select">
            <option value="-1" selected="selected">Все данные</option>
        </select>
    </div>
    <div class="col-md-3">
        @include ('siteselector')
    </div>
</div>
@endsection
@section('content')
@include('notifications')
<table class="table table-striped">
    <thead class="thead-dark">
        <th>Ссылка</th>
        <th>Ключ</th>
        @foreach($periods as $period)
        <th>{{ $period["start_date"]}} - {{$period["end_date"]}}</th>
        @endforeach
    </thead>
    <tbody id="data-container">
    </tbody>
</table>
@endsection

@section('js')
<script>
    Math.fmod = function (a,b) { return Number((a - (Math.floor(a / b) * b)).toPrecision(8)); };

    let urls = {!! json_encode($urls) !!};
    let periods = {{count($periods)}};
    let inProgress = false;
    let hasMore = true;
    let isSearch = false;

        function makeSearch(){
            let selectData = [];
            urls.forEach(function(element, index){
                selectData.push({
                    id: index,
                    text: element.url
                })
            });
            $("#search-select").select2({
                data: selectData,
                width: '200px'
            });
            $("#search-select").change(function(){
                let index = $(this).children("option:selected").val();
                if(index == -1){
                    isSearch = false;
                    $('#data-container').empty();
                    loadNextLine.index = 0;
                    loadNextLine();
                }else{
                    isSearch = true;
                    $('#data-container').empty();
                    loadNextLine.index = 0;
                    loadNextLine(index);
                }
            });
        }

        async function loadNextLine(index=null){
            if( typeof loadNextLine.index == 'undefined' ) {
                loadNextLine.index = 0;
            }
            if (index == null){
               index = loadNextLine.index;
               loadNextLine.index++;
            }
            if (typeof urls[index] == 'undefined'){
                return false;
            }
            let response = await fetch("/stats/get_url_positions?url=" + urls[index].url+"&field={{$field}}&agg_function={{$aggFunction}}&interval={{$interval}}");
            let data = await response.json();
            let hide = false;
            let hash, hidden = 0;
            data.forEach(function(element, index){
                hash = getHash(element.url);
                if(index == 10){
                    hide = true;
                }
                if(index > 10){
                    hidden++;
                }
                let tr = $('<tr></tr>');
                tr.append($('<td></td>').text(element.url));
                tr.append($('<td></td>').text(element.keyword));
                for(array_index = 0; array_index < periods; array_index++){
                    let value = element['row_' + array_index];
                    if(Math.fmod(value, 1) > 0){
                        value = value.toFixed(2);
                    }
                    if(value != null){
                        tr.append($('<td></td>').text(value).css("background-color", getColor(value, {{$minValue}},{{$maxValue}},{{$invertColor}})));
                    }else{
                        tr.append($('<td></td>').text("-"));
                    }
                }
                if(index == 0){
                    tr.css('font-weight', 'bold');
                }
                if(hide == true){
                    tr.css("display", "none");
                    tr.attr("data-id", hash);
                }
                $('#data-container').append(tr);
            });
            if(hide == true){
                let tr = $('<tr></tr>');
                let td = $('<td></td>').attr("colspan", "100%").attr("align", "center");
                td.text("Показать ещё (" + hidden + ")");
                td.attr("data-hash", hash);
                td.click( function() {
                    $("tr[data-id='"+ hash +"']").each(function(index, element){
                        if ($(this).css("display") == "none"){
                            $(this).css('display', 'table-row');
                            $("td[data-hash='"+hash+"']").text("Скрыть ("+hidden+")");
                        }else{
                            $(this).css('display', 'none');
                            $("td[data-hash='"+hash+"']").text("Показать ("+hidden+")");
                        }
                    })
                });
                tr.append(td);
                $('#data-container').append(tr);

            }
            if(loadNextLine.index < urls.length - 1){
                return true;
            }else{
                return false;
            }
        }

        function getHash(string){
            var hash = 0, i, chr;
            for (i = 0; i < string.length; i++) {
                chr   = string.charCodeAt(i);
                hash  = ((hash << 5) - hash) + chr;
                hash |= 0; 
            }
            return hash;
        }

        function getColor(value, min, max, mirror=0, brithness=0.8) {
            let ratio = value;
            if(max == 0){
              max = 1;
            }
            if (value < min) {
                value = min;
            } else if (value > max) {
                value = max;
            }
            if (min > 1) {
                ratio = value * 1 / min;
            } else {
                ratio = value;
            }
            if(mirror > 0){
              var hue = (max - ratio) * 120 / max;
            }else{
              var hue = ratio * 120 / max;
            }
            var rgb = hslToRgb(hue, 1, brithness);
            return "rgb(" + rgb[0] + "," + rgb[1] + "," + rgb[2] + ")";
        }

        function hslToRgb (h, s, l) {
            if (s === 0) return [l, l, l]
               h /= 360
            var q = l < 0.5 ? l * (1 + s) : l + s - l * s
            var p = 2 * l - q

            return [
                Math.round(hueToRgb(p, q, h + 1/3) * 255),
                Math.round(hueToRgb(p, q, h) * 255),
                Math.round(hueToRgb(p, q, h - 1/3) * 255)
            ]
        }

        function hueToRgb (p, q, t) {
            if (t < 0) t += 1
            if (t > 1) t -= 1
            if (t < 1/6) return p + (q - p) * 6 * t
            if (t < 1/2) return q
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6

            return p
        }

        makeSearch();
        loadNextLine();
        $(window).scroll(function() {
            if(($(window).scrollTop() + $(window).height() >= $(document).height() - 200) && !inProgress && hasMore == true && isSearch == false) {
                inProgress = true;
                loadNextLine().then(function(hasMore){
                    inProgress = false;
                    hasMore = hasMore;
                });
            }
        });
        $("#interval-select").change(function(){
            $(this).closest("form").submit();
        });
</script>
@endsection