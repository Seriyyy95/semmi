<h3>История изменения позиций<h3>
<canvas id="positionsChart" width="600" height="300"></canvas>
<h3>История изменения шторма<h3>
<canvas id="stormChart" width="600" height="300"></canvas>
<h3>История изменения кликов<h3>
<canvas id="clicksChart" width="600" height="300"></canvas>
<h3>История изменения показов<h3>
<canvas id="impressionsChart" width="600" height="300"></canvas>

<script>
    var positionsCanvas = document.getElementById("positionsChart");
    var clicksCanvas = document.getElementById("clicksChart");
    var impressinsCanvas = document.getElementById("impressionsChart");
    var stormCanvas = document.getElementById("stormChart");

    var positionsChart = new Chart(positionsCanvas, {
        type: 'line',
        data: {
            labels: {!! json_encode($headerData) !!},
            datasets: [{
                    label: 'Позиция',
                    data: {!! json_encode($positionsData) !!},
                    borderColor: 'orange',
                    backgroundColor: 'transparent',
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'top',
                labels: {
                    boxWidth: 80,
                    fontColor: 'black'
                }
            }
        }
    });
    var stormChart = new Chart(stormCanvas, {
        type: 'line',
        data: {
            labels: {!! json_encode($headerData) !!},
            datasets: [{
                    label: 'Коефициент вариации',
                    data: {!! json_encode($cvData) !!},
                    borderColor: 'gray',
                    backgroundColor: 'transparent',
            }]
        },
        options: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    boxWidth: 80,
                    fontColor: 'black'
                }
            }
        }
    });

    var clicksChart = new Chart(clicksCanvas, {
        type: 'line',
        data: {
            labels: {!! json_encode($headerData) !!},
            datasets: [{
                    label: 'Клики',
                    data: {!! json_encode($clicksData) !!},
                    borderColor: 'green',
                    backgroundColor: 'transparent',
            }]
        },
        options: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    boxWidth: 80,
                    fontColor: 'black'
                }
            }
        }
    });
    var impressionsChart = new Chart(impressinsCanvas, {
        type: 'line',
        data: {
            labels: {!! json_encode($headerData) !!},
            datasets: [{
                    label: 'Показы',
                    data: {!! json_encode($impressionsData) !!},
                    borderColor: 'red',
                    backgroundColor: 'transparent',
            }]
        },
        options: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    boxWidth: 80,
                    fontColor: 'black'
                }
            }
        }
    });

</script>
