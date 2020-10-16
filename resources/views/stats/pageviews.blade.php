@extends('adminlte::page')

@section('title', $title)

@section('content_header')
<div class="row">
    <div class="col-md-3">
        <h1 class="m-0 text-dark">{{$title}}</h1>
    </div>
    <div class="col-md-6">
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
        <th>Год</th>
        @php ($months = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12")) @endphp
        @foreach($months as $month)
        <th>{{$month}}</th>
        @endforeach
    </thead>
    <tbody id="data-container">
    </tbody>
</table>
@endsection

@section('js')
@php ($isSearch = Session::has("search_url") ? true : 0) @endphp
<script>
    Math.fmod = function (a,b) { return Number((a - (Math.floor(a / b) * b)).toPrecision(8)); };

    let urls = {!! json_encode($urls) !!};
    let periodsMetadata = {!! json_encode($periodsMetadata) !!};
    let periods = {{count($periods)}};
    let inProgress = false;
    let hasMore = true;
    let isSearch = {{$isSearch}};
    let callback = '{{$callback}}';

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
                    loadNextLine(index, true);
                }
            });
        }

        async function loadNextLine(index=null, search_query=0){
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
            let response = await fetch(callback + "?url=" + urls[index].url+"&field={{$field}}&agg_function={{$aggFunction}}&is_search=" + search_query);
            let data = await response.json();

            data.forEach(function(element, index){
                let startYear = periodsMetadata["firstYear"];
                let endYear = periodsMetadata["lastYear"];
                let titleSpan = endYear - startYear+2;
                let rowsCount = 0;
                let total = element.total;
                if(Math.fmod(total, 1) > 0){
                    total = total.toFixed(2);
                }

                do{
                    let tr = $('<tr></tr>');
                    if(rowsCount == 0){
                        tr.append($('<td></td>').text(element.url).attr("rowspan", titleSpan));
                    }
                    tr.append($('<td></td>').text(startYear));
                    for(let monthIndex = 1; monthIndex <= 12; monthIndex++){
                        if(monthIndex < 10){
                            monthString = "0" + monthIndex + "" + startYear;
                        }else{
                            monthString = monthIndex + "" + startYear;
                        }
                        let value = -1;
                        if(typeof periodsMetadata.periods[monthString] !== "undefined"){
                            value = element['row_' + periodsMetadata.periods[monthString].index];
                        }
                        if(Math.fmod(value, 1) > 0){
                            value = value.toFixed(2);
                        }
                        if(value > -1){
                            tr.append($('<td></td>').text(value).attr("title", periodsMetadata.periods[monthString].period).css("background-color", getColor(value, {{$minValue}},{{$maxValue}},{{$invertColor}})));
                        }else{
                            tr.append($('<td></td>').text("-"));
                        }
                    }
                    $('#data-container').append(tr);
                    startYear++;
                    rowsCount++;
                }while(startYear <= endYear);
                let tr = $('<tr></tr>');
                tr.append($('<td></td>').text("Всего").css("font-weight", "bold"));
                tr.append($('<td></td>').text(total).attr("colspan", 12).css("text-align", "center"));
                $('#data-container').append(tr);
            });
            if(loadNextLine.index < urls.length - 1){
                return true;
            }else{
                return false;
            }
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
        if(isSearch == false){
            loadNextLine();
            loadNextLine();
            loadNextLine();
            loadNextLine();
        }
        $(window).scroll(function() {
            if(($(window).scrollTop() + $(window).height() >= $(document).height() - 200) && !inProgress && hasMore == true && isSearch == false) {
                inProgress = true;
                loadNextLine().then(function(hasMore){
                    inProgress = false;
                    hasMore = hasMore;
                });
            }
        });


</script>
@if(Session::has('search_url'))
    <script>
        isSearch = true;
        let url = "{!! Session::get('search_url')!!}";
        index = urls.findIndex(param => param.url === url);
        console.log(index);
        $("#search-select").val(index).trigger('change');
    </script>
@endif

@endsection
