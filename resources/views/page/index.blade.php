@extends('adminlte::page')

@section('title', 'Статистика для страницы')

@section('content_header')
    <div class="row">
        <div class="col-md-2">
            <h1>Страница</h1>
        </div>
        <div class="col-md-7">
            <vue-select :options="this.select_options" :value="this.selected_option" label="value" :reduce="option => option.id" @input="searchItem">
            </vue-select>
        </div>
        <div class="col-md-3">
            @include ('siteselector', ["route" => "stats.select_ga_site"])
        </div>
    </div>

@endsection
@section('content')
    <div class="modal" id="modal-window" aria-hidden="true">
        <div class="modal-dialog modal-xl" style="width: 750px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Журнал</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        id="modal-window-close">
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

    @include('notifications')
    <div id="app">
        <h2>Просмотры страницы</h2>
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Год</th>
                    <th v-for="month in months" v-text="month"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in pageviewsData">
                    <td v-for="cell,index in record" v-text="cell" v-colorize-pageviews="index"></td>
                </tr>
                <tr>
                    <td><b>Всего</b></td>
                    <td colspan="12" style="text-align:center" v-text="pageviewsTotal"></td>
                </tr>
            </tbody>
        </table>
        <h2>Доход страницы</h2>
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Год</th>
                    <th v-for="month in months" v-text="month"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in revenueData">
                    <td v-for="cell, index in record" v-text="cell" v-colorize-revenue="index"></td>
                </tr>
                <tr>
                    <td><b>Всего</b></td>
                    <td colspan="12" style="text-align:center" v-text="revenueTotal"></td>
                </tr>
            </tbody>
        </table>
        <h2>Изменение позиции страницы</h2>
        <positions-chart v-bind:chart-data="this.positionsGraphData" :width="800" :height="300"></positions-chart>
        <h2>Изменение шторма страницы</h2>
        <storm-chart v-bind:chart-data="this.stormGraphData" :width="800" :height="300"></storm-chart>
        <h2>Ключевые слова</h2>
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Слово</th>
                    <th>Показы</th>
                    <th>Клики</th>
                    <th>Позиция</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="keyword in keywordsData">
                    <td v-text="keyword.keyword"></td>
                    <td v-text="keyword.total_impressions"></td>
                    <td v-text="keyword.total_clicks"></td>
                    <td v-text="keyword.avg_position"></td>
                    <td><button type="button" class="btn btn-warning" data-toggle="modal"
                        data-target="#modal-window" v-bind:data-remote="'/page/keyword_graph?keyword=' + encodeURI(keyword.keyword)">История</button></td>
                </tr>
            </tbody>
        </table>

    </div>
@endsection
@section('js')
    <script>
        const page = new Vue({
            el: ".wrapper",
            data: function() {
                return {
                    urls: {!!json_encode($urls) !!},
                    periodsMetadata: {!! json_encode($periodsMetadata) !!},
                    periods: {!! count($periods) !!},
                    startUrl: "{!! $startUrl !!}",
                    months: ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"],
                    select_options: [],
                    selected_option: null,
                    searchedUrl: '',
                    pageviewsData: [],
                    pageviewsTotal: 0,
                    revenueData: [],
                    revenueTotal: 0,
                    keywordsData: [],
                    positionsGraphData: {},
                    stormGraphData: {},
                };
            },
            components: {
                'vue-select': VueSelect.VueSelect,
                'positions-chart': {
                    extends: VueChartJs.Line,
                    mixins: [VueChartJs.mixins.reactiveProp],
                    mounted () {
                        this.renderChart(this.chartData, {responsive: true, maintainAspectRatio: true})
                    }
                },
                'storm-chart': {
                    extends: VueChartJs.Line,
                    mixins: [VueChartJs.mixins.reactiveProp],
                    mounted () {
                        this.renderChart(this.chartData, {responsive: true, maintainAspectRatio: true})
                    }
                }

            },
            methods: {
                loadPageviews: async function() {
                    let response = await fetch("/api/page/get_url_calendar?url=" + this.searchedUrl + "&field=pageviews&agg_function=sum&api_token={{$api_token}}&ga_site_id={{$site_id}}");
                    let data = await response.json();
                    let startYear = this.periodsMetadata["firstYear"];
                    let endYear = this.periodsMetadata["lastYear"];
                    let rowsCount = 0;
                    this.pageviewsTotal = data.total;
                    do{
                        let rowData = [];
                        rowData.push(startYear);
                        for(let monthIndex = 1; monthIndex <= 12; monthIndex++){
                            if(monthIndex < 10){
                                monthString = "0" + monthIndex + "" + startYear;
                            }else{
                                monthString = monthIndex + "" + startYear;
                            }
                            let value = -1;
                            if(typeof this.periodsMetadata.periods[monthString] !== "undefined"){
                                value = data['row_' + this.periodsMetadata.periods[monthString].index];
                            }
                            if(value > -1){
                                rowData.push(value);
                            }else{
                                rowData.push("-");
                            }
                        }
                        this.pageviewsData.push(rowData)
                        startYear++;
                        rowsCount++;
                    }while(startYear <= endYear);
                },
                loadRevenue: async function() {
                    let response = await fetch("/api/page/get_url_calendar?url=" + this.searchedUrl + "&field=adsenseRevenue&agg_function=sum&api_token={{$api_token}}&ga_site_id={{$site_id}}");
                    let data = await response.json();
                    let startYear = this.periodsMetadata["firstYear"];
                    let endYear = this.periodsMetadata["lastYear"];
                    let rowsCount = 0;
                    this.revenueTotal = (data.total).toFixed(2);
                    do{
                        let rowData = [];
                        rowData.push(startYear);
                        for(let monthIndex = 1; monthIndex <= 12; monthIndex++){
                            if(monthIndex < 10){
                                monthString = "0" + monthIndex + "" + startYear;
                            }else{
                                monthString = monthIndex + "" + startYear;
                            }
                            let value = -1;
                            if(typeof this.periodsMetadata.periods[monthString] !== "undefined"){
                                value = data['row_' + this.periodsMetadata.periods[monthString].index];
                            }
                            if(value > -1){
                                rowData.push((value).toFixed(2));
                            }else{
                                rowData.push("-");
                            }
                        }
                        this.revenueData.push(rowData)
                        startYear++;
                        rowsCount++;
                    }while(startYear <= endYear);
                },
                loadKeywordsData: async function(){
                    let response = await fetch("/api/page/get_url_keywords?url=" + this.searchedUrl + "&api_token={{$api_token}}&ga_site_id={{$site_id}}");
                    let data = await response.json();
                    this.keywordsData = data;
                },
                loadGraphData: async function(){
                    let response = await fetch("/api/page/get_url_graph?url=" + this.searchedUrl + "&api_token={{$api_token}}&ga_site_id={{$site_id}}");
                    let data = await response.json();
                    this.positionsGraphData = {
                        labels: data["headerData"],
                        datasets: [{
                            label: 'Позиция',
                            backgroundColor: '#f87979',
                            data: data["positionsData"],
                        }]
                    };
                    this.stormGraphData = {
                        labels: data["headerData"],
                        datasets: [{
                            label: 'Коефициент вариации',
                            backgroundColor: '#FF5733',
                            data: data["cvData"],
                        }]
                    };

                },
                loadData: function(){
                    this.pageviewsData = [];
                    this.revenueData = [];
                    this.keywordsData = [];
                    this.loadPageviews();
                    this.loadRevenue();
                    this.loadKeywordsData();
                    this.loadGraphData();
                },
                searchItem: function(index){
                    this.selected_option = this.select_options[index];
                    this.searchedUrl = this.selected_option.value;
                    this.loadData();
                },
            },
            computed: {

            },
            directives: {
                'colorize-pageviews': {
                    inserted: function(el, binding) {
                        let index = binding.value;
                        let value = parseFloat(el.textContent)
                        if(index > 0 && value > 0){
                            let color = getColor(value, 0, 600);
                            el.style.backgroundColor = color;
                        }
                    }
                },
                'colorize-revenue': {
                    inserted: function(el, binding) {
                        let index = binding.value;
                        let value = parseFloat(el.textContent)
                        if(index > 0 && value > 0){
                            let color = getColor(value, 0, 10);
                            el.style.backgroundColor = color;
                        }
                    }
                },
            },
            created: function() {
                for(let i = 0; i < this.urls.length; i++){
                    this.select_options.push({
                        id: i,
                        value: this.urls[i].url
                    });
                }
                if(this.select_options.length > 0){
                    if(this.startUrl.length > 0){
                        for(index in this.select_options) {
                            if(this.select_options[index].value == this.startUrl){
                                this.selected_option = this.select_options[index];
                                this.searchedUrl = this.select_options[index].value;
                            }
                        }
                    }
                    if(this.selected_option == null){
                        this.selected_option = this.select_options[0];
                        this.searchedUrl = this.selected_option.value;
                    }
                    this.loadData();
                }

            },
            destroyed() {
            }
        });
        document.addEventListener('DOMContentLoaded', function(){
            $('#site_id').change(function(){
                $(this).closest("form").submit();
            });
        },true);
        document.addEventListener('DOMContentLoaded', function(){
            $('#modal-window').on('show.bs.modal', function (e) {
                var button = $(e.relatedTarget);
                var modal = $(this);
                modal.find('.modal-body').load(button.data("remote"));
            });
        });

    </script>
@endsection
