let chart;

function afficherGraphique() {

    const heures = document.querySelectorAll(".g_heure");
    const temps = document.querySelectorAll(".g_temp");
    const hums = document.querySelectorAll(".g_hum");

    let labels = [];
    let dataTemp = [];
    let dataHum = [];

    for (let i = 0; i < heures.length; i++) {
        labels.push(heures[i].textContent.trim());
        dataTemp.push(parseFloat(temps[i].textContent.trim()));
        dataHum.push(parseFloat(hums[i].textContent.trim()));
    }

    
    labels.reverse();
    dataTemp.reverse();
    dataHum.reverse();

    const ctx = document.getElementById("graph").getContext("2d");

    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Température",
                    data: dataTemp,
                    borderWidth: 2
                },
                {
                    label: "Humidité",
                    data: dataHum,
                    borderWidth: 2
                }
            ]
        }
    });
}